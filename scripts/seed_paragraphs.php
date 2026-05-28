<?php
/**
 * Seed capability paragraph entities onto Platform, Service, and Solution nodes.
 *
 * For every canonical platform, service, and solution node (matched by alias),
 * this script creates `capability` paragraph entities and attaches them to the
 * node's `field_key_capabilities` field.
 *
 * Capability data is sourced from CONTENT.md and represents the authoritative
 * list of key capabilities per page.
 *
 * Idempotent — safe to re-run.
 *   (default)   skip nodes that already have field_key_capabilities populated.
 *   --force     delete existing paragraphs and re-seed from this script.
 *   --dry-run   report what would happen without writing to the DB.
 *
 * Run:
 *   ddev drush scr scripts/seed_paragraphs.php
 *   ddev drush scr scripts/seed_paragraphs.php -- --dry-run
 *   ddev drush scr scripts/seed_paragraphs.php -- --force
 *   ddev drush scr scripts/seed_paragraphs.php -- --dry-run --force
 *
 * On production:
 *   docker compose exec drupal drush scr /var/www/html/scripts/seed_paragraphs.php -- --force
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

// ---------------------------------------------------------------------------
// CLI flags
// ---------------------------------------------------------------------------

$wl_argv   = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$WL_DRY    = in_array('--dry-run', $wl_argv, true);
$WL_FORCE  = in_array('--force', $wl_argv, true);

// ---------------------------------------------------------------------------
// Capability data
// Source: webcms/docs/CONTENT.md (Key Capabilities sections)
// Structure: alias => [ ['title' => '...', 'benefit' => '...'], ... ]
// ---------------------------------------------------------------------------

$WL_CAPABILITIES = [

  // ── Platforms ──────────────────────────────────────────────────────────────

  '/platforms/sabal' => [
    ['title' => 'Automated deployment and configuration management',          'benefit' => 'Reduces manual effort and eliminates configuration drift across environments'],
    ['title' => 'Secure network segmentation and zero-trust architecture',    'benefit' => 'Limits lateral movement and enforces least-privilege at the network layer'],
    ['title' => 'Encrypted backup systems with long-term retention',          'benefit' => 'Ensures data is recoverable and audit-compliant without exposure risk'],
    ['title' => 'Comprehensive monitoring and alerting',                      'benefit' => 'Provides continuous visibility into infrastructure health and mission readiness'],
    ['title' => 'Flexible deployment across any environment',                 'benefit' => 'Supports on-premises, private cloud, and hybrid architectures without lock-in'],
  ],

  '/platforms/keel' => [
    ['title' => 'Advanced content modeling and workflow management',          'benefit' => 'Empowers editorial teams to manage complex content structures without developer involvement'],
    ['title' => 'Secure headless architecture for maximum flexibility',       'benefit' => 'Separates the content layer from delivery so teams can publish to any channel or application'],
    ['title' => 'Enterprise media management and multilingual support',       'benefit' => 'Handles large media libraries and multilingual content within a single governed system'],
    ['title' => 'Structured search and personalization integration',          'benefit' => 'Connects cleanly with Alidade Search Platform for federated, relevance-tuned content discovery'],
    ['title' => 'Expert development and customization',                       'benefit' => 'Extends the platform to meet agency-specific requirements without compromising core stability'],
  ],

  '/platforms/alidade' => [
    ['title' => 'Intelligent full-text and faceted search',                   'benefit' => 'Allows users to find mission-critical information through natural-language and structured queries'],
    ['title' => 'Multilingual and secure search indexing',                    'benefit' => 'Indexes content across languages while enforcing access controls at the document level'],
    ['title' => 'Real-time synchronization and relevance tuning',             'benefit' => 'Keeps search results current and ranked to reflect operational priorities'],
    ['title' => 'Integration with existing content systems',                  'benefit' => 'Connects to Keel CMS Platform and external repositories through a standard indexing pipeline'],
  ],

  '/platforms/squawk' => [
    ['title' => 'Centralized identity and access management',                 'benefit' => 'Provides a single authoritative source for user identities across all connected systems'],
    ['title' => 'Zero-trust security principles',                             'benefit' => 'Enforces continuous verification — no implicit trust based on network location or prior session'],
    ['title' => 'Role-based access controls and auditing',                    'benefit' => 'Ensures users have only the permissions their role requires, with full audit trail'],
    ['title' => 'Secure integration with VPN and mesh networking',            'benefit' => 'Works alongside Tailscale and Sabal-based mesh networks for end-to-end access governance'],
  ],

  '/platforms/manifest' => [
    ['title' => 'Enterprise database and caching systems',                    'benefit' => 'Delivers high-throughput data access with consistent performance for mission-critical workloads'],
    ['title' => 'Automated backup and recovery processes',                    'benefit' => 'Reduces data loss exposure and recovery time to meet continuity requirements'],
    ['title' => 'Performance optimization for mission workloads',             'benefit' => 'Tunes query execution, caching, and replication for operational tempo requirements'],
    ['title' => 'Full data sovereignty and encryption',                       'benefit' => 'Keeps all data within the authorization boundary with encryption at rest and in transit'],
  ],

  '/platforms/lighthouse' => [
    ['title' => 'Real-time metrics collection and visualization',             'benefit' => 'Surfaces infrastructure and application health at a glance for rapid situational awareness'],
    ['title' => 'Proactive alerting and incident response',                   'benefit' => 'Notifies on-call teams of anomalies before they reach mission impact'],
    ['title' => 'Detailed performance and availability tracking',             'benefit' => 'Provides historical trends and SLO visibility across the entire platform stack'],
    ['title' => 'Custom dashboards for mission-specific KPIs',                'benefit' => 'Allows operators and program managers to monitor the metrics that matter to their mission'],
  ],

  '/platforms/coquina' => [
    ['title' => 'Secure CI/CD pipeline automation with environment isolation', 'benefit' => 'Delivers software through automated gates without relying on external SaaS CI/CD services'],
    ['title' => 'Container registry with integrated image scanning',           'benefit' => 'Detects vulnerabilities before any artifact reaches deployment — not after'],
    ['title' => 'Infrastructure-as-Code integration with automated testing',  'benefit' => 'Validates every infrastructure change before it is promoted to the next environment'],
    ['title' => 'Air-gapped software delivery for classified environments',    'benefit' => 'Operates fully inside an authorization boundary with no external network dependency'],
    ['title' => 'SAST/DAST and dependency auditing at every pipeline stage',  'benefit' => 'Enforces security policy as code, not as a post-deployment checklist'],
    ['title' => 'Artifact management with full chain-of-custody records',     'benefit' => 'Provides the provenance documentation required for compliance and audit review'],
  ],

  // ── Services ───────────────────────────────────────────────────────────────

  '/services/private-infrastructure-engineering' => [
    ['title' => 'Infrastructure-as-Code design and automated deployment',     'benefit' => 'Eliminates manual provisioning and ensures environments are reproducible and auditable'],
    ['title' => 'Secure network architecture and segmentation',               'benefit' => 'Enforces least-privilege at the network layer from initial build, not as a retrofit'],
    ['title' => 'Sovereign environment design with full operational control', 'benefit' => 'Eliminates dependency on hyperscaler SLAs and gives the organization direct control over its infrastructure'],
    ['title' => '24/7 managed operations and incident response',              'benefit' => 'Frees internal teams to focus on mission while WL maintains infrastructure health around the clock'],
    ['title' => 'Compliance-aligned configuration management',                'benefit' => 'Maintains documentation and configuration evidence for FedRAMP, FISMA, and agency ATO processes'],
  ],

  '/services/zero-trust-identity-consulting' => [
    ['title' => 'Zero-trust architecture design and roadmap',                 'benefit' => 'Produces an enforceable access model that does not rely on implicit network trust'],
    ['title' => 'Centralized identity provider integration',                  'benefit' => 'Consolidates identity across systems using SAML, OIDC, and Tailscale-based mesh access patterns'],
    ['title' => 'Role-based access controls and least-privilege enforcement', 'benefit' => 'Limits blast radius of credential compromise by ensuring no user has more access than required'],
    ['title' => 'Audit logging and compliance evidence generation',           'benefit' => 'Produces the access records required for IG review, ATO, and continuous monitoring programs'],
    ['title' => 'Integration with existing systems and VPN replacements',     'benefit' => 'Transitions organizations from perimeter-based VPN to policy-enforced zero-trust access without disruption'],
  ],

  '/services/defense-technology-integration' => [
    ['title' => 'Aviation, drone, and unmanned systems integration',          'benefit' => 'Connects air and ground platforms into a unified operational picture'],
    ['title' => 'Sensor fusion and multi-source data aggregation',            'benefit' => 'Combines data streams from diverse sensors into coherent, actionable intelligence'],
    ['title' => 'Command-and-control platform engineering',                   'benefit' => 'Delivers reliable C2 infrastructure for time-sensitive, operationally critical decisions'],
    ['title' => 'Secure data links and communications architecture',          'benefit' => 'Maintains secure, low-latency connections between deployed systems and command infrastructure'],
    ['title' => 'Autonomous systems integration and testing',                 'benefit' => 'Validates autonomous behavior against mission requirements before operational deployment'],
  ],

  '/services/headless-cms-implementation' => [
    ['title' => 'Headless Drupal architecture design and implementation',     'benefit' => 'Decouples the editorial backend from presentation so content can reach any channel or application'],
    ['title' => 'JSON:API and GraphQL content delivery configuration',        'benefit' => 'Exposes content through standard APIs that frontend applications and integrations consume directly'],
    ['title' => 'Content modeling aligned to organizational workflows',       'benefit' => 'Structures content to match how editorial teams actually work, reducing friction and errors'],
    ['title' => 'USWDS and Section 508 compliance engineering',               'benefit' => 'Builds federal accessibility and design system requirements into the platform foundation'],
    ['title' => 'Ongoing development and platform evolution',                 'benefit' => 'Extends and adapts the CMS as agency requirements evolve without disrupting existing editorial workflows'],
  ],

  '/services/enterprise-search-architecture' => [
    ['title' => 'Solr and Elasticsearch architecture and deployment',         'benefit' => 'Delivers a scalable, sovereign search backend under full organizational control'],
    ['title' => 'Index design, schema configuration, and relevance tuning',  'benefit' => 'Produces search results that reflect operational priorities, not just keyword frequency'],
    ['title' => 'Federated search across multiple content sources',           'benefit' => 'Unifies search across repositories, sites, and applications through a single query interface'],
    ['title' => 'Secure, access-controlled indexing',                        'benefit' => 'Enforces document-level access controls so users only retrieve content they are authorized to see'],
    ['title' => 'Search analytics and continuous optimization',               'benefit' => 'Uses query data to improve relevance and surface gaps in coverage over time'],
  ],

  '/services/ai-integration' => [
    ['title' => 'AI capability assessment and integration roadmap',           'benefit' => 'Identifies the highest-value AI integration points and sequences implementation for maximum mission impact'],
    ['title' => 'Sovereign AI deployment in private environments',            'benefit' => 'Keeps models and data within the authorization boundary — no external API dependency'],
    ['title' => 'Custom model development and fine-tuning',                   'benefit' => 'Produces models tailored to your data and use cases, not general-purpose commercial approximations'],
    ['title' => 'AI governance frameworks and compliance documentation',      'benefit' => 'Satisfies OMB AI policy, ethical use requirements, and program office oversight obligations'],
    ['title' => 'Workflow automation and predictive analytics integration',   'benefit' => 'Eliminates manual handoffs and surfaces predictive signals that improve decision speed and accuracy'],
  ],

  '/services/digital-modernization' => [
    ['title' => 'Legacy system assessment and modernization roadmap',         'benefit' => 'Identifies technical debt, risk, and the sequenced path to modern architecture with minimal disruption'],
    ['title' => 'Secure data migration and platform transition',              'benefit' => 'Moves data from legacy systems to modern platforms without loss, corruption, or compliance exposure'],
    ['title' => 'Process automation and workflow re-engineering',             'benefit' => 'Eliminates manual steps and brittle hand-offs that slow operational tempo'],
    ['title' => 'Phased implementation with operational continuity',          'benefit' => 'Modernizes in stages so current operations continue uninterrupted during transition'],
    ['title' => 'Technical debt reduction and maintainability improvement',   'benefit' => 'Reduces the ongoing cost of maintaining outdated systems and the risk of unplanned outages'],
  ],

  '/services/custom-software-development' => [
    ['title' => 'Mission-specific application design and development',        'benefit' => 'Produces software built around your operational constraints, not commercial assumptions'],
    ['title' => 'Security-by-design architecture and code review',            'benefit' => 'Embeds security requirements from the first line of code rather than retrofitting controls after launch'],
    ['title' => 'Scalable, maintainable codebase built to government standards', 'benefit' => 'Delivers software that agency IT teams can sustain and audit without outside help'],
    ['title' => 'Full-stack development across web, API, and backend layers', 'benefit' => 'Covers the entire application stack under a single engagement with consistent design discipline'],
    ['title' => 'CI/CD-integrated delivery pipelines with branch-based promotion', 'benefit' => 'Automates testing and promotion so new capabilities ship with quality gates at every stage'],
    ['title' => 'Documentation and knowledge transfer for in-house sustainment', 'benefit' => 'Leaves your team fully equipped to operate, maintain, and extend the application independently'],
  ],

  '/services/integration-engineering' => [
    ['title' => 'Legacy system integration with modern APIs and microservices', 'benefit' => 'Connects decades-old platforms to current systems without requiring full replacement'],
    ['title' => 'Secure API gateway design, implementation, and management',  'benefit' => 'Creates a controlled, auditable entry point for all system-to-system communication'],
    ['title' => 'ETL pipeline and data transformation engineering',           'benefit' => 'Moves data between systems reliably, with transformation logic that enforces consistency and quality'],
    ['title' => 'Event-driven integration patterns',                          'benefit' => 'Replaces brittle point-to-point connections with decoupled, resilient message-based architectures'],
    ['title' => 'Protocol translation and middleware development',            'benefit' => 'Bridges incompatible systems without requiring changes to either endpoint'],
    ['title' => 'Integration testing, monitoring, and failure-mode documentation', 'benefit' => 'Ensures integrations remain reliable under load and that failures are visible and recoverable'],
  ],

  '/services/digital-asset-solutions' => [
    ['title' => 'Digital asset strategy and platform design',                 'benefit' => 'Establishes a governed architecture for managing digital assets within the authorization boundary'],
    ['title' => 'Secure blockchain and distributed ledger implementation',    'benefit' => 'Provides an immutable, auditable record layer for asset transactions and provenance'],
    ['title' => 'Cross-border payment and settlement infrastructure',         'benefit' => 'Enables faster, more transparent financial operations with full compliance documentation'],
    ['title' => 'Regulatory compliance alignment for digital assets',         'benefit' => 'Maps implementation to applicable financial services and government compliance requirements'],
  ],

  '/services/intelligence-actionable-insights' => [
    ['title' => 'Multi-source data fusion and intelligence synthesis',        'benefit' => 'Aggregates signals from diverse sources into coherent, prioritized intelligence products'],
    ['title' => 'Advanced analytics and pattern recognition',                 'benefit' => 'Identifies trends and anomalies that are not visible in individual data streams'],
    ['title' => 'Secure processing of sensitive and classified data',         'benefit' => 'Maintains need-to-know access controls and handling requirements throughout the analytics pipeline'],
    ['title' => 'Intelligence product delivery and dissemination',            'benefit' => 'Gets finished intelligence to decision-makers in the format and cadence they need'],
    ['title' => 'Continuous monitoring and threat anticipation frameworks',   'benefit' => 'Shifts the organization from reactive incident response to proactive threat awareness'],
  ],

  // ── Solutions ──────────────────────────────────────────────────────────────

  '/solutions/dotedu' => [
    ['title' => 'Preconfigured Drupal distribution aligned to higher education content patterns', 'benefit' => 'Accelerates deployment by starting from a tested foundation built for academic governance models'],
    ['title' => 'Multi-site management with centralized governance',          'benefit' => 'Allows colleges and departments to maintain editorial autonomy within institution-wide policy controls'],
    ['title' => 'Federated search across colleges, departments, and affiliated sites', 'benefit' => 'Surfaces relevant content across the institution through a single, relevance-tuned search interface'],
    ['title' => 'Section 508 and WCAG 2.1 AA compliance built in',           'benefit' => 'Satisfies federal accessibility requirements from the foundation — not as a remediation effort'],
  ],

  '/solutions/accord' => [
    ['title' => 'Flexible content modeling suited to distributed editorial teams', 'benefit' => 'Empowers small teams to manage complex program and communications content without developer involvement'],
    ['title' => 'Sovereign data hosting with full organizational control',    'benefit' => 'Eliminates shared-tenancy exposure — content and donor data remain within the organization\'s infrastructure'],
    ['title' => 'Scalable architecture without per-seat or per-feature pricing', 'benefit' => 'Grows with the organization without cost structure that penalizes success or expansion'],
    ['title' => 'Accessible, standards-compliant publishing',                 'benefit' => 'Meets WCAG 2.1 AA requirements for public-facing program and donor communications'],
  ],

  '/solutions/palisade' => [
    ['title' => 'Sovereign data infrastructure with tenant isolation and encryption', 'benefit' => 'Eliminates shared-tenancy exposure for organizations handling sensitive user or health data'],
    ['title' => 'Zero-trust identity and access controls with full audit logging', 'benefit' => 'Produces the compliance evidence required for SOC 2, HIPAA, and enterprise security reviews'],
    ['title' => 'Data residency controls aligned to HIPAA, SOC 2, and applicable privacy frameworks', 'benefit' => 'Keeps data in the jurisdiction and under the governance model the compliance program requires'],
    ['title' => 'Access governance for enterprise security reviews',          'benefit' => 'Enables founders and CTOs to demonstrate control to enterprise customers and investors'],
  ],

  '/solutions/bulkhead' => [
    ['title' => 'Sovereign infrastructure with full audit trails and configuration management', 'benefit' => 'Produces compliance evidence continuously — not as a fire drill before an audit'],
    ['title' => 'Zero-trust identity and access controls for regulated frameworks', 'benefit' => 'Aligns access governance to financial services, healthcare, and energy regulatory requirements'],
    ['title' => 'Encrypted, sovereign data management with retention policies', 'benefit' => 'Maintains data within the compliance boundary with policy-enforced retention and access governance'],
    ['title' => 'Phased modernization preserving operational continuity',     'benefit' => 'Moves regulated organizations off legacy exposure without disrupting current operations'],
  ],

  '/solutions/dotgov' => [
    ['title' => 'Preconfigured Drupal distribution aligned to USWDS and Section 508', 'benefit' => 'Satisfies federal accessibility and design system requirements from initial deployment'],
    ['title' => 'FedRAMP-compatible infrastructure deployment path',          'benefit' => 'Provides a documented path to agency ATO with full data sovereignty within the agency boundary'],
    ['title' => 'Federated search with classification-aware indexing and access controls', 'benefit' => 'Returns only the content the requesting user is authorized to see, at any classification level'],
    ['title' => 'Zero-trust identity integration for staff portals and intranets', 'benefit' => 'Protects authenticated content from unauthorized access without a perimeter-only security posture'],
    ['title' => 'Documentation for sustainment by agency IT staff',          'benefit' => 'Enables in-house teams to operate and evolve the platform without ongoing vendor dependency'],
  ],

  '/solutions/gazette' => [
    ['title' => 'Sovereign publishing platform for findings, reports, and disclosures', 'benefit' => 'Provides a dedicated, isolated environment for public-facing oversight content'],
    ['title' => 'Secure data management for sensitive reporting submissions', 'benefit' => 'Maintains strict separation between public content and restricted investigative records'],
    ['title' => 'Access controls and audit logging for federal oversight compliance', 'benefit' => 'Satisfies IG-specific compliance requirements and produces evidence for external review'],
    ['title' => 'Data segregation architecture separating public and restricted systems', 'benefit' => 'Enforces clear boundaries between what the public sees and what investigators work with'],
  ],

  '/solutions/outpost' => [
    ['title' => 'Sovereign infrastructure modernization in classified and disconnected environments', 'benefit' => 'Replaces fragile legacy environments with automated, repeatable deployment that works air-gapped'],
    ['title' => 'CI/CD and DevSecOps pipelines for classified environments',  'benefit' => 'Delivers software through automated security gates inside the authorization boundary — no external SaaS'],
    ['title' => 'Automated security scanning and compliance gate enforcement', 'benefit' => 'Makes security policy a first-class requirement at every pipeline stage, not a deployment afterthought'],
    ['title' => 'Phased modernization maintaining operational continuity',    'benefit' => 'Transitions from legacy systems without disrupting active mission operations'],
  ],

  '/solutions/software-factory' => [
    ['title' => 'Complete CI/CD pipeline deployable in air-gapped environments', 'benefit' => 'Delivers fully automated software manufacturing inside the authorization boundary with no external dependency'],
    ['title' => 'Integrated SAST/DAST, container scanning, and dependency auditing', 'benefit' => 'Enforces security at every pipeline stage — not as a final checkpoint but as a continuous gate'],
    ['title' => 'Artifact management with full provenance and chain-of-custody', 'benefit' => 'Produces the documentation required for program office and IG compliance review'],
    ['title' => 'Governance framework and process documentation',             'benefit' => 'Gives program managers a defensible, auditable delivery process that satisfies acquisition oversight requirements'],
  ],

];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Find the most recent revision ID of a paragraph on a given node + field.
 * Returns false if the field is not populated.
 */
function wlp_node_has_capabilities(Node $node): bool {
  return !$node->get('field_key_capabilities')->isEmpty();
}

/**
 * Find a node by its path alias.
 * Returns null if not found.
 */
function wlp_find_node_by_alias(string $alias): ?Node {
  $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
  $aliases = $storage->loadByProperties(['alias' => $alias, 'langcode' => 'en']);
  if (!$aliases) {
    return null;
  }
  $pa   = reset($aliases);
  $path = $pa->getPath();  // '/node/NID'
  if (!preg_match('|^/node/(\d+)$|', $path, $m)) {
    return null;
  }
  return Node::load((int) $m[1]);
}

/**
 * Create a single capability paragraph entity (not yet attached to a node).
 */
function wlp_make_paragraph(string $title, string $benefit): Paragraph {
  $para = Paragraph::create([
    'type'                         => 'capability',
    'field_capability_title'       => $title,
    'field_mission_benefit'        => $benefit,
    'field_capability_description' => $title . '. ' . $benefit . '.',
  ]);
  $para->save();
  return $para;
}

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------

$mode_label = $WL_DRY
  ? ($WL_FORCE ? 'DRY-RUN + FORCE (no DB writes)' : 'DRY-RUN (no DB writes)')
  : ($WL_FORCE ? 'FORCE (existing capabilities will be replaced)' : 'SKIP-IF-EXISTS (default)');

echo "=== Seeding capability paragraphs ===\n";
echo "Mode: {$mode_label}\n\n";

$counts = ['skipped' => 0, 'seeded' => 0, 'missing' => 0, 'would-seed' => 0, 'would-skip' => 0];

foreach ($WL_CAPABILITIES as $alias => $capabilities) {
  $node = wlp_find_node_by_alias($alias);

  if (!$node) {
    printf("  [!] %-50s  node NOT FOUND — skipping\n", $alias);
    $counts['missing']++;
    continue;
  }

  $bundle = $node->bundle();
  $title  = mb_substr($node->getTitle(), 0, 45);
  $count  = count($capabilities);

  if (wlp_node_has_capabilities($node) && !$WL_FORCE) {
    if ($WL_DRY) {
      printf("  [=?] %-50s  (%s) — WOULD SKIP (already has capabilities)\n", $alias, $bundle);
      $counts['would-skip']++;
    } else {
      printf("  [=]  %-50s  (%s) — skipped (already has capabilities)\n", $alias, $bundle);
      $counts['skipped']++;
    }
    continue;
  }

  if ($WL_DRY) {
    printf("  [+?] %-50s  (%s) — WOULD SEED %d capability paragraph(s)\n", $alias, $bundle, $count);
    $counts['would-seed']++;
    continue;
  }

  // Remove existing paragraphs if --force.
  if ($WL_FORCE && wlp_node_has_capabilities($node)) {
    foreach ($node->get('field_key_capabilities') as $item) {
      $existing_para = $item->entity;
      if ($existing_para) {
        $existing_para->delete();
      }
    }
    $node->set('field_key_capabilities', []);
  }

  // Create and attach new paragraphs.
  $para_values = [];
  foreach ($capabilities as $cap) {
    $para = wlp_make_paragraph($cap['title'], $cap['benefit']);
    $para_values[] = ['target_id' => $para->id(), 'target_revision_id' => $para->getRevisionId()];
  }

  $node->set('field_key_capabilities', $para_values);
  $node->setNewRevision(false);
  $node->save();

  printf("  [+]  %-50s  (%s) — seeded %d capability paragraph(s)\n", $alias, $bundle, $count);
  $counts['seeded']++;
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "\n=== Summary ===\n";
$parts = [];
foreach ($counts as $k => $v) {
  if ($v > 0) {
    $parts[] = "{$k}={$v}";
  }
}
echo '  ' . ($parts ? implode('  ', $parts) : '(nothing to do)') . "\n";

if ($WL_DRY) {
  echo "\nDry run: no paragraphs were created. Re-run without --dry-run to apply.\n";
} else {
  echo "\nDone. Review nodes at /admin/content.\n";
}
