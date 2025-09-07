<?php

/**
 * WL GraphQL taxonomy bootstrap:
 * - Ensures graphql + persisted queries modules
 * - Creates a custom module "wl_graphql_taxonomy" that adds computed fields:
 *     TaxonomyTerm.ancestors: [TaxonomyTerm]
 *     TaxonomyTerm.children:  [TaxonomyTerm]
 * - Creates two persisted queries: WL_TermPage and WL_MainNav
 *
 * Safe to re-run. Begin with DRY_RUN = true.
 */

use Drupal\image\Entity\ImageStyle;

const DRY_RUN = false; // <-- set to false to apply changes

$installer = \Drupal::service('module_installer');
$need = ['graphql', 'graphql_persisted_queries', 'taxonomy'];
$installer->install(array_values(array_filter($need, fn($m) => !\Drupal::moduleHandler()->moduleExists($m))));

/* ------------------------------------------------------------------------------------
 * 1) Write a tiny module that exposes ancestors/children on TaxonomyTerm via GraphQL 4
 * ------------------------------------------------------------------------------------ */
$fs = \Drupal::service('file_system');
$module_dir = DRUPAL_ROOT . '/modules/custom/wl_graphql_taxonomy';
if (!is_dir($module_dir)) {
  if (!DRY_RUN) { $fs->mkdir($module_dir, 0775, TRUE); }
}

$info_yml = <<<YML
name: WL GraphQL Taxonomy
type: module
description: Adds computed ancestors/children fields to taxonomy terms for GraphQL.
core_version_requirement: ^10 || ^11
package: WilkesLiberty
dependencies:
  - drupal:graphql
  - drupal:taxonomy
YML;

$module_php = <<<'PHP'
<?php
/**
 * Empty module file; plugins live under src/Plugin/GraphQL/Fields.
 */
PHP;

$src_dir = $module_dir . '/src/Plugin/GraphQL/Fields';
if (!is_dir($src_dir)) {
  if (!DRY_RUN) { $fs->mkdir($src_dir, 0775, TRUE); }
}

$ancestors_php = <<<'PHP'
<?php

namespace Drupal\wl_graphql_taxonomy\Plugin\GraphQL\Fields;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @GraphQLField(
 *   id = "wl_term_ancestors",
 *   name = "ancestors",
 *   type = "[TaxonomyTerm]",
 *   parents = {"TaxonomyTerm"}
 * )
 */
class TermAncestors extends FieldPluginBase {

  protected EntityTypeManagerInterface $etm;

  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $instance = parent::create($container, $configuration, $pluginId, $pluginDefinition);
    $instance->etm = $container->get('entity_type.manager');
    return $instance;
  }

  protected function resolve($value, array $args, ResolveContext $context, ResolveInfo $info) {
    // $value is a taxonomy term entity.
    if (!$value || $value->getEntityTypeId() !== 'taxonomy_term') {
      return NULL;
    }

    $storage = $this->etm->getStorage('taxonomy_term');
    $ancestors = [];
    $current = $value;
    // Walk up the tree; loadParents returns an array keyed by tid.
    while ($current) {
      $parents = $storage->loadParents($current->id());
      if (!$parents) {
        break;
      }
      $parent = reset($parents);
      $ancestors[] = $parent;
      $current = $parent;
    }
    // Oldest first (root) -> nearest parent last is often nicer for breadcrumbs.
    return array_reverse($ancestors);
  }
}
PHP;

$children_php = <<<'PHP'
<?php

namespace Drupal\wl_graphql_taxonomy\Plugin\GraphQL\Fields;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @GraphQLField(
 *   id = "wl_term_children",
 *   name = "children",
 *   type = "[TaxonomyTerm]",
 *   parents = {"TaxonomyTerm"}
 * )
 */
class TermChildren extends FieldPluginBase {

  protected EntityTypeManagerInterface $etm;

  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $instance = parent::create($container, $configuration, $pluginId, $pluginDefinition);
    $instance->etm = $container->get('entity_type.manager');
    return $instance;
  }

  protected function resolve($value, array $args, ResolveContext $context, ResolveInfo $info) {
    // $value is a taxonomy term entity.
    if (!$value || $value->getEntityTypeId() !== 'taxonomy_term') {
      return NULL;
    }
    $storage = $this->etm->getStorage('taxonomy_term');
    // One level of children, as full entities.
    // loadTree($vid, $parent = 0, $max_depth = NULL, $load_entities = FALSE)
    $children = $storage->loadTree($value->bundle(), $value->id(), 1, TRUE);
    /** @var \Drupal\taxonomy\TermInterface[] $children */
    return $children;
  }
}
PHP;

if (!DRY_RUN) {
  file_put_contents($module_dir . '/wl_graphql_taxonomy.info.yml', $info_yml);
  file_put_contents($module_dir . '/wl_graphql_taxonomy.module', $module_php);
  file_put_contents($src_dir . '/TermAncestors.php', $ancestors_php);
  file_put_contents($src_dir . '/TermChildren.php', $children_php);
}

if (!\Drupal::moduleHandler()->moduleExists('wl_graphql_taxonomy') && !DRY_RUN) {
  $installer->install(['wl_graphql_taxonomy']);
}

/* ------------------------------------------------------------------------------------
 * 2) Create two persisted queries (GraphQL 4.x persisted_queries submodule)
 * ------------------------------------------------------------------------------------ */

function wl_create_or_update_persisted_query(string $id, string $label, string $query, string $schema = 'default'): void {
  $storage = \Drupal::entityTypeManager()->getStorage('graphql_persisted_query');
  $existing = $storage->load($id);
  $data = [
    'id' => $id,
    'label' => $label,
    'query' => $query,
    'schema' => $schema,
  ];
  if ($existing) {
    if (!DRY_RUN) {
      $existing->set('label', $label);
      $existing->set('query', $query);
      $existing->set('schema', $schema);
      $existing->save();
    }
  } else {
    if (!DRY_RUN) {
      $storage->create($data)->save();
    }
  }
}

/**
 * NOTE:
 * These queries assume the standard GraphQL 4 core schema where:
 * - You can fetch by ID via `entityById(entityType: TAXONOMY_TERM, id: String!)`.
 * - Entities expose `entityUrl { path }` (provided by graphql core).
 * If your server uses Compose or a custom schema, tweak the root fields accordingly.
 */

// WL_TermPage: fetch a term by ID with ancestors/children + basic SEO fields.
$term_page_query = <<<'GQL'
query WL_TermPage($id: String!) {
  entityById(entityType: TAXONOMY_TERM, id: $id) {
    ... on TaxonomyTerm {
      entityId
      bundle
      name
      entityUrl { path }
      description
      fieldSeoTitle
      fieldMetaDescription
      ancestors {
        entityId
        name
        entityUrl { path }
      }
      children {
        entityId
        name
        entityUrl { path }
      }
    }
  }
}
GQL;

// WL_MainNav: top-level Capabilities & Industries flagged "Show in navigation?"
$main_nav_query = <<<'GQL'
query WL_MainNav {
  capabilities: taxonomyTermQuery(
    filter: {
      conditions: [
        { field: "vid", operator: EQUAL, value: ["capabilities"] }
        { field: "parent", operator: EQUAL, value: ["0"] }
        { field: "field_show_in_nav.value", operator: EQUAL, value: ["1"] }
      ]
    }
    sort: { field: "weight", direction: ASC }
  ) {
    entities {
      ... on TaxonomyTerm {
        entityId
        name
        entityUrl { path }
      }
    }
  }
  industries: taxonomyTermQuery(
    filter: {
      conditions: [
        { field: "vid", operator: EQUAL, value: ["industries"] }
        { field: "parent", operator: EQUAL, value: ["0"] }
        { field: "field_show_in_nav.value", operator: EQUAL, value: ["1"] }
      ]
    }
    sort: { field: "weight", direction: ASC }
  ) {
    entities {
      ... on TaxonomyTerm {
        entityId
        name
        entityUrl { path }
      }
    }
  }
}
GQL;

wl_create_or_update_persisted_query('wl_term_page', 'WL Term Page', $term_page_query, 'default');
wl_create_or_update_persisted_query('wl_main_nav', 'WL Main Nav', $main_nav_query, 'default');

/* ------------------------------------------------------------------------------------
 * 3) Cache rebuild so plugins & queries show up
 * ------------------------------------------------------------------------------------ */
if (!DRY_RUN) {
  \Drupal::service('router.builder')->rebuild();
  \Drupal::service('cache.bootstrap')->invalidateAll();
  \Drupal::service('cache.render')->invalidateAll();
  \Drupal::messenger()->addStatus('WL GraphQL taxonomy fields and persisted queries installed.');
}

print DRY_RUN
  ? "\n[DRY RUN] Created/updated files in memory. Set DRY_RUN=false and re-run to apply.\n"
  : "\n[APPLIED] wl_graphql_taxonomy enabled, fields available, persisted queries created.\n";
