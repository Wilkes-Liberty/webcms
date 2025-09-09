# Headless Clean Helpers

A Drupal module providing strict HTML sanitization filters designed for headless CMS and API usage scenarios.

## Overview

The `headless_clean` module provides a customizable HTML sanitization filter that ensures only explicitly whitelisted HTML elements and attributes are retained in text content. This is particularly useful for:

- **Headless CMS implementations** where clean, predictable HTML structure is essential
- **API responses** that need consistent, minimal HTML output  
- **Content syndication** where strict HTML control is required
- **Security-focused environments** requiring additional HTML sanitization layers

## Features

- **Strict HTML sanitization** using DOMDocument for reliable parsing
- **Configurable whitelist** of allowed HTML tags and attributes
- **Flexible handling modes** (strict removal vs. tag unwrapping)
- **Security-first approach** with automatic blocking of dangerous attributes
- **Detailed logging** for debugging and monitoring (optional)
- **Integration with Drupal's filter system** and text format management

## Installation

1. Place the `headless_clean` module in your `modules/custom` directory
2. Enable the module: `drush en headless_clean`
3. Configure text formats at `/admin/config/content/formats`

## Configuration

### Filter Settings

The **Headless HTML Sanitizer** filter provides the following configuration options:

#### Allowed HTML Tags
- **Field**: Space-separated list of allowed HTML tag names (without angle brackets)
- **Default**: `p h2 h3 h4 ul ol li blockquote hr strong em code pre a`
- **Example**: `p h2 h3 ul ol li a strong em`

#### Allowed Attributes  
- **Field**: Space-separated list of allowed HTML attributes
- **Default**: `href`
- **Example**: `href title alt`
- **Note**: These attributes are allowed on any permitted tag

#### Strict Mode
- **Default**: Enabled
- **Enabled**: Disallowed tags are completely removed from output
- **Disabled**: Disallowed tags are unwrapped (content preserved, tags removed)

#### Log Sanitization Actions
- **Default**: Disabled
- **Purpose**: Logs sanitization events for debugging
- **Recommendation**: Enable only during development

### Text Format Integration

Add the filter to a text format:

1. Go to **Configuration > Content authoring > Text formats**
2. Edit or create a text format
3. Enable **Headless HTML Sanitizer** in the filter list
4. Configure the filter settings as needed
5. Set appropriate weight/order (typically after HTML correction filters)

### Example Configuration

For a typical headless CMS setup:

```yaml
# Example text format configuration
allowed_tags: 'p h2 h3 h4 ul ol li blockquote hr strong em code pre a'
allowed_attributes: 'href'
strict_mode: true
log_sanitization: false
```

## How It Works

### Sanitization Process

1. **Input Validation**: Empty content or plain text passes through unchanged
2. **DOM Parsing**: HTML is loaded into DOMDocument for reliable manipulation
3. **Tag Processing**: Each HTML element is evaluated against the whitelist
4. **Attribute Filtering**: Allowed elements have their attributes filtered
5. **Security Checks**: Dangerous attributes (events, JavaScript URLs, etc.) are blocked
6. **Output Generation**: Clean HTML is serialized and returned

### Security Features

The filter automatically blocks dangerous content:

- **Event handlers**: All `on*` attributes (onclick, onload, etc.)
- **Inline styles**: The `style` attribute is always removed
- **JavaScript URLs**: `javascript:` and `data:` URLs in href/src attributes
- **Unknown attributes**: Any attribute not in the whitelist

### Processing Modes

#### Strict Mode (Default)
```html
<!-- Input -->
<div class="container">
  <p>Hello <script>alert('xss')</script> world</p>
</div>

<!-- Output -->
<p>Hello world</p>
```

#### Non-Strict Mode
```html
<!-- Input -->
<div class="container">
  <p>Hello <span>world</span></p>
</div>

<!-- Output -->
<p>Hello world</p>
```

## Use Cases

### Headless CMS API
Ensure consistent HTML structure in API responses:

```php
// Content with mixed HTML
$content = '<div class="content"><p>Text</p><img src="..." /></div>';

// After filtering (strict mode)
$output = '<p>Text</p>';
```

### Content Syndication
Clean HTML for RSS feeds or content sharing:

```html
<!-- Before -->
<article class="post" data-id="123">
  <h2 style="color: red;">Title</h2>
  <p onclick="track()">Content</p>
</article>

<!-- After -->
<h2>Title</h2>
<p>Content</p>
```

### Email Templates
Generate clean HTML for email content:

```html
<!-- Input: Rich editor content -->
<div>
  <p style="margin: 10px;">
    <a href="https://example.com" target="_blank">Link</a>
  </p>
</div>

<!-- Output: Email-safe HTML -->
<p><a href="https://example.com">Link</a></p>
```

## Integration Examples

### With CKEditor
The filter works seamlessly with CKEditor configurations:

```yaml
# editor.editor.headless_clean.yml
editor: ckeditor5
settings:
  plugins:
    htmlSupport:
      allow:
        - name: 'p|h2|h3|h4|ul|ol|li|blockquote|hr|strong|em|code|pre|a'
          attributes: ['href']
```

### With Views and REST
Use in Views for API endpoints:

```yaml
# Display mode configuration
field_body:
  type: text_default
  settings:
    format: headless_clean  # Your text format
```

### Programmatic Usage
```php
use Drupal\filter\Entity\FilterFormat;

// Load the text format
$format = FilterFormat::load('headless_clean');

// Process content
$filtered = check_markup($content, 'headless_clean');
```

## Troubleshooting

### Content Disappearing
- **Check allowed tags**: Ensure required tags are in the whitelist
- **Review logs**: Enable logging to see what's being removed
- **Test incrementally**: Start with permissive settings, then restrict

### Attributes Being Removed  
- **Verify whitelist**: Check the allowed attributes configuration
- **Security blocking**: Some attributes are blocked regardless of settings

### Performance Issues
- **Large content**: The filter processes HTML using DOM manipulation
- **Complex markup**: Deeply nested structures require more processing
- **Caching**: Ensure proper caching is enabled for filtered content

## Development

### Testing
```bash
# Run module tests
phpunit web/modules/custom/headless_clean/tests/

# Enable debug logging
drush config:set headless_clean.settings log_sanitization true
```

### Extending
The filter can be extended by:

1. **Subclassing**: Create custom filters extending `FilterHeadlessSanitize`
2. **Services**: Inject additional services for custom processing
3. **Events**: Subscribe to filter events for additional logic

## Support

For issues, feature requests, or questions:

1. Check the Drupal logs at `/admin/reports/dblog`
2. Enable debug logging for detailed sanitization information
3. Review the filter configuration and text format settings

## License

This module is licensed under the same terms as Drupal core (GPL-2.0-or-later).
