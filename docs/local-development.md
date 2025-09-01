# Local Development Guide - Wilkes & Liberty Web CMS

This guide provides comprehensive instructions for setting up a local development environment for the Wilkes & Liberty headless Drupal 11 CMS using DDEV, following our principles of digital sovereignty and development freedom.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Initial Setup](#initial-setup)
- [DDEV Configuration](#ddev-configuration)
- [Drupal Installation](#drupal-installation)
- [Development Workflow](#development-workflow)
- [API Development](#api-development)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

**Operating System Support:**
- macOS (recommended for Wilkes & Liberty development)
- Linux (Ubuntu 20.04+, other distributions)
- Windows 10/11 with WSL2

**Required Software:**
- **Docker** (v20.10+) - Container runtime
- **DDEV** (v1.22.0+) - Local development environment
- **Git** (v2.30+) - Version control
- **Composer** (v2.0+) - PHP dependency management
- **PHP** (v8.1+) - For local CLI operations
- **Node.js** (v18+) - For build tools (optional)

### Installation Instructions

#### macOS Installation

```bash
# Install Docker Desktop
brew install --cask docker

# Install DDEV
brew install ddev/ddev/ddev

# Install Composer (if not already installed)
brew install composer

# Verify installations
docker --version
ddev version
composer --version
```

#### Linux Installation

```bash
# Install Docker (Ubuntu/Debian)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install DDEV
curl -fsSL https://pkg.ddev.com/apt/gpg.DD2316338C7BF2016B2D47C23A33DDA13A30C6B2.key | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/ddev.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/ddev.gpg] https://pkg.ddev.com/apt/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list
sudo apt update
sudo apt install ddev

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Log out and back in to refresh group membership
```

#### Windows (WSL2) Installation

```powershell
# Install WSL2 and Ubuntu
wsl --install

# From within WSL2, follow Linux installation steps
# Ensure Docker Desktop is configured to use WSL2 backend
```

## Initial Setup

### Repository Setup

```bash
# Clone the repository
git clone git@github.com:Wilkes-Liberty/webcms.git
cd webcms

# Or fork and clone your fork
git clone git@github.com:YOUR_USERNAME/webcms.git
cd webcms
git remote add upstream git@github.com:Wilkes-Liberty/webcms.git
```

### Environment Configuration

```bash
# Copy environment template (when available)
cp .env.example .env

# Edit environment variables for local development
# Set database credentials, API keys, etc.
```

## DDEV Configuration

### Project Configuration

The project includes a pre-configured `.ddev/config.yaml` file optimized for headless Drupal development:

```yaml
# .ddev/config.yaml
name: webcms
type: drupal11
docroot: web
php_version: "8.1"
webserver_type: nginx-fpm
router_http_port: "80"
router_https_port: "443"
xdebug_enabled: false
mariadb_version: "10.8"
use_dns_when_possible: true
composer_version: "2"

# Additional services for headless development
additional_services:
  - redis
  - elasticsearch

# Hooks for automated setup
hooks:
  post-start:
    - exec: composer install
    - exec: drush cr
```

### Custom DDEV Commands

The project includes custom DDEV commands in `.ddev/commands/web/`:

#### Setup Command (`ddev setup`)
```bash
#!/bin/bash
# .ddev/commands/web/setup
composer install
drush site:install minimal --yes
drush config:import --yes
drush cache:rebuild
drush user:create admin --password="admin123" --mail="admin@wilkesliberty.com"
drush user:role:add administrator admin
echo "Setup complete! Admin user created: admin/admin123"
```

#### API Test Command (`ddev api-test`)
```bash
#!/bin/bash
# .ddev/commands/web/api-test
echo "Testing JSON:API endpoints..."
curl -s -H "Accept: application/vnd.api+json" https://webcms.ddev.site/jsonapi/node/solution | jq .
echo "API test complete."
```

### Starting Your Environment

```bash
# Start DDEV (first time)
ddev start

# Run initial setup
ddev setup

# Access your site
ddev launch          # Opens in browser
ddev launch /admin   # Opens admin interface
```

## Drupal Installation

### Fresh Installation

```bash
# Start with a clean Drupal 11 installation
ddev start
ddev composer create-project drupal/recommended-project:^11.0 .
ddev composer require 'drupal/core-dev:^11.0' --dev

# Install Drupal
ddev drush site:install minimal --site-name="Wilkes & Liberty CMS" --account-name=admin --account-pass=admin123 --yes

# Install additional modules for headless development
ddev composer require drupal/jsonapi_extras drupal/simple_oauth drupal/decoupled_router drupal/cors
ddev drush en jsonapi jsonapi_extras simple_oauth decoupled_router cors -y
```

### Content Types Installation

```bash
# Import content type configurations
ddev drush config:import --yes

# Or create content types programmatically
ddev drush en devel_generate -y
ddev drush genc 50  # Generate sample content
```

## Development Workflow

### Daily Development

```bash
# Start development session
ddev start
ddev composer install  # Update dependencies if needed
ddev drush cr          # Clear caches
ddev launch            # Open site

# Make changes to code, configuration, content types
# Export configuration changes
ddev drush cex -y

# Test changes
ddev phpunit
ddev composer phpcs

# Commit changes
git add .
git commit -m "Add new revolutionary content type"
git push origin feature/content-type
```

### Configuration Management

```bash
# Export current configuration
ddev drush config:export --yes

# Import configuration (after pulling changes)
ddev drush config:import --yes

# Check configuration status
ddev drush config:status

# Compare configuration differences
ddev drush config:diff
```

### Database Management

```bash
# Create database backup
ddev export-db --file=backup-$(date +%Y%m%d).sql.gz

# Import database from file
ddev import-db --src=backup.sql.gz

# Access database directly
ddev mysql         # MySQL CLI
ddev phpmyadmin    # Web interface

# Reset database to fresh state
ddev drush sql:drop -y
ddev drush site:install minimal -y
ddev drush config:import -y
```

## API Development

### JSON:API Configuration

```bash
# Enable and configure JSON:API
ddev drush en jsonapi jsonapi_extras -y

# Configure JSONAPI extras for custom endpoints
ddev drush config:set jsonapi_extras.settings include_count true -y
ddev drush config:set jsonapi_extras.settings default_disabled false -y
```

### Testing API Endpoints

```bash
# Test JSON:API endpoints
curl -H "Accept: application/vnd.api+json" https://webcms.ddev.site/jsonapi/node/solution

# Test with authentication
TOKEN=$(ddev get-oauth-token)
curl -H "Authorization: Bearer $TOKEN" -H "Accept: application/vnd.api+json" https://webcms.ddev.site/jsonapi/node/solution

# Test GraphQL (if enabled)
curl -X POST https://webcms.ddev.site/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ nodeQuery { entities { ... on NodeSolution { title } } } }"}'
```

### Custom Module Development

```bash
# Generate module scaffolding
ddev drush generate:module

# Module structure for API endpoints
web/modules/custom/liberty_api/
├── liberty_api.info.yml
├── liberty_api.routing.yml
├── src/
│   ├── Controller/
│   │   └── LibertySolutionsController.php
│   └── Plugin/
│       └── rest/
│           └── resource/
│               └── LibertySolutionsResource.php
```

### Development URLs

```bash
# Main site
https://webcms.ddev.site

# Admin interface
https://webcms.ddev.site/admin

# JSON:API explorer
https://webcms.ddev.site/jsonapi

# API documentation (if installed)
https://webcms.ddev.site/admin/config/services/openapi

# Database admin
https://webcms.ddev.site:8037  # PhpMyAdmin
```

## Testing

### Automated Testing

```bash
# Run PHPUnit tests
ddev phpunit

# Run specific test classes
ddev phpunit web/modules/custom/liberty_api/tests/

# Run coding standards checks
ddev composer phpcs

# Fix coding standards violations
ddev composer phpcbf

# Run security checks
ddev composer security-check
```

### Manual Testing

```bash
# Test content creation workflow
ddev launch /node/add/solution

# Test API responses
ddev api-test

# Test configuration import/export
ddev drush config:export -y
ddev drush config:import -y

# Performance testing
ddev launch /admin/reports/status
```

### Content Testing

```bash
# Generate test content
ddev drush en devel_generate -y
ddev drush genc 10 --types=solution
ddev drush genc 10 --types=service
ddev drush genc 10 --types=technology

# Test content API responses
curl -s https://webcms.ddev.site/jsonapi/node/solution | jq '.data | length'
```

## Advanced Development

### Xdebug Configuration

```bash
# Enable Xdebug for debugging
ddev xdebug on

# Configure your IDE to connect to port 9003
# PHPStorm: Languages & Frameworks > PHP > Servers
# Add server: webcms.ddev.site, port 443, debugger Xdebug

# Disable when not needed (improves performance)
ddev xdebug off
```

### Custom Services

Add additional services to `.ddev/docker-compose.services.yml`:

```yaml
version: '3.6'
services:
  redis:
    container_name: ddev-${DDEV_SITENAME}-redis
    image: redis:7-alpine
    restart: unless-stopped
    ports:
      - "6379"
    environment:
      - REDIS_DATABASES=16

  elasticsearch:
    container_name: ddev-${DDEV_SITENAME}-elasticsearch
    image: elasticsearch:8.10.4
    restart: unless-stopped
    ports:
      - "9200"
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
```

### Performance Optimization

```bash
# Enable Redis for caching
ddev composer require drupal/redis
ddev drush en redis -y

# Configure Redis in settings.local.php
echo "Redis configuration added to settings.local.php"

# Enable CSS/JS aggregation
ddev drush config:set system.performance css.preprocess true -y
ddev drush config:set system.performance js.preprocess true -y

# Configure caching for API responses
ddev drush config:set system.performance cache.page.max_age 3600 -y
```

## Troubleshooting

### Common Issues

#### Docker/DDEV Issues

```bash
# DDEV won't start
ddev poweroff
ddev start

# Port conflicts
ddev stop
sudo lsof -i :80  # Check what's using port 80
ddev start

# Permission issues (Linux)
sudo chown -R $USER:$USER .ddev
```

#### Composer Issues

```bash
# Composer memory limit
ddev composer install --memory-limit=-1

# Clear Composer cache
ddev composer clear-cache

# Update Composer to latest version
ddev composer self-update
```

#### Drupal Issues

```bash
# Clear all caches
ddev drush cache:rebuild

# Fix file permissions
ddev exec chmod -R 755 web/sites/default/files
ddev exec chown -R www-data:www-data web/sites/default/files

# Database connection issues
ddev describe  # Check database credentials
```

#### Configuration Issues

```bash
# Configuration import fails
ddev drush config:import --partial -y

# Configuration export issues
ddev drush config:export --yes
git checkout config/sync/core.extension.yml  # If module conflicts
```

### Debugging API Issues

```bash
# Check API module status
ddev drush pm:list | grep -E "(json|api|rest)"

# Test API authentication
ddev drush eval "print_r(\Drupal::service('simple_oauth.oauth2_token_generator')->generate());"

# Check API permissions
ddev launch /admin/people/permissions

# View API logs
ddev logs | grep -i api
```

### Performance Debugging

```bash
# Check PHP memory usage
ddev exec php -i | grep memory

# Monitor database queries
ddev drush sql:cli --extra="--verbose"

# Profile page loads
ddev composer require drupal/webprofiler --dev
ddev drush en webprofiler -y
```

## Best Practices

### Security

- Keep all dependencies updated
- Use strong passwords for admin accounts
- Configure proper API authentication
- Regular security audits with `ddev composer audit`
- Never commit sensitive configuration or secrets

### Performance

- Enable Redis caching
- Configure CSS/JS aggregation
- Use CDN for file serving in production
- Optimize database queries
- Monitor API response times

### Development

- Always work in feature branches
- Export configuration after changes
- Write tests for custom functionality
- Follow Drupal coding standards
- Document API endpoints thoroughly

### Content Management

- Use consistent content type structures
- Implement editorial workflows
- Plan content relationships carefully
- Document content architecture decisions
- Test content migration procedures

## Additional Resources

### Documentation

- [DDEV Documentation](https://ddev.readthedocs.io/)
- [Drupal 11 API Documentation](https://api.drupal.org/api/drupal/11)
- [JSON:API Specification](https://jsonapi.org/)
- [Drupal REST API Guide](https://www.drupal.org/docs/core-modules-and-themes/core-modules/rest-module)

### Community

- [Drupal Slack](https://drupal.slack.com/) - #headless channel
- [DDEV Community](https://github.com/ddev/ddev/discussions)
- [Drupal API-First Initiative](https://www.drupal.org/community/initiatives/api-first)

### Tools

- [Postman](https://www.postman.com/) - API testing
- [Insomnia](https://insomnia.rest/) - REST client
- [GraphiQL](https://github.com/graphql/graphiql) - GraphQL explorer
- [jq](https://stedolan.github.io/jq/) - JSON processor

---

**Developing with digital sovereignty principles.**

*This local development environment empowers you to build content management systems that champion editorial independence and privacy-first design, following in the tradition of John Wilkes' fight for freedom of expression.*
