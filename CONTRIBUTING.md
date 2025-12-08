# Contributing to Wilkes & Liberty Web CMS

Welcome to the Wilkes & Liberty Web CMS project! This document provides comprehensive guidelines for contributing to our headless Drupal 11 content management system using a GitHub flow workflow and DDEV local development environment.

## Table of Contents

- [Getting Started](#getting-started)
- [GitHub Flow Workflow](#github-flow-workflow)
- [Development Environment](#development-environment)
- [Branch Structure](#branch-structure)
- [Content Guidelines](#content-guidelines)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Deployment Process](#deployment-process)
- [Pull Request Process](#pull-request-process)

## Getting Started

### Prerequisites

- **DDEV** (v1.22.0+) - Local development environment management
- **Docker** - Container runtime required by DDEV
- **Composer** (v2.0+) - PHP dependency management
- **Git** - Version control system
- **GitHub account** with repository access
- **PHP** (v8.1+) - Required for Drupal 11
- **Node.js** (v18+) - For frontend build tools (optional)

### Initial Setup

1. **Fork the repository** on GitHub
2. **Clone your fork locally**:
   ```bash
   git clone git@github.com:YOUR_USERNAME/webcms.git
   cd webcms
   ```
3. **Add upstream remote**:
   ```bash
   git remote add upstream git@github.com:Wilkes-Liberty/webcms.git
   ```
4. **Install DDEV** if not already installed:
   ```bash
   # macOS with Homebrew
   brew install ddev/ddev/ddev
   
   # Or download from https://github.com/ddev/ddev/releases
   ```

## GitHub Flow Workflow

We use a simplified GitHub flow for all contributions, adapted for headless CMS development:

### 1. Sync with Master Branch

```bash
# Switch to master branch and pull latest changes
git checkout master
git pull upstream master
git push origin master
```

### 2. Create Feature Branch

```bash
# Create and switch to a new feature branch
git checkout -b feature/descriptive-name

# Examples:
# git checkout -b content/add-solution-content-type
# git checkout -b api/add-graphql-endpoint
# git checkout -b fix/json-api-serialization
# git checkout -b docs/update-deployment-guide
```

### 3. Start DDEV Environment

```bash
# Start your local development environment
ddev start

# Install/update dependencies
ddev composer install

# Import latest configuration
ddev drush cim -y

# Clear caches
ddev drush cr
```

### 4. Make Changes

- **Content types**: Create/modify content type configurations
- **Custom modules**: Develop custom functionality in `web/modules/custom/`
- **Configuration**: Export configuration changes with `ddev drush cex`
- **API endpoints**: Add custom REST or GraphQL endpoints
- **Templates**: Modify themes in `web/themes/custom/`

### 5. Test Locally

```bash
# Clear caches and test
ddev drush cr
ddev launch

# Run automated tests
ddev phpunit

# Test API endpoints
curl -X GET "https://api.wilkesliberty.local/jsonapi/node/solution" \
  -H "Accept: application/vnd.api+json"

# Validate configuration exports
ddev drush config:status
```

### 6. Export Configuration

```bash
# Export all configuration changes
ddev drush cex -y

# Review exported configuration
git diff config/sync/
```

### 7. Commit Changes

```bash
# Stage your changes
git add .

# Commit with descriptive message
git commit -m "Add solution content type with API endpoints

- Create solution content type with fields
- Configure JSON:API resource for solutions
- Add GraphQL schema for solution queries
- Export configuration for deployment"
```

### 8. Push and Create Pull Request

```bash
# Push feature branch to your fork
git push origin feature/descriptive-name

# Create pull request on GitHub
# Target: upstream/master ← your-fork/feature/descriptive-name
```

### 9. Code Review Process

- **Automated checks** will run (Drupal coding standards, security scans)
- **API testing** validates all endpoints work correctly
- **Configuration review** ensures clean configuration exports
- **Content team review** for editorial workflow changes
- **Security review** for API or permission changes

### 10. Clean Up

```bash
# After PR is merged, clean up local branches
git checkout master
git pull upstream master
git branch -d feature/descriptive-name
git push origin --delete feature/descriptive-name
```

## Development Environment

### DDEV Configuration

The project includes a `.ddev/config.yaml` file with optimized settings for headless Drupal development:

```yaml
name: webcms
type: drupal11
docroot: web
php_version: "8.1"
webserver_type: nginx-fpm
router_http_port: "80"
router_https_port: "443"
xdebug_enabled: false
additional_hostnames: []
additional_fqdns: []
mariadb_version: "10.8"
mysql_version: ""
use_dns_when_possible: true
composer_version: "2"
```

### Local Development Commands

```bash
# Start/stop DDEV environment
ddev start
ddev stop

# Install/update Composer dependencies
ddev composer install
ddev composer update

# Drush commands
ddev drush status
ddev drush cr            # Clear cache
ddev drush cim -y        # Import configuration
ddev drush cex -y        # Export configuration
ddev drush uli           # Generate one-time login link

# Database operations
ddev import-db --src=backup.sql.gz
ddev export-db --file=backup.sql.gz

# Access services
ddev launch              # Open site in browser
ddev launch /admin       # Open admin interface
ddev ssh                 # SSH into web container
ddev logs                # View container logs
```

### Development Server

We maintain a password-protected development environment for staging:

- **URL**: `https://api-dev.wilkesliberty.com`
- **Authentication**: API key authentication for content access
- **Deployment**: Automatic from `dev` branch
- **Purpose**: API testing and content preview

**Note**: Only maintainers can deploy to the development server.

## Branch Structure

### Main Branches

- **`master`**: Production-ready code
  - Always deployable to production
  - **Protected branch** with restrictions:
    - Direct pushes are blocked
    - Changes must go through pull requests
    - Automated tests must pass
    - At least one review required for non-maintainers
    - Force pushes prohibited
    - Branch deletion blocked
    - Admin bypass available when necessary
  - Source of truth for production releases

- **`dev`**: Development integration branch
- Deployed to https://api-dev.wilkesliberty.com
  - Used for staging and API testing
  - Maintained by project maintainers

### Branch Protection Details

The `master` branch is protected to ensure content management system stability:

#### What's Blocked:
- **Direct pushes**: `git push origin master` will be rejected
- **Force pushes**: `git push --force origin master` will be rejected
- **Branch deletion**: The master branch cannot be deleted

#### What's Required:
- **Pull requests**: All changes must go through a pull request
- **Automated tests**: PHPUnit, coding standards, security scans must pass
- **Configuration validation**: Exported configuration must be valid
- **API testing**: All endpoints must respond correctly

#### What's NOT Required for Maintainers:
- **External reviews**: Repository maintainers can approve their own PRs
- **Linear history**: Merge commits are allowed for feature integration

### Feature Branches

Create feature branches from `master` using descriptive names:

- `content/add-new-content-type`
- `api/graphql-endpoint`
- `feature/editorial-workflow`
- `fix/json-api-serialization`
- `config/permissions-update`
- `docs/api-documentation`

## Content Guidelines

### Content Management Principles

Follow the "Digital Liberation" theme in all content structure and editorial workflows:

- **Content Sovereignty**: Maintain complete control over content structure and governance
- **API-First Design**: Ensure all content is accessible via clean, well-documented APIs
- **Editorial Freedom**: Content workflows should empower editors, not constrain them
- **Privacy by Design**: No tracking or surveillance of editorial activities

### Content Type Standards

When creating or modifying content types:

1. **Naming Conventions**: Use snake_case for machine names (`solution_category`)
2. **Field Structure**: Group related fields using field groups
3. **API Exposure**: Configure JSON:API and GraphQL access appropriately
4. **Editorial UX**: Design forms that are intuitive for content creators
5. **Validation**: Implement appropriate field validation and required fields

### Content Architecture

Each content type should align with our revolutionary content strategy:

#### Solutions (`solution`)
- **Purpose**: Digital liberation product offerings
- **Key Fields**: title, description, revolutionary_impact, technical_details, case_studies
- **Relationships**: Categories, services, technologies

#### Services (`service`)
- **Purpose**: Professional services for digital independence
- **Key Fields**: title, description, delivery_models, competencies, success_stories
- **Relationships**: Solutions, capabilities, industries

#### Technology (`technology`)
- **Purpose**: Core technical capabilities
- **Key Fields**: title, description, sovereignty_approach, implementation_details, alternatives
- **Relationships**: Solutions, services, use_cases

### Editorial Workflows

- **Content Creation**: Editors create content in draft status
- **Review Process**: Content goes through editorial review before publication
- **API Publication**: Published content automatically becomes available via API
- **Version Control**: Configuration changes are tracked and versioned

## Code Standards

### Drupal Coding Standards

Follow Drupal 11 coding standards for all custom development:

```bash
# Check coding standards
ddev composer phpcs

# Automatically fix coding standards violations
ddev composer phpcbf
```

### Custom Module Development

When developing custom modules:

- Use `web/modules/custom/` directory
- Follow Drupal 11 APIs and best practices
- Include comprehensive documentation
- Write unit tests for complex functionality
- Ensure API endpoints are well-documented

### Configuration Management

- **Export everything**: All configuration changes must be exported
- **Clean exports**: Remove UUID and other environment-specific data
- **Logical grouping**: Group related configuration changes in single commits
- **Dependencies**: Ensure configuration dependencies are properly defined

### API Development Standards

When creating custom API endpoints:

```php
<?php

namespace Drupal\custom_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Custom API controller for digital liberation content.
 */
class LibertySolutionsController extends ControllerBase {

  /**
   * Returns solutions filtered by revolutionary impact.
   */
  public function getRevolutionarySolutions(): JsonResponse {
    // Implementation following Drupal best practices
  }
}
```

## Testing

### Local Testing Checklist

Before submitting a pull request:

- [ ] **DDEV Build**: `ddev start` completes successfully
- [ ] **Composer Install**: `ddev composer install` completes without errors
- [ ] **Configuration Import**: `ddev drush cim -y` imports without conflicts
- [ ] **Cache Clear**: `ddev drush cr` clears all caches
- [ ] **PHPUnit Tests**: `ddev phpunit` passes all tests
- [ ] **Coding Standards**: `ddev composer phpcs` passes
- [ ] **Security Check**: `ddev composer security-check` passes
- [ ] **API Endpoints**: All JSON:API and GraphQL endpoints respond correctly
- [ ] **Content Types**: All content type forms work correctly
- [ ] **Configuration Export**: `ddev drush cex` produces clean exports

### Content Testing

- [ ] **Content Creation**: Test creating content through admin interface
- [ ] **Editorial Workflow**: Test draft → review → published workflow
- [ ] **API Response**: Verify content appears in API responses
- [ ] **Field Validation**: Test required fields and validation rules
- [ ] **Content Relationships**: Test entity references and relationships

### API Testing

```bash
# Test JSON:API endpoints
curl -X GET "https://api.wilkesliberty.local/jsonapi/node/solution" \
  -H "Accept: application/vnd.api+json" | jq

# Test GraphQL endpoint
curl -X POST "https://api.wilkesliberty.local/graphql" \
  -H "Content-Type: application/json" \
  -d '{"query": "{ nodeQuery { entities { ... on NodeSolution { title } } } }"}'

# Test REST API endpoints
curl -X GET "https://api.wilkesliberty.local/api/solutions" \
  -H "Accept: application/json"
```

### Automated Testing

The project includes comprehensive automated testing:

- **Unit Tests**: PHPUnit tests for custom modules
- **Kernel Tests**: Integration tests for Drupal components
- **Functional Tests**: End-to-end testing of content workflows
- **API Tests**: Automated testing of all API endpoints
- **Security Tests**: Vulnerability scanning and dependency checking

## Deployment Process

### Automatic Deployment (Maintainers Only)

The project uses automated Git deployment for development environment:

```bash
# Deploy to development environment
git checkout dev
git merge master
git push origin dev
```

This triggers:
1. Automatic deployment to https://api-dev.wilkesliberty.com
2. Composer dependency installation
3. Configuration import
4. Cache clearing
5. Database updates if needed

### Configuration Deployment

```bash
# Deploy configuration changes
ddev drush deploy

# This runs:
# - drush updatedb -y
# - drush cim -y
# - drush cr
```

### Manual Production Deployment

Production deployments follow a controlled process:

1. **Preparation**: Merge approved changes to `master`
2. **Testing**: Verify all functionality in development environment
3. **Backup**: Create database and configuration backups
4. **Deploy**: Run deployment scripts on production server
5. **Validation**: Verify all APIs and content work correctly
6. **Rollback Plan**: Have rollback procedure ready if issues arise

## Pull Request Process

### Before Submitting

1. **Sync with upstream**: Ensure your branch is up-to-date
2. **Test thoroughly**: Follow the complete testing checklist
3. **Export configuration**: Ensure all config changes are exported
4. **Clean commit history**: Squash commits if needed
5. **Write clear description**: Explain what, why, and how

### PR Template

Use this template for pull requests:

```markdown
## Description
Brief description of changes and their revolutionary impact

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Content type/field changes
- [ ] API endpoint additions/modifications
- [ ] Configuration updates
- [ ] Documentation updates
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)

## Testing Done
- [ ] Local DDEV build successful
- [ ] All PHPUnit tests pass
- [ ] Coding standards validation passes
- [ ] Configuration imports/exports cleanly
- [ ] API endpoints tested manually
- [ ] Content workflows tested
- [ ] Security scan passes

## API Changes
If this PR affects APIs, describe the changes:
- New endpoints added
- Modified response structures
- Authentication changes
- Breaking changes to existing APIs

## Configuration Changes
- [ ] New content types created
- [ ] Fields added/modified
- [ ] Permissions updated
- [ ] Workflow changes
- [ ] API configuration modified

## Screenshots/API Examples
Add screenshots for UI changes or API response examples

## Additional Notes
Any additional context about revolutionary implications or technical considerations

## Checklist
- [ ] My code follows the Drupal coding standards
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings or errors
- [ ] All tests pass locally
- [ ] Configuration exports are clean and complete
```

### Review Process

1. **Automated Checks**: 
   - Drupal coding standards validation
   - PHPUnit test execution
   - Security vulnerability scanning
   - Configuration validation
   
2. **Manual Review**: 
   - Code quality and Drupal best practices
   - API design and documentation
   - Content architecture alignment
   - Security implications assessment
   
3. **Testing**: 
   - Functional testing of new features
   - API endpoint validation
   - Content workflow verification
   - Integration testing

### After Approval

- Maintainers will merge the PR using appropriate merge strategy
- Feature branch will be deleted automatically
- Changes may be deployed to staging for further testing
- Production deployment follows separate controlled process

## Questions or Help?

### Getting Help

- **GitHub Issues**: For bugs, feature requests, or technical questions
- **GitHub Discussions**: For general project discussion and ideas
- **Development Team**: Contact maintainers for urgent technical issues
- **Editorial Team**: Contact content team for editorial workflow questions

### Documentation Resources

- [Local Development Guide](docs/local-development.md)
- [API Documentation](docs/api/)
- [Content Architecture Guide](docs/content-architecture.md)
- [Deployment Procedures](docs/deployment/)

### Emergency Procedures

- **Production Issues**: Immediate escalation to technical lead
- **Security Vulnerabilities**: Direct contact to security team
- **Content Emergencies**: Editorial team escalation procedures

---

**Thank you for contributing to the digital liberation movement through sovereign content management!**

*"The liberty of the press is the palladium of all the civil, political, and religious rights."* - John Wilkes

*In building this headless CMS, we advance the cause of editorial independence, content sovereignty, and freedom from surveillance capitalism.*
