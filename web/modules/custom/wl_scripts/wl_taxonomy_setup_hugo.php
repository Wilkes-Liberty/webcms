<php
/**
* Idempotent taxonomy setup script for Wilkes & Liberty.
*
* How to run:
*   drush scr scripts/taxonomy_setup.php
*
* What this does:
* - Creates missing vocabularies that mirror the Hugo prototype IA:
*   sections, technologies, solutions, services, industries, capabilities, categories
*   (skips 'tags' because the site already has it)
* - Creates/updates terms and parent/child hierarchies with weights matching the Hugo menus
* - Safe to run multiple times (idempotent)
*
* Notes:
* - Vocabularies are configuration; export with `drush cex -y` if you manage config in git.
* - Terms are content (stored in the DB). If you need to ship defaults, consider Default Content or Features.
*/

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
* Ensure a vocabulary exists, creating it if necessary.
*/
function wl_ensure_vocabulary(string $vid, string $label, string $description = ''): Vocabulary {
$vocab = Vocabulary::load($vid);
if (!$vocab) {
$vocab = Vocabulary::create([
'vid' => $vid,
'name' => $label,
'description' => $description,
]);
$vocab->save();
print "[+] Created vocabulary: {$vid} ({$label})\n";
}
else {
$changed = false;
if ($vocab->label() !== $label) {
$vocab->set('name', $label);
$changed = true;
}
if ((string) $vocab->get('description') !== (string) $description) {
$vocab->set('description', $description);
$changed = true;
}
if ($changed) {
$vocab->save();
print "[~] Updated vocabulary: {$vid}\n";
}
else {
print "[=] Vocabulary exists: {$vid}\n";
}
}
return $vocab;
}

/**
* Find a term by name within a vocabulary. Returns the first match.
*/
function wl_find_term(string $vid, string $name): ?Term {
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$ids = $storage->getQuery()
->condition('vid', $vid)
->condition('name', $name)
->accessCheck(TRUE)
->range(0, 1)
->execute();
if (!$ids) {
return NULL;
}
$entities = $storage->loadMultiple($ids);
return $entities ? reset($entities) : NULL;
}

/**
* Ensure a term exists in a vocabulary with optional parent and weight.
* Returns the term entity.
*/
function wl_ensure_term(string $vid, string $name, ?int $parent_tid = NULL, ?int $weight = NULL): Term {
$term = wl_find_term($vid, $name);
$created = false;

if (!$term) {
$values = [
'vid' => $vid,
'name' => $name,
];
if ($parent_tid) {
$values['parent'] = [$parent_tid];
}
if ($weight !== NULL) {
$values['weight'] = $weight;
}
$term = Term::create($values);
$term->save();
$created = true;
$msg = "[+] Created term: {$name} (vid: {$vid})";
if ($parent_tid) { $msg .= " parent: {$parent_tid}"; }
if ($weight !== NULL) { $msg .= " weight: {$weight}"; }
print $msg . "\n";
}
else {
$changed = false;

// Ensure parent relationship.
if ($parent_tid) {
$existing_parents = $term->get('parent')->getValue();
$existing_parent_ids = array_map(static fn($v) => (int) ($v['target_id'] ?? 0), $existing_parents);
if (!in_array((int) $parent_tid, $existing_parent_ids, true)) {
$term->set('parent', [$parent_tid]);
$changed = true;
}
}

// Ensure weight.
if ($weight !== NULL && (int) $term->getWeight() !== (int) $weight) {
$term->setWeight((int) $weight);
$changed = true;
}

if ($changed) {
$term->save();
print "[~] Updated term: {$name} (vid: {$vid})\n";
}
else {
print "[=] Term exists: {$name} (vid: {$vid})\n";
}
}

return $term;
}

/**
* Create all vocabularies and terms based on the Hugo IA.
*/
function wl_setup_taxonomy(): void {
// Define vocabularies and term structures (including hierarchies and weights).
$map = [
'sections' => [
'label' => 'Sections',
'description' => 'Top-level site sections as per the Hugo prototype.',
'terms' => [
['name' => 'Capabilities', 'weight' => 1],
['name' => 'Technology',  'weight' => 2],
['name' => 'Solutions',   'weight' => 3],
['name' => 'Services',    'weight' => 4],
['name' => 'Industries',  'weight' => 5],
['name' => 'About',       'weight' => 6],
],
],

'technologies' => [
'label' => 'Technologies',
'description' => 'Technology topics and sub-technologies.',
'terms' => [
['name' => 'Artificial Intelligence', 'weight' => 21],
[
'name' => 'Blockchain',
'weight' => 22,
'children' => [
['name' => 'XRP Ledger', 'weight' => 221],
],
],
[
'name' => 'Content Management',
'weight' => 23,
'children' => [
['name' => 'Drupal', 'weight' => 231],
],
],
['name' => 'Digital Identity', 'weight' => 24],
],
],

'solutions' => [
'label' => 'Solutions',
'description' => 'Reusable solution offerings.',
'terms' => [
['name' => 'Digital Health ID',     'weight' => 31],
['name' => 'Digital Modernization', 'weight' => 32],
['name' => 'Financial Optimization','weight' => 33],
['name' => 'Private LLMs',          'weight' => 34],
['name' => 'Product AI',            'weight' => 35],
['name' => 'Zero Trust',            'weight' => 36],
],
],

'services' => [
'label' => 'Services',
'description' => 'Professional services offerings.',
'terms' => [
['name' => 'AI Specialist Support', 'weight' => 41],
['name' => 'Cloud Operations',      'weight' => 42],
['name' => 'Cybersecurity',         'weight' => 43],
['name' => 'Software Development',  'weight' => 44],
],
],

'industries' => [
'label' => 'Industries',
'description' => 'Industry verticals.',
'terms' => [
['name' => 'Aerospace & Defense',     'weight' => 51],
['name' => 'Civil Engineering',       'weight' => 52],
['name' => 'Communications & Media',  'weight' => 53],
['name' => 'Education',               'weight' => 54],
['name' => 'Emerging Markets',        'weight' => 55],
['name' => 'Finance',                 'weight' => 56],
['name' => 'Government',              'weight' => 57],
['name' => 'Health',                  'weight' => 58],
['name' => 'Legal',                   'weight' => 59],
['name' => 'Travel',                  'weight' => 60],
],
],

'capabilities' => [
'label' => 'Capabilities',
'description' => 'Cross-cutting capabilities referenced across tech/services/solutions.',
'terms' => [
['name' => 'Artificial Intelligence', 'weight' => 10],
['name' => 'Blockchain',              'weight' => 20],
['name' => 'Cloud Operations',        'weight' => 30],
['name' => 'Content Management',      'weight' => 40],
['name' => 'Cybersecurity',           'weight' => 50],
['name' => 'Digital Identity',        'weight' => 60],
['name' => 'Digital Modernization',   'weight' => 70],
['name' => 'Infrastructure',          'weight' => 80],
['name' => 'Software Development',    'weight' => 90],
],
],

'categories' => [
'label' => 'Categories',
'description' => 'Editorial and informational categories used across the site.',
'terms' => [
['name' => 'Digital Innovation'],
['name' => 'Privacy Technology'],
['name' => 'Enterprise Solutions'],
['name' => 'Technology Solutions'],
['name' => 'Enterprise Infrastructure'],
['name' => 'Professional Services'],
['name' => 'Enterprise Consulting'],
['name' => 'News'],
['name' => 'Digital Liberation'],
['name' => 'Updates'],
],
],
];

// Create vocabularies (skip 'tags' as it already exists in this site).
foreach ($map as $vid => $info) {
wl_ensure_vocabulary($vid, $info['label'], $info['description'] ?? '');
}

// Create terms with hierarchy where defined.
foreach ($map as $vid => $info) {
if (empty($info['terms'])) {
continue;
}

foreach ($info['terms'] as $term_def) {
$parent_tid = NULL;
$term = wl_ensure_term(
$vid,
$term_def['name'],
parent_tid: NULL,
weight: $term_def['weight'] ?? NULL,
);
$parent_tid = (int) $term->id();

if (!empty($term_def['children']) && is_array($term_def['children'])) {
foreach ($term_def['children'] as $child_def) {
wl_ensure_term(
$vid,
$child_def['name'],
parent_tid: $parent_tid,
weight: $child_def['weight'] ?? NULL,
);
}
}
}
}

print "\nDone. Review newly created vocabularies and terms at /admin/structure/taxonomy.\n";
}

wl_setup_taxonomy();
