# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is a **headless Drupal 11 CMS** serving as the API backend for the Wilkes & Liberty digital liberation platform. The system powers [wilkesliberty.com](https://wilkesliberty.com) and other front-end applications via RESTful APIs, JSON:API, and GraphQL endpoints.

Key architectural principles:
- **API-First Design**: All content accessible via clean, well-documented APIs
- **Content Sovereignty**: Complete control over content structure and governance  
- **Editorial Freedom**: Tab-based field organization empowering content creators
- **Privacy by Design**: No surveillance capitalism or data harvesting
- **AI-Enhanced Content**: Integrated AI modules for content generation and optimization

## Development Environment

### Prerequisites

- **DDEV** (v1.22.0+) for local development
- **Docker** for containerization
- **Composer** (v2.0+) for PHP dependency management
- **PHP** (v8.3+) as configured in DDEV

### DDEV Configuration

The project uses custom DDEV settings optimized for headless Drupal development:

```yaml path=/Users/jcerda/Sites/WilkesLiberty/www/api/.ddev/config.yaml start=1
name: cms
type: drupal11
docroot: web
php_version: "8.3"
webserver_type: nginx-fpm
database:
  type: mariadb
  version: "10.11"
additional_fqdns:
  - cms.wl.dev
```

### Essential Development Commands

#### Project Setup
```bash
# Start DDEV environment
ddev start

# Install/update dependencies
ddev composer install

# Import configuration
ddev drush cim -y

# Clear caches
ddev drush cr

# Access the site
ddev launch                # Main site
ddev launch /admin        # Admin interface
```

#### Configuration Management
```bash
# Export configuration changes
ddev drush cex -y

# Check configuration status
ddev drush config:status

# Compare configuration differences
ddev drush config:diff

# Deploy configuration (runs updatedb + cim + cr)
ddev drush deploy
```

#### Database Operations
```bash
# Create database backup
ddev export-db --file=backup-$(date +%Y%m%d).sql.gz

# Import database
ddev import-db --src=backup.sql.gz

# Access database
ddev mysql                # CLI
ddev phpmyadmin          # Web interface
```

#### Content & API Development
```bash
# Generate sample content
ddev drush en devel_generate -y
ddev drush genc 10 --types=article

# Test API endpoints
curl -H "Accept: application/vnd.api+json" https://cms.ddev.site/jsonapi/node/article

# Test GraphQL
curl -X POST https://cms.ddev.site/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ nodeQuery { entities { ... on NodeArticle { title } } } }"}'
```

#### Testing & Quality Assurance
```bash
# Run PHPUnit tests
ddev phpunit

# Check coding standards
ddev composer phpcs

# Fix coding standards violations
ddev composer phpcbf

# Run security checks
ddev composer security-check
```

#### Translation Management
```bash
# Export interface translations
./scripts/export-interface-translations.sh

# Import custom translations
./scripts/import-custom-translations.sh

# Export custom translations
./scripts/export-custom-translations.sh
```

## High-Level Architecture

### Content Model

The CMS employs a sophisticated content architecture organized around digital liberation themes:

#### Content Types
- **Article**: News, blog posts, editorial content with rich media support
- **Landing Page**: Marketing pages with component-based layout using Paragraphs
- **Basic Page**: Standard informational pages
- **Case Study**: Success stories with structured templates
- **Resource**: Downloadable content with compliance tracking
- **Career**: Job postings with department/seniority classifications
- **Event**: Webinars, conferences with event type categorization

#### Taxonomy Architecture
- **Solutions**: Digital liberation product offerings (migrated from use_cases)
- **Technologies**: Technical capabilities and tools
- **Capabilities**: Service offerings and competencies (migrated from services)
- **Industries**: Sector-specific focus areas
- **Topics**: Content categorization for tagging
- **Tech Stack**: Technical implementation details
- **Personas**: Target audience segments
- **Compliance**: Regulatory framework tracking

#### Field Organization

Content types use **tab-based field organization** for optimal editorial experience:

- **Content Tab**: Body, summary, components
- **Media Tab**: Hero images, social images, media assets
- **CTAs Tab**: Primary and secondary call-to-action links
- **SEO Tab**: Meta titles, descriptions, canonical URLs, breadcrumb labels
- **Technical Tab**: Cache tags, revalidation TTL, preview tokens
- **Relationships Tab**: Parent/child relationships, related content, capability connections
- **Classification Tab**: Taxonomy assignments (industry, personas, technologies)
- **Layout Tab**: Template selection, theme variants, visibility settings
- **Editorial Tab**: Read time, campaign tracking, review dates

### API Architecture

#### JSON:API (Primary)
- **Endpoint**: `https://cms.ddev.site/jsonapi`
- **Enhanced with jsonapi_extras**: Custom resource configurations, include/exclude fields
- **Content Types**: All content accessible via `/jsonapi/node/{content_type}`
- **Taxonomy**: All vocabularies via `/jsonapi/taxonomy_term/{vocabulary}`

#### GraphQL
- **Endpoint**: `https://cms.ddev.site/graphql`
- **Schema**: Auto-generated from content types using graphql_compose
- **Features**: Complex queries, nested relationships, field selection

#### REST API
- **Custom endpoints**: Located in `web/modules/custom/`
- **Authentication**: JWT and OAuth2 support via simple_oauth module
- **Rate limiting**: Configured for production API usage

### AI Integration

The platform incorporates cutting-edge AI capabilities:

- **ai**: Core AI framework
- **ai_image_alt_text**: Automated alt text generation
- **ai_seo**: SEO optimization suggestions  
- **ai_tmgmt**: AI-powered translation workflows
- **ai_provider_openai**: OpenAI integration
- **auto_translation**: Multilingual content automation

### Performance & Caching

- **Redis**: Caching backend for improved performance
- **Purge**: Cache invalidation for dynamic content
- **CSS/JS aggregation**: Optimized asset delivery
- **Image optimization**: Focal Point integration with WebP support
- **CDN-ready**: Structured for content delivery network integration

### Multilingual Support

- **Spanish (es)** and **Russian (ru)** translations
- **Custom translation scripts**: Located in `/scripts/` directory
- **Interface translations**: Exportable/importable via Drush commands
- **AI-powered translation**: Integrated with translation management modules

## Custom Modules

Located in `web/modules/custom/`:

- **wl_language_switcher**: Custom language switching functionality
- **wl_taxo_nav**: Auto-sync taxonomy terms to main menu navigation
- **wl_text_formats**: Custom text format configurations  
- **tmgmt_session_fix**: Translation management fixes

## Development Workflow

### Branch Strategy
- **master**: Production-ready code (protected branch)
- **dev**: Development integration deployed to staging
- **feature/**: Feature development branches

### Configuration Management
Always export configuration changes after modifications:

```bash
# After making changes in admin interface
ddev drush cex -y
git add config/sync/
git commit -m "Export configuration changes"
```

### Custom Development
When creating custom modules:

```bash
# Generate module scaffolding
ddev drush generate:module

# Place in web/modules/custom/
# Follow Drupal 11 coding standards
# Include comprehensive tests
```

### API Development
For custom API endpoints:

```bash
# Test endpoints during development
curl -X GET "https://cms.ddev.site/jsonapi/node/article" \
  -H "Accept: application/vnd.api+json" | jq

# Validate GraphQL queries
curl -X POST "https://cms.ddev.site/graphql" \
  -H "Content-Type: application/json" \
  -d '{"query": "{ nodeQuery { entities { entityId entityLabel } } }"}'
```

### Common Development Tasks

#### Adding New Content Types
1. Create content type via admin or configuration
2. Add fields using tab-based organization
3. Configure form/view displays with field groups
4. Export configuration: `ddev drush cex -y`
5. Test API endpoints for new content type

#### Working with Taxonomies
1. Create/modify vocabularies in Structure > Taxonomy
2. Add custom fields (e.g., field_show_in_nav for menu integration)
3. Configure wl_taxo_nav module for automatic menu sync
4. Export configuration and test API responses

#### Managing Translations
1. Use provided scripts for consistent translation workflows
2. Export interface customizations before deployment
3. Test multilingual API responses
4. Leverage AI translation modules for efficiency

## Key Dependencies

### Headless CMS Modules
- **jsonapi_extras**: Enhanced JSON:API functionality
- **decoupled_router**: Frontend routing support
- **graphql_compose**: GraphQL schema generation
- **simple_oauth**: API authentication

### Content Management
- **paragraphs**: Component-based page building
- **field_group**: Tab-based field organization
- **scheduler**: Content scheduling
- **content_moderation**: Editorial workflows

### Performance & SEO
- **redis**: Caching backend
- **metatag**: SEO meta tags
- **pathauto**: URL aliases
- **focal_point**: Smart image cropping

### AI & Translation
- **ai** suite: AI content enhancement
- **tmgmt**: Translation management
- **auto_translation**: Automated multilingual workflows

## Environment URLs

### Development
- **Main Site**: `https://cms.ddev.site`
- **Admin**: `https://cms.ddev.site/admin`  
- **JSON:API**: `https://cms.ddev.site/jsonapi`
- **GraphQL**: `https://cms.ddev.site/graphql`
- **PhpMyAdmin**: `https://cms.ddev.site:8037`

### Staging
- **Dev Environment**: `https://cms-dev.wilkesliberty.com`

## Troubleshooting

### Common Issues

#### DDEV won't start
```bash
ddev poweroff
ddev start
```

#### Configuration import fails
```bash
ddev drush config:import --partial -y
```

#### API authentication issues
```bash
# Check OAuth2 configuration
ddev drush eval "print_r(\Drupal::service('simple_oauth.oauth2_token_generator')->generate());"
```

#### Translation problems
```bash
# Rebuild translation cache
ddev drush locale:rebuild
ddev drush cr
```

This WARP.md provides the essential context for productive development in this headless Drupal 11 CMS environment, focusing on the unique architectural decisions, development workflows, and API-first approach that distinguishes this digital liberation platform.