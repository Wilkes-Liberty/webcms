<?php

use Drupal\taxonomy\Entity\Vocabulary;

$vocabs = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

$by_label = [];
$summary = [];

foreach ($vocabs as $vid => $vocab) {
  $label = $vocab->label();
  $by_label[$label][] = $vid;

  $tids = \Drupal::entityQuery('taxonomy_term')->condition('vid', $vid)->accessCheck(FALSE)->execute();
  $count = $tids ? count($tids) : 0;

  $dupes = [];
  if ($tids) {
    $terms = $term_storage->loadMultiple($tids);
    $seen = [];
    foreach ($terms as $t) {
      $name = $t->getName();
      $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
      $seen[$norm][] = $t->id();
    }
    foreach ($seen as $norm => $ids) {
      if (count($ids) > 1) {
        $dupes[$norm] = $ids;
      }
    }
  }

  $summary[$vid] = [
    'label' => $label,
    'term_count' => $count,
    'dup_sets' => $dupes,
  ];
}

print "Vocabularies:\n";
foreach ($summary as $vid => $info) {
  $dup_count = count($info['dup_sets']);
  print sprintf("- %s (%s): %d terms%s\n", $vid, $info['label'], $info['term_count'], $dup_count ? " – duplicate names: $dup_count" : "");
}

$dupe_labels = array_filter($by_label, fn($vids) => count($vids) > 1);
if ($dupe_labels) {
  print "\nDuplicate vocabulary labels (same label, multiple machine names):\n";
  foreach ($dupe_labels as $label => $vids) {
    print "  - $label: ".implode(', ', $vids)."\n";
  }
}

$printed = 0;
print "\nSample duplicate term groups (first 100):\n";
foreach ($summary as $vid => $info) {
  foreach ($info['dup_sets'] as $norm => $ids) {
    $printed++;
    print "  [$vid] \"$norm\" => tids ".implode(', ', $ids)."\n";
    if ($printed >= 100) { break 2; }
  }
}

print "\nTip: After review, we can merge duplicates within each vocabulary by keeping the lowest TID, remapping references site-wide, and deleting the rest.\n";

