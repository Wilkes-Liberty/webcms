# WL Text Formats

A custom Drupal module providing specialized text formats and HTML sanitization for Wilkes Liberty's headless architecture.

## Overview

This module provides custom text formats designed specifically for Wilkes Liberty's headless Drupal setup, including:

- **headless_clean**: For admin/backend content authoring with rich editing capabilities
- **headless_safe**: For frontend user-generated content with strict sanitization
- Custom HTML sanitization filter for consistent API output
- CKEditor 5 integration with AI assistance

## Features

- **Two specialized text formats**: Optimized for different use cases
- **Custom HTML sanitizer**: Configurable tags, attributes, and processing modes
- **CKEditor 5 integration**: Rich editing with AI assistance for admin content
- **Headless-optimized output**: Clean, structured HTML for Next.js frontend consumption
- **GraphQL-ready**: Predictable markup structure for GraphQL queries

## Text Formats

### Headless (Clean HTML)
- **Use case**: Admin/backend content authoring
- **Features**: Full CKEditor 5 toolbar, AI assistance, code blocks, rich formatting
- **Allowed tags**: Headings (h2-h4), paragraphs, lists, links, formatting, code blocks
- **Sanitization**: Non-strict mode, preserves admin formatting choices

### Headless (Frontend Safe)
- **Use case**: Frontend user-generated content
- **Features**: Basic formatting only, strict sanitization
- **Allowed tags**: Paragraphs, basic formatting (strong, em), lists, safe links
- **Sanitization**: Strict mode, aggressive filtering, logging enabled

## Installation

1. Place this module in your `modules/custom` directory
2. Enable the module: `drush pm:install wl_text_formats`
3. Both text formats will be automatically available

## Configuration

The module includes pre-configured text formats. To modify:

1. Go to **Administration » Configuration » Text formats and editors**
2. Edit "Headless (Clean HTML)" or "Headless (Frontend Safe)"
3. Adjust filter settings as needed

### Filter Settings
- **Allowed HTML tags**: Space-separated list without angle brackets
- **Allowed attributes**: Space-separated, supports element-specific syntax like `class(code)`
- **Strict mode**: Remove vs. convert disallowed elements
- **Log sanitization**: Enable logging for frontend content monitoring

## Technical Architecture

### Frontend Integration
- **Next.js compatibility**: Clean HTML output optimized for React rendering
- **GraphQL ready**: Structured content for efficient queries
- **Code highlighting**: Support for syntax highlighting with language classes

### Security Model
- **Admin content**: Permissive filtering, trusted input
- **User content**: Aggressive sanitization, untrusted input
- **Attribute filtering**: Element-specific attribute restrictions

## AI Integration

The headless_clean format includes AI assistance via:
- Content generation and completion
- Tone adjustment and translation
- HTML reformatting and spell checking
- Constrained output matching allowed HTML elements

## Requirements

- Drupal 11.x
- CKEditor 5 (for rich editing)
- AI CKEditor module (for AI features)
- PHP DOM extension

## Support

Custom module for Wilkes Liberty's headless CMS implementation.
