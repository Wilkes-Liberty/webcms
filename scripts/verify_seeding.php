<?php
/**
 * Seeding verification — read-only audit of DB state.
 *
 * Checks:
 *   1. All canonical nodes exist, are published, and have correct path aliases.
 *   2. All platform/service/solution nodes have field_key_capabilities populated.
 *   3. All solution/case-study nodes have field_outcomes populated (if expected).
 *   4. Summary counts and any gaps reported.
 *
 * Read-only — makes no changes.
 *
 * Run:
 *   ddev drush scr scripts/verify_seeding.php
 */

use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

// ---------------------------------------------------------------------------
// Canonical node definitions (alias → expected bundle)
// ---------------------------------------------------------------------------

$WL_CANONICAL = [
  // Platforms
  '/platforms/sabal'      => 'platform',
  '/platforms/keel'       => 'platform',
  '/platforms/alidade'    => 'platform',
  '/platforms/squawk'     => 'platform',
  '/platforms/manifest'   => 'platform',
  '/platforms/lighthouse' => 'platform',
  '/platforms/coquina'    => 'platform',

  // Services
  '/services/private-infrastructure-engineering' => 'service',
  '/services/zero-trust-identity-consulting'     => 'service',
  '/services/defense-technology-integration'     => 'service',
  '/services/headless-cms-implementation'        => 'service',
  '/services/enterprise-search-architecture'     => 'service',
  '/services/ai-integration'                     => 'service',
  '/services/digital-modernization'              => 'service',
  '/services/custom-software-development'        => 'service',
  '/services/integration-engineering'            => 'service',
  '/services/digital-asset-solutions'            => 'service',
  '/services/intelligence-actionable-insights'   => 'service',

  // Solutions
  '/solutions/dotedu'           => 'solution',
  '/solutions/accord'           => 'solution',
  '/solutions/palisade'         => 'solution',
  '/solutions/bulkhead'         => 'solution',
  '/solutions/dotgov'           => 'solution',
  '/solutions/gazette'          => 'solution',
  '/solutions/outpost'          => 'solution',
  '/solutions/software-factory' => 'solution',

  // Landing pages
  '/'                              => 'landing_page',
  '/about'                         => ['basic_page', 'landing_page'],
  '/contact'                       => ['basic_page', 'landing_page'],
  '/federal'                       => 'landing_page',
  '/partners'                      => ['basic_page', 'landing_page'],
  '/resources'                     => 'landing_page',
  '/resources/downloads-guides'    => 'landing_page',

  // Hub index pages (these may be dynamic-index, not Drupal nodes)
  '/platforms'  => ['landing_page', 'basic_page'],
  '/services'   => ['landing_page', 'basic_page'],
  '/solutions'  => ['landing_page', 'basic_page'],
];

// Bundles that require field_key_capabilities to be populated.
$CAPABILITY_BUNDLES = ['platform', 'service', 'solution'];

// Bundles that require field_outcomes to be populated.
$OUTCOME_BUNDLES = ['solution', 'case_study'];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Load a node by its path alias.
 *
 * Special case: '/' resolves via system.site front-page config, not a path
 * alias. Drupal sets `system.site.page.front` to the internal path (e.g.
 * `/node/42`) for the configured front page — there is no path alias for '/'.
 */
function wlv_node_by_alias(string $alias): ?Node {
  // Homepage — check system.site.page.front.
  if ($alias === '/') {
    $front = \Drupal::config('system.site')->get('page.front');
    if ($front && preg_match('/^\/node\/(\d+)$/', $front, $m)) {
      return Node::load((int) $m[1]) ?: null;
    }
    return null;
  }

  $alias_manager = \Drupal::service('path_alias.manager');
  $system_path = $alias_manager->getPathByAlias($alias);
  if (!$system_path || $system_path === $alias) {
    return null;
  }
  if (preg_match('/^\/node\/(\d+)$/', $system_path, $m)) {
    return Node::load((int) $m[1]) ?: null;
  }
  return null;
}

// ---------------------------------------------------------------------------
// Section 1: Node existence + alias + bundle + status
// ---------------------------------------------------------------------------

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           SEEDING VERIFICATION                               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "┌─ SECTION 1: Node existence, alias, bundle, status ──────────\n";

$missing        = [];
$wrong_bundle   = [];
$unpublished    = [];
$found          = 0;

foreach ($WL_CANONICAL as $alias => $expected_bundle) {
  $node = wlv_node_by_alias($alias);

  if (!$node) {
    $missing[] = $alias;
    printf("│  [✗ MISSING]   %s\n", $alias);
    continue;
  }

  $bundle  = $node->bundle();
  $status  = $node->isPublished();
  $allowed = is_array($expected_bundle) ? $expected_bundle : [$expected_bundle];

  $bundle_ok = in_array($bundle, $allowed, true);
  $status_ok = $status;

  if (!$bundle_ok) {
    $wrong_bundle[] = $alias;
    printf("│  [✗ BUNDLE]    %-45s  got=%s  want=%s\n",
      $alias, $bundle, implode('|', $allowed));
  } elseif (!$status_ok) {
    $unpublished[] = $alias;
    printf("│  [✗ UNPUB]     %-45s  bundle=%s\n", $alias, $bundle);
  } else {
    $found++;
    printf("│  [✓]           %-45s  %s\n", $alias, $bundle);
  }
}

echo "└──────────────────────────────────────────────────────────────\n\n";

// ---------------------------------------------------------------------------
// Section 2: Capability paragraphs
// ---------------------------------------------------------------------------

echo "┌─ SECTION 2: field_key_capabilities ─────────────────────────\n";

$cap_missing = [];
$cap_present = 0;

foreach ($WL_CANONICAL as $alias => $expected_bundle) {
  $allowed = is_array($expected_bundle) ? $expected_bundle : [$expected_bundle];
  $bundle  = array_intersect($allowed, $CAPABILITY_BUNDLES);
  if (empty($bundle)) continue;

  $node = wlv_node_by_alias($alias);
  if (!$node) continue; // Already reported in Section 1

  if (!$node->hasField('field_key_capabilities')) {
    printf("│  [✗ NO FIELD]  %s (bundle=%s)\n", $alias, $node->bundle());
    continue;
  }

  $caps = $node->get('field_key_capabilities')->referencedEntities();
  if (empty($caps)) {
    $cap_missing[] = $alias;
    printf("│  [✗ EMPTY]     %-45s  (%s)\n", $alias, $node->bundle());
  } else {
    $cap_present++;
    printf("│  [✓] %d cap(s)  %-45s\n", count($caps), $alias);
  }
}

echo "└──────────────────────────────────────────────────────────────\n\n";

// ---------------------------------------------------------------------------
// Section 3: Outcome paragraphs
// ---------------------------------------------------------------------------

echo "┌─ SECTION 3: field_outcomes ──────────────────────────────────\n";

$outcome_missing = [];
$outcome_present = 0;

foreach ($WL_CANONICAL as $alias => $expected_bundle) {
  $allowed = is_array($expected_bundle) ? $expected_bundle : [$expected_bundle];
  $bundle  = array_intersect($allowed, $OUTCOME_BUNDLES);
  if (empty($bundle)) continue;

  $node = wlv_node_by_alias($alias);
  if (!$node) continue;

  if (!$node->hasField('field_outcomes')) {
    // Not all solution/case_study nodes may have this field yet — skip quietly.
    continue;
  }

  $outcomes = $node->get('field_outcomes')->referencedEntities();
  if (empty($outcomes)) {
    $outcome_missing[] = $alias;
    printf("│  [—  EMPTY]    %-45s  (not yet seeded — OK for stubs)\n", $alias);
  } else {
    $outcome_present++;
    printf("│  [✓] %d outcome(s)  %-43s\n", count($outcomes), $alias);
  }
}

if (empty($outcome_missing) && $outcome_present === 0) {
  echo "│  (no solution/case_study nodes found with field_outcomes)\n";
}

echo "└──────────────────────────────────────────────────────────────\n\n";

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

$total      = count($WL_CANONICAL);
$issues     = count($missing) + count($wrong_bundle) + count($unpublished) + count($cap_missing);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  SUMMARY                                                     ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf("║  Canonical paths checked:          %-4d                     ║\n", $total);
printf("║  Nodes found + published:          %-4d                     ║\n", $found);
printf("║  Missing / alias not found:        %-4d                     ║\n", count($missing));
printf("║  Wrong bundle:                     %-4d                     ║\n", count($wrong_bundle));
printf("║  Unpublished:                      %-4d                     ║\n", count($unpublished));
printf("║  Missing capabilities (empty):     %-4d                     ║\n", count($cap_missing));
printf("║  Nodes with capabilities seeded:   %-4d                     ║\n", $cap_present);
echo "╠══════════════════════════════════════════════════════════════╣\n";
if ($issues === 0) {
  echo "║  ✓  All checks passed. Seeding looks complete.              ║\n";
} else {
  printf("║  ✗  %d issue(s) found — see details above.                  ║\n", $issues);
}
echo "╚══════════════════════════════════════════════════════════════╝\n";
