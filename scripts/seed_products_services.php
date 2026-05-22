<?php
/**
 * Seed Product and Service nodes from docs/CONTENT.md.
 *
 * Creates:
 *   - 6 Product nodes
 *   - 10 Service nodes
 *   - Solution nodes (from the "## Solutions" section in CONTENT.md)
 *
 * For each node it populates:
 *   - title, body (HTML), field_summary, field_seo_title, field_meta_description
 *   - field_mission_impact (when present in source)
 *   - field_key_capabilities (one paragraph:capability per "Key Capabilities" bullet)
 *   - path alias (/products/{slug}, /services/{slug})
 *   - status = published, moderation_state = published
 *   - best-effort taxonomy refs (field_platform, field_target_sectors,
 *     field_personas) — only set when matching terms already exist in the vocab.
 *     See docs/TAXONOMY_AUDIT.md for vocabularies that need seeding first.
 *
 * Idempotent — safe to re-run. Re-running matches existing nodes by path alias
 * and, by default, skips them. Will NOT overwrite editorial changes made
 * through the admin UI.
 *
 * Modes:
 *   (default)   skip-if-exists. Create missing nodes; leave existing ones alone.
 *   --dry-run   report what would happen without writing anything to the DB.
 *               Overrides --update.
 *   --update    when a node already exists, overwrite its CONTENT.md-sourced
 *               fields (title, body, field_summary, field_seo_title,
 *               field_meta_description, field_mission_impact,
 *               field_key_capabilities) with the latest values from
 *               docs/CONTENT.md. Old capability paragraphs are deleted and
 *               re-created. Taxonomy refs are NOT touched in update mode so
 *               editor-applied classification is preserved. Emits a warning
 *               when the existing node's `changed` timestamp drifted from its
 *               `created` timestamp — that node has been edited since seed and
 *               --update will clobber those edits.
 *
 * Run:
 *   ddev drush scr scripts/seed_products_services.php
 *   ddev drush scr scripts/seed_products_services.php -- --dry-run
 *   ddev drush scr scripts/seed_products_services.php -- --update
 *   ddev drush scr scripts/seed_products_services.php -- --dry-run --update
 *
 * On production:
 *   docker compose exec drupal drush scr /var/www/html/scripts/seed_products_services.php -- --update
 *
 * The `--` separator is required so Drush passes the flags through to the
 * script as $extra instead of trying to parse them itself.
 *
 * Source of truth for copy: docs/CONTENT.md. Do not edit copy in this script —
 * edit CONTENT.md and re-run with --update, or edit the nodes directly through
 * the admin UI.
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

/** Path to the content source-of-truth, relative to the Drupal docroot. */
const WL_CONTENT_MD_PATH = __DIR__ . '/../docs/CONTENT.md';

/** Body text format. Matches the headless-safe filter used for frontend rendering. */
const WL_TEXT_FORMAT_BODY = 'headless_safe';

/** Text format for short formatted fields (mission impact, capability descriptions). */
const WL_TEXT_FORMAT_INLINE = 'headless_safe';

/** Default moderation state for seeded content. Change to 'draft' if editorial review is required before publish. */
const WL_DEFAULT_MODERATION_STATE = 'published';

/**
 * Tolerance in seconds when deciding whether a node has been edited since
 * seed. Node::save() can update `changed` a moment after `created` even on a
 * fresh entity, so we allow a small window before flagging the node as edited.
 */
const WL_EDIT_DRIFT_SECONDS = 5;

// ---------------------------------------------------------------------------
// CLI flag parsing
// ---------------------------------------------------------------------------
//
// Drush passes positional script arguments to the included script in $extra
// when invoked as `drush scr <script> -- --dry-run`. Fall back to scanning
// $_SERVER['argv'] for the same flags so the script also works when run via
// other PHP entry points.

$wl_argv = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$WL_DRY_RUN = in_array('--dry-run', $wl_argv, true);
$WL_UPDATE  = in_array('--update', $wl_argv, true);

// ---------------------------------------------------------------------------
// Markdown → HTML (tiny purpose-built converter)
// ---------------------------------------------------------------------------

/**
 * Convert a small subset of Markdown to HTML.
 *
 * Handles:
 *   - **bold** → <strong>bold</strong>
 *   - blank-line separated paragraphs → <p>...</p>
 *   - lines starting with "- " grouped into <ul><li>...</li></ul>
 *   - HTML-escapes everything else
 *
 * This is intentionally simple. Source content lives in docs/CONTENT.md which
 * only uses these constructs. Anything more complex should be authored as HTML
 * in the node directly.
 */
function wl_md_to_html(string $markdown): string {
  $lines = preg_split('/\r\n|\n|\r/', trim($markdown));
  $blocks = [];
  $buf = [];
  $mode = null; // null | 'p' | 'ul'

  $flush = function () use (&$buf, &$mode, &$blocks) {
    if (!$buf) {
      return;
    }
    if ($mode === 'ul') {
      $items = array_map(static fn(string $li): string => '<li>' . wl_md_inline(trim(substr($li, 1))) . '</li>', $buf);
      $blocks[] = '<ul>' . implode('', $items) . '</ul>';
    }
    else {
      $blocks[] = '<p>' . wl_md_inline(implode(' ', $buf)) . '</p>';
    }
    $buf = [];
    $mode = null;
  };

  foreach ($lines as $line) {
    $line = rtrim($line);
    if ($line === '') {
      $flush();
      continue;
    }
    if (preg_match('/^\s*-\s+/', $line)) {
      if ($mode !== 'ul') {
        $flush();
        $mode = 'ul';
      }
      $buf[] = ltrim($line);
    }
    else {
      if ($mode === 'ul') {
        $flush();
      }
      $mode = 'p';
      $buf[] = $line;
    }
  }
  $flush();

  return implode("\n", $blocks);
}

/** Inline conversion: **bold** + HTML-escape. */
function wl_md_inline(string $text): string {
  $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
}

// ---------------------------------------------------------------------------
// CONTENT.md parser
// ---------------------------------------------------------------------------

/**
 * Parse docs/CONTENT.md into a structured array.
 *
 * Returns:
 *   [
 *     'products' => [ ['title' => ..., 'seo_title' => ..., 'meta_description' => ...,
 *                      'summary' => ..., 'capabilities' => [...], 'mission_impact' => ...,
 *                      'body_paragraphs' => [...] ], ... ],
 *     'services' => [ ... ],
 *   ]
 */
function wl_parse_content_md(string $path): array {
  if (!is_readable($path)) {
    throw new RuntimeException("CONTENT.md not readable at: {$path}");
  }
  $src = file_get_contents($path);

  // Split on top-level section headers.
  $sections = preg_split('/^##\s+(Products|Services|Solutions)\s*$/m', $src, -1, PREG_SPLIT_DELIM_CAPTURE);
  // After split: [preamble, 'Products', ..., 'Services', ..., 'Solutions', ...]
  $result = ['products' => [], 'services' => [], 'solutions' => []];

  for ($i = 1; $i < count($sections); $i += 2) {
    $kind = strtolower($sections[$i]);  // 'products' | 'services' | 'solutions'
    $body = $sections[$i + 1] ?? '';
    $result[$kind] = wl_parse_entries($body);
  }

  return $result;
}

/**
 * Parse a Products or Services section body into entries.
 *
 * Each entry begins with "### N. Title".
 */
function wl_parse_entries(string $section_body): array {
  $entries = [];
  // Split on "### N. Title" lines, capturing the title.
  $parts = preg_split('/^###\s+\d+\.\s+(.+?)\s*$/m', $section_body, -1, PREG_SPLIT_DELIM_CAPTURE);
  for ($i = 1; $i < count($parts); $i += 2) {
    $title = trim($parts[$i]);
    $body = trim($parts[$i + 1] ?? '');
    // Strip trailing horizontal-rule separator if present.
    $body = preg_replace('/\n---\s*$/', '', $body);
    $entries[] = wl_parse_entry($title, $body);
  }
  return $entries;
}

/**
 * Parse one entry body. The body has:
 *   **SEO Title:** ...
 *   **Meta Description:** ...
 *   **Summary:** ...                          (Products always; Services sometimes)
 *   **Full Page Copy:**
 *   #### Title repeat
 *   ...paragraphs...
 *   **Key Capabilities**
 *   - bullet
 *   - bullet
 *   **Mission Impact**
 *   paragraph
 */
function wl_parse_entry(string $title, string $body): array {
  $entry = [
    'title' => $title,
    'seo_title' => wl_extract_field($body, 'SEO Title'),
    'meta_description' => wl_extract_field($body, 'Meta Description'),
    'summary' => wl_extract_field($body, 'Summary'),
    'capabilities' => [],
    'mission_impact' => null,
    'body_paragraphs' => [],
  ];

  // Isolate the Full Page Copy block.
  $copy = '';
  if (preg_match('/\*\*Full Page Copy:\*\*\s*(.*)$/s', $body, $m)) {
    $copy = trim($m[1]);
  }
  if ($copy === '') {
    return $entry;
  }

  // Drop the leading #### heading (it's a redundant restatement of the title).
  $copy = preg_replace('/^####\s+.+?\n/', '', $copy);

  // Pull out the Mission Impact block (always last when present).
  if (preg_match('/\*\*Mission Impact\*\*\s*(.*)$/s', $copy, $m)) {
    $entry['mission_impact'] = trim($m[1]);
    $copy = preg_replace('/\*\*Mission Impact\*\*.*$/s', '', $copy);
  }

  // Pull out the Key Capabilities block.
  if (preg_match('/\*\*Key Capabilities\*\*\s*\n(.+?)(?=\n\s*\*\*|\z)/s', $copy, $m)) {
    $bullets = preg_split('/\n/', trim($m[1]));
    foreach ($bullets as $b) {
      $b = trim($b);
      if ($b === '' || !str_starts_with($b, '-')) {
        continue;
      }
      $entry['capabilities'][] = trim(substr($b, 1));
    }
    $copy = preg_replace('/\*\*Key Capabilities\*\*.*?(?=\n\s*\*\*|\z)/s', '', $copy);
  }

  // Whatever's left is narrative paragraphs.
  $entry['body_paragraphs'] = array_values(array_filter(
    array_map('trim', preg_split('/\n\s*\n/', trim($copy))),
    static fn(string $p): bool => $p !== ''
  ));

  return $entry;
}

/** Extract a "**Label:** value" line. Returns null if not present. */
function wl_extract_field(string $body, string $label): ?string {
  $pattern = '/\*\*' . preg_quote($label, '/') . ':\*\*\s*(.+?)(?=\n\s*\n|\n\*\*|\z)/s';
  if (preg_match($pattern, $body, $m)) {
    return trim($m[1]);
  }
  return null;
}

// ---------------------------------------------------------------------------
// Drupal helpers
// ---------------------------------------------------------------------------

/** Find an existing node by its path alias. Returns null if not found. */
function wl_find_node_by_alias(string $alias): ?Node {
  /** @var \Drupal\path_alias\AliasRepositoryInterface $repo */
  $repo = \Drupal::service('path_alias.repository');
  $lookup = $repo->lookupByAlias($alias, 'en');
  if (!$lookup || !preg_match('|^/node/(\d+)$|', $lookup['path'], $m)) {
    return null;
  }
  return Node::load($m[1]);
}

/** Best-effort taxonomy term lookup by name. Returns the term ID or null. */
function wl_term_id_by_name(string $vid, string $name): ?int {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $ids = $storage->getQuery()
    ->condition('vid', $vid)
    ->condition('name', $name)
    ->accessCheck(true)
    ->range(0, 1)
    ->execute();
  if (!$ids) {
    return null;
  }
  return (int) reset($ids);
}

/** Map zero or more term names to taxonomy reference field values. */
function wl_term_refs(string $vid, array $names): array {
  $refs = [];
  foreach ($names as $name) {
    $tid = wl_term_id_by_name($vid, $name);
    if ($tid !== null) {
      $refs[] = ['target_id' => $tid];
    }
  }
  return $refs;
}

/** Build a slug from a title — kebab-case ASCII, no diacritics. */
function wl_slug(string $title): string {
  $s = strtolower($title);
  $s = preg_replace('/&/', ' and ', $s);
  $s = preg_replace('/[^a-z0-9]+/', '-', $s);
  return trim($s, '-');
}

/**
 * Build a capability paragraph from a bullet string. Re-uses an existing
 * paragraph entity if the same title already exists attached to this node.
 *
 * field_capability_description is required by config but CONTENT.md only
 * supplies titles — we seed the description with the title so the entity
 * validates, and editors expand it later.
 */
function wl_build_capability_paragraph(string $title): Paragraph {
  $p = Paragraph::create([
    'type' => 'capability',
    'field_capability_title' => $title,
    'field_capability_description' => [
      'value' => '<p>' . wl_md_inline($title) . '</p>',
      'format' => WL_TEXT_FORMAT_INLINE,
    ],
  ]);
  $p->save();
  return $p;
}

// ---------------------------------------------------------------------------
// Best-effort taxonomy mapping
// ---------------------------------------------------------------------------

/**
 * Map entry title → suggested taxonomy term names for soft-classification.
 * Only used when the matching term already exists in the vocabulary; missing
 * terms are silently skipped (see docs/TAXONOMY_AUDIT.md).
 */
function wl_taxonomy_suggestions(string $bundle, string $title): array {
  $defaults = [
    'platform' => null,        // platforms vocab — exact platform brand name
    'target_sectors' => [],    // target_sectors vocab
    'personas' => [],          // persona vocab (vid is singular)
    'solutions' => [],         // solutions taxonomy
  ];

  // Products: each Product *is* a platform. The taxonomy term, if seeded,
  // shares the product title verbatim per the recommendation in
  // docs/TAXONOMY_AUDIT.md.
  if ($bundle === 'product') {
    $defaults['platform'] = $title;
    $defaults['target_sectors'] = ['Defense', 'Federal Government'];
  }
  elseif ($bundle === 'service') {
    $defaults['target_sectors'] = ['Defense', 'Federal Government'];
    // Light keyword routing.
    if (stripos($title, 'Zero-Trust') !== false) {
      $defaults['solutions'][] = 'Zero Trust';
    }
    if (stripos($title, 'AI') !== false || stripos($title, 'Machine Learning') !== false) {
      $defaults['solutions'][] = 'Private LLMs';
    }
    if (stripos($title, 'Digital Modernization') !== false) {
      $defaults['solutions'][] = 'Digital Modernization';
    }
    if (stripos($title, 'Cryptocurrency') !== false || stripos($title, 'Digital Asset') !== false) {
      $defaults['solutions'][] = 'Financial Optimization';
    }
  }
  elseif ($bundle === 'solution') {
    // Solutions are the "branded package" — they can reference themselves in the solutions taxonomy
    $defaults['solutions'][] = $title; // or map to a taxonomy term if naming differs
    $defaults['target_sectors'] = ['Defense', 'Federal Government'];
  }

  return $defaults;
}

// ---------------------------------------------------------------------------
// Main: seed one entry
// ---------------------------------------------------------------------------

/**
 * True when the node's `changed` timestamp has drifted from `created` by more
 * than WL_EDIT_DRIFT_SECONDS — i.e. it has been edited since the seed run.
 */
function wl_node_was_edited(Node $node): bool {
  $created = (int) $node->getCreatedTime();
  $changed = (int) $node->getChangedTime();
  return ($changed - $created) > WL_EDIT_DRIFT_SECONDS;
}

/**
 * Build the CONTENT.md-sourced field values for a node. Shared between create
 * and update paths so both see the same parsing of CONTENT.md.
 *
 * Capability paragraphs are created up front and returned by reference so the
 * caller can attach them to the node. Taxonomy is returned separately — it's
 * only applied on create (update mode preserves editor-applied taxonomy).
 */
function wl_build_node_values(string $bundle, array $entry, string $alias): array {
  $body_md = implode("\n\n", $entry['body_paragraphs']);
  $body_html = wl_md_to_html($body_md);

  $capability_refs = [];
  foreach ($entry['capabilities'] as $cap_title) {
    $p = wl_build_capability_paragraph($cap_title);
    $capability_refs[] = ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()];
  }

  $values = [
    'type' => $bundle,
    'title' => $entry['title'],
    'status' => 1,
    'moderation_state' => WL_DEFAULT_MODERATION_STATE,
    'path' => ['alias' => $alias],
    'body' => [
      'value' => $body_html,
      'format' => WL_TEXT_FORMAT_BODY,
    ],
    'field_summary' => $entry['summary'] ?? '',
    'field_seo_title' => $entry['seo_title'] ?? '',
    'field_meta_description' => $entry['meta_description'] ?? '',
    'field_key_capabilities' => $capability_refs,
  ];

  if ($entry['mission_impact']) {
    $values['field_mission_impact'] = [
      'value' => wl_md_to_html($entry['mission_impact']),
      'format' => WL_TEXT_FORMAT_INLINE,
    ];
  }

  $taxo = wl_taxonomy_suggestions($bundle, $entry['title']);
  $taxo_values = [];
  if ($taxo['platform']) {
    $tid = wl_term_id_by_name('platforms', $taxo['platform']);
    if ($tid !== null) {
      $taxo_values['field_platform'] = [['target_id' => $tid]];
    }
  }
  if ($taxo['target_sectors']) {
    $refs = wl_term_refs('target_sectors', $taxo['target_sectors']);
    if ($refs) {
      $taxo_values['field_target_sectors'] = $refs;
    }
  }
  if ($taxo['personas']) {
    $refs = wl_term_refs('persona', $taxo['personas']);
    if ($refs) {
      $taxo_values['field_personas'] = $refs;
    }
  }
  if (!empty($taxo['solutions'])) {
    $refs = wl_term_refs('solutions', $taxo['solutions']);
    if ($refs) {
      $taxo_values['field_solutions'] = $refs;
    }
  }

  return [
    'values' => $values,
    'capability_refs' => $capability_refs,
    'taxonomy' => $taxo_values,
  ];
}

/**
 * Update an existing node with the latest CONTENT.md values. Replaces the
 * CONTENT.md-sourced fields and the capability paragraphs; leaves taxonomy
 * fields untouched so editor classifications survive. Old capability paragraph
 * entities are deleted to avoid orphans.
 */
function wl_update_existing_node(Node $node, array $built, string $alias): array {
  // Collect old capability paragraphs so we can delete them after the node
  // save commits the new references.
  $old_caps = [];
  if ($node->hasField('field_key_capabilities')) {
    foreach ($node->get('field_key_capabilities')->referencedEntities() as $p) {
      $old_caps[] = $p;
    }
  }

  $values = $built['values'];
  // Don't reset type or path on update — type is immutable, and resetting the
  // alias would create a duplicate path_alias row.
  unset($values['type'], $values['path'], $values['status'], $values['moderation_state']);

  foreach ($values as $field => $value) {
    if ($node->hasField($field)) {
      $node->set($field, $value);
    }
  }

  $node->save();

  foreach ($old_caps as $p) {
    try {
      $p->delete();
    }
    catch (\Throwable $e) {
      // Best-effort cleanup — leave orphan in place rather than failing the run.
    }
  }

  return [
    'status' => 'updated',
    'nid' => (int) $node->id(),
    'alias' => $alias,
    'capabilities' => count($built['capability_refs']),
  ];
}

function wl_seed_entry(string $bundle, array $entry, bool $dry_run, bool $update): array {
  $title = $entry['title'];
  $slug = wl_slug($title);
  $path_prefix = match($bundle) {
    'product' => '/products',
    'service' => '/services',
    'solution' => '/solutions',
    default => '/unknown',
  };
  $alias = $path_prefix . '/' . $slug;

  $existing = wl_find_node_by_alias($alias);

  if ($existing) {
    $edited = wl_node_was_edited($existing);

    if (!$update) {
      return [
        'status' => $dry_run ? 'would-skip' : 'skipped',
        'reason' => 'exists',
        'nid' => (int) $existing->id(),
        'alias' => $alias,
        'edited_since_seed' => $edited,
      ];
    }

    if ($dry_run) {
      return [
        'status' => 'would-update',
        'nid' => (int) $existing->id(),
        'alias' => $alias,
        'edited_since_seed' => $edited,
      ];
    }

    $built = wl_build_node_values($bundle, $entry, $alias);
    $result = wl_update_existing_node($existing, $built, $alias);
    $result['edited_since_seed'] = $edited;
    return $result;
  }

  // No existing node — create or report would-create.
  if ($dry_run) {
    return ['status' => 'would-create', 'alias' => $alias];
  }

  $built = wl_build_node_values($bundle, $entry, $alias);
  $values = $built['values'] + $built['taxonomy'];

  $node = Node::create($values);
  $node->save();

  return [
    'status' => 'created',
    'nid' => (int) $node->id(),
    'alias' => $alias,
    'capabilities' => count($built['capability_refs']),
    'taxonomy_applied' => array_keys($built['taxonomy']),
  ];
}

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------

$mode_label = $WL_DRY_RUN
  ? ($WL_UPDATE ? 'DRY-RUN + UPDATE (no DB writes)' : 'DRY-RUN (no DB writes)')
  : ($WL_UPDATE ? 'UPDATE (existing nodes will be overwritten)' : 'SKIP-IF-EXISTS (default)');

echo "=== Seeding Products, Services & Solutions from " . WL_CONTENT_MD_PATH . " ===\n";
echo "Mode: {$mode_label}\n\n";

$parsed = wl_parse_content_md(WL_CONTENT_MD_PATH);

$status_keys = ['created', 'skipped', 'updated', 'would-create', 'would-skip', 'would-update'];
$summary = [
  'product'   => array_fill_keys($status_keys, 0),
  'service'   => array_fill_keys($status_keys, 0),
  'solutions' => array_fill_keys($status_keys, 0),   // plural to match $parsed['solutions']
];

// Ensure all three bundles are always present in the summary (prevents undefined key warnings)
foreach (['product', 'service', 'solutions'] as $b) {
  if (!isset($summary[$b])) {
    $summary[$b] = array_fill_keys($status_keys, 0);
  }
}
$warnings = [];

$render = function (string $bundle, array $entry, array $r) use (&$warnings): void {
  switch ($r['status']) {
    case 'created':
      $taxo = $r['taxonomy_applied'] ? ' [taxonomy: ' . implode(',', $r['taxonomy_applied']) . ']' : '';
      echo sprintf("  [+] %s  nid=%d  alias=%s  capabilities=%d%s\n",
        $entry['title'], $r['nid'], $r['alias'], $r['capabilities'], $taxo);
      break;
    case 'updated':
      $note = !empty($r['edited_since_seed']) ? ' [WARN: editor changes overwritten]' : '';
      echo sprintf("  [~] %s  nid=%d  alias=%s  capabilities=%d%s\n",
        $entry['title'], $r['nid'], $r['alias'], $r['capabilities'], $note);
      if (!empty($r['edited_since_seed'])) {
        $warnings[] = sprintf('Updated %s node "%s" (nid=%d) had been edited since seed — those edits are now overwritten.',
          $bundle, $entry['title'], $r['nid']);
      }
      break;
    case 'skipped':
      $note = !empty($r['edited_since_seed']) ? ' [edited since seed]' : '';
      echo sprintf("  [=] %s  (exists, nid=%d, alias=%s) — skipped%s\n",
        $entry['title'], $r['nid'], $r['alias'], $note);
      break;
    case 'would-create':
      echo sprintf("  [+?] %s  alias=%s — WOULD CREATE\n", $entry['title'], $r['alias']);
      break;
    case 'would-update':
      $note = !empty($r['edited_since_seed']) ? ' [WARN: would overwrite editor changes]' : '';
      echo sprintf("  [~?] %s  nid=%d  alias=%s — WOULD UPDATE%s\n",
        $entry['title'], $r['nid'], $r['alias'], $note);
      if (!empty($r['edited_since_seed'])) {
        $warnings[] = sprintf('WOULD overwrite editor changes on %s node "%s" (nid=%d) if --update were re-run without --dry-run.',
          $bundle, $entry['title'], $r['nid']);
      }
      break;
    case 'would-skip':
      $note = !empty($r['edited_since_seed']) ? ' [edited since seed]' : '';
      echo sprintf("  [=?] %s  (exists, nid=%d, alias=%s) — WOULD SKIP%s\n",
        $entry['title'], $r['nid'], $r['alias'], $note);
      break;
    default:
      echo sprintf("  [?] %s  status=%s\n", $entry['title'], $r['status']);
  }
};

echo "--- Products ---\n";
foreach ($parsed['products'] as $entry) {
  $r = wl_seed_entry('product', $entry, $WL_DRY_RUN, $WL_UPDATE);
  $summary['product'][$r['status']]++;
  $render('product', $entry, $r);
}

echo "\n--- Services ---\n";
foreach ($parsed['services'] as $entry) {
  $r = wl_seed_entry('service', $entry, $WL_DRY_RUN, $WL_UPDATE);
  $summary['service'][$r['status']]++;
  $render('service', $entry, $r);
}

echo "\n--- Solutions ---\n";
foreach ($parsed['solutions'] as $entry) {
  $r = wl_seed_entry('solution', $entry, $WL_DRY_RUN, $WL_UPDATE);
  $summary['solutions'][$r['status']]++;
  $render('solution', $entry, $r);
}

echo "\n=== Summary ===\n";
$fmt = function (string $label, array $counts): string {
  $counts = $counts ?? [];
  $parts = [];
  foreach ($counts as $k => $v) {
    if ($v > 0) {
      $parts[] = "{$k}={$v}";
    }
  }
  return sprintf("%-10s %s\n", $label . ':', $parts ? implode('  ', $parts) : '(none)');
};
echo $fmt('Products', $summary['product'] ?? []);
echo $fmt('Services', $summary['service'] ?? []);
echo $fmt('Solutions', $summary['solutions'] ?? []);

if ($warnings) {
  echo "\n=== Warnings ===\n";
  foreach ($warnings as $w) {
    echo "  ! {$w}\n";
  }
}

if ($WL_DRY_RUN) {
  echo "\nDry run: no nodes were created, updated, or deleted. Re-run without --dry-run to apply.\n";
}
else {
  echo "\nReview at /admin/content. Capability paragraphs were seeded with title-as-description placeholders; editors should expand field_capability_description and add field_mission_benefit.\n";
  echo "Taxonomy refs were only applied on create where matching terms exist (preserved as-is in update mode). See docs/TAXONOMY_AUDIT.md to seed missing vocabularies.\n";
}
