# Wilkes & Liberty Web CMS - Headless Drupal 11

**Digital liberation through sovereign content management.** This headless Drupal 11 CMS serves as the backend foundation for [wilkesliberty.com](https://wilkesliberty.com), championing digital independence and privacy-first content management in the tradition of John Wilkes' fight for liberty.

> *"The liberty of the press is the palladium of all the civil, political, and religious rights."* - John Wilkes

## Project Overview

This headless Drupal 11 installation serves as a decoupled content management system, providing API-first content delivery while maintaining editorial freedom and data sovereignty. The system powers our digital liberation platform by delivering structured content to multiple front-end applications while ensuring complete control over our data and content governance.

### Revolutionary Principles

- **Content Sovereignty**: Complete ownership and control of our content and data
- **API-First Architecture**: Headless design enables maximum flexibility and independence
- **Privacy by Design**: No surveillance capitalism, no data harvesting
- **Developer Freedom**: Open-source tools and transparent development practices
- **Editorial Liberty**: Content creators maintain full editorial control and workflow autonomy

## Project Structure

```
├── config/                   # Drupal configuration management
│   └── sync/                # Configuration synchronization files
├── docs/                    # Project documentation
│   ├── local-development.md # DDEV setup and development guide
│   ├── api/                # API documentation
│   └── deployment/         # Deployment procedures
├── drush/                  # Drush configuration and commands
├── patches/                # Composer patches for contributed modules
├── scripts/                # Deployment and maintenance scripts
├── web/                    # Drupal web root
│   ├── core/              # Drupal core (managed by Composer)
│   ├── modules/           # Custom and contributed modules
│   │   ├── contrib/       # Contributed modules (managed by Composer)
│   │   └── custom/        # Custom modules for Wilkes & Liberty
│   ├── profiles/          # Installation profiles
│   ├── sites/             # Site-specific configuration
│   │   └── default/       # Default site configuration
│   └── themes/            # Custom and contributed themes
│       ├── contrib/       # Contributed themes (managed by Composer)
│       └── custom/        # Custom themes for headless integration
├── .ddev/                 # Local development environment configuration
├── composer.json         # PHP dependency management
└── composer.lock         # Locked dependency versions
```

## Core Capabilities

### Content API Architecture
- **RESTful APIs**: Full REST API support for all content types
- **JSON:API**: Drupal's native JSON:API for standardized data exchange
- **GraphQL Integration**: Advanced query capabilities for complex data relationships
- **Custom API Endpoints**: Tailored endpoints for specific front-end requirements

### Content Management Features
- **Structured Content Types**: Purpose-built content types for digital liberation content
- **Editorial Workflows**: Review and approval processes for content governance
- **Media Management**: Sovereign file and image management with CDN integration
- **Multi-language Support**: Internationalization for global digital liberation advocacy

### Security & Privacy
- **API Authentication**: JWT and OAuth2 integration for secure API access
- **Permission-Based Access**: Granular permissions for content and API access
- **Content Encryption**: Sensitive content protection and encryption capabilities
- **Audit Logging**: Complete audit trail for all content and configuration changes

## Development Environment

### Prerequisites

- **DDEV** (v1.22.0+) - Local development environment
- **Docker** - Container runtime for DDEV
- **Composer** (v2.0+) - PHP dependency management
- **Git** - Version control
- **Node.js** (v18+) - For frontend build tools (optional)

### Quick Start

```bash
# Clone the repository
git clone git@github.com:Wilkes-Liberty/webcms.git
cd webcms

# Start DDEV environment
ddev start

# Install dependencies
ddev composer install

# Import configuration
ddev drush cim -y

# Generate sample content (optional)
ddev drush en devel_generate -y
ddev drush genc 50

# Access the site
ddev launch
```

### API Endpoints

Once running, your headless Drupal instance will provide these key endpoints:

- **JSON:API**: `https://webcms.ddev.site/jsonapi`
- **REST API**: `https://webcms.ddev.site/api`
- **GraphQL**: `https://webcms.ddev.site/graphql`
- **Admin Interface**: `https://webcms.ddev.site/admin`

## Content Architecture

### Revolutionary Content Types

#### Solutions (`solution`)
Digital liberation product offerings and platforms
- Revolutionary impact metrics
- Technical sovereignty details
- Implementation case studies

#### Services (`service`)
Professional services for digital independence
- Service delivery models
- Competency frameworks
- Client success stories

#### Technology (`technology`)
Core technical capabilities and innovations
- Sovereignty-first technical approaches
- Privacy-preserving implementations
- Open-source alternatives

#### Industries (`industry`)
Industry-specific liberation strategies
- Sector-specific challenges
- Tailored solution approaches
- Regulatory compliance frameworks

#### Capabilities (`capability`)
Organizational competencies for digital freedom
- Expertise domains
- Certification standards
- Continuous improvement processes

### Content Relationships

The system maintains sophisticated relationships between content types to support complex content queries and ensure editorial consistency across all digital liberation messaging.

## API Integration

### Frontend Integration

This headless CMS is designed to serve multiple frontend applications:

- **Primary Website**: Hugo-based static site at [wilkesliberty.com](https://wilkesliberty.com)
- **Mobile Applications**: React Native apps for iOS and Android
- **Partner Portals**: Client and partner-specific interfaces
- **Developer Documentation**: API-driven documentation sites

### Authentication & Security

```javascript
// Example API authentication
const response = await fetch('https://api.wilkesliberty.com/jsonapi/node/solution', {
  headers: {
    'Authorization': 'Bearer YOUR_JWT_TOKEN',
    'Content-Type': 'application/vnd.api+json',
    'Accept': 'application/vnd.api+json'
  }
});
```

## Contributing

We welcome contributions to advance the cause of digital liberation! This project follows the same revolutionary principles as our main website development.

Please see our [CONTRIBUTING.md](CONTRIBUTING.md) file for detailed information about:

- **GitHub Flow Workflow**: Pull request-based development process
- **DDEV Development Environment**: Local setup and testing procedures
- **Content Standards**: Editorial guidelines for digital liberation messaging
- **API Development**: Standards for custom endpoints and integrations
- **Security Practices**: Maintaining privacy-first development standards

### Branch Protection & Workflow

The `master` branch is protected to maintain system stability:

- ✅ **Pull Request Required**: Direct pushes to `master` are blocked
- ✅ **Automated Testing**: All PRs must pass automated tests
- ✅ **Content Review**: Editorial review required for content changes
- ✅ **Security Scanning**: Automated security vulnerability scanning

### Development Workflow

```bash
# Create and switch to a feature branch
git checkout -b feature/api-enhancement

# Make your changes and test locally
ddev drush cr
ddev phpunit

# Commit and push feature branch
git add .
git commit -m "Add new API endpoint for solution filtering"
git push origin feature/api-enhancement

# Create pull request targeting master branch
```

## Configuration Management

### Environment-Specific Settings

```php
// web/sites/default/settings.php
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
```

### Configuration Synchronization

```bash
# Export current configuration
ddev drush cex -y

# Import configuration changes
ddev drush cim -y

# Check configuration status
ddev drush config:status
```

## Testing & Quality Assurance

### Automated Testing

```bash
# Run PHPUnit tests
ddev phpunit

# Run code quality checks
ddev composer phpcs
ddev composer phpcbf

# Run security analysis
ddev composer security-check
```

### Content Testing

- **API Response Validation**: Automated testing of all API endpoints
- **Content Structure Validation**: Ensuring consistent content type schemas
- **Performance Testing**: API response time and throughput testing
- **Security Testing**: Authentication and authorization validation

## Deployment

### Staging Environment

- **URL**: `https://cms-dev.wilkesliberty.com`
- **Purpose**: Content review and API testing
- **Access**: Authenticated access for development team
- **Deployment**: Automatic from `dev` branch

### Production Environment

- **URL**: `https://cms.wilkesliberty.com`
- **Purpose**: Production content delivery
- **Access**: API-only access (no admin interface)
- **Deployment**: Manual deployment from `master` branch

### Deployment Process

```bash
# Production deployment (authorized personnel only)
git checkout master
git pull origin master
composer install --no-dev --optimize-autoloader
drush deploy
```

## Monitoring & Maintenance

### Performance Monitoring
- API response time tracking
- Database query optimization
- Content delivery performance
- Cache hit ratio monitoring

### Security Monitoring
- Failed authentication attempt tracking
- Suspicious API access pattern detection
- Vulnerability scanning and patching
- Regular security audit procedures

### Content Governance
- Editorial workflow compliance monitoring
- Content quality assurance processes
- API usage analytics and optimization
- Regular content audit and cleanup

## Support & Resources

### Documentation
- [Local Development Guide](docs/local-development.md)
- [API Documentation](docs/api/)
- [Content Editorial Guidelines](docs/editorial-guidelines.md)
- [Deployment Procedures](docs/deployment/)

### Community
- **GitHub Issues**: Bug reports and feature requests
- **Development Team**: Internal technical support
- **Editorial Team**: Content strategy and governance support

### Emergency Contacts
- **Technical Issues**: Development team escalation
- **Content Issues**: Editorial team escalation
- **Security Incidents**: Immediate security team notification

---

**Defending digital liberty through sovereign content management.**

*In the spirit of John Wilkes' fight for freedom of expression, we build systems that ensure editorial independence, content sovereignty, and protection from surveillance capitalism.*
