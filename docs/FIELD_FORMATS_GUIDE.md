# Field Types & Formats Quick Reference

Updated: 2025-12-08

This quick reference guide helps content creators understand the different field types and text formats available in the CMS.

## Text Formats Overview

| Format | Purpose | HTML Output | AI Assistance | Best For |
|--------|---------|-------------|---------------|----------|
| **Headless Clean** | Rich content creation | Structured HTML | ✅ Full AI features | Body content, articles |
| **Headless Plain AI** | Simple text with AI | `<p>` and `<br>` only | ✅ AI completion & translate | Summaries, captions |
| **Headless Safe** | User-generated content | Sanitized HTML | ❌ No AI | Comments, bios |
| **Plain Text** | Strict plain text | Escaped HTML only | ❌ No AI | System fields, secure text |

## Field Types Reference

### Text Fields

#### **String** 
- **Usage**: Single line text
- **Max Length**: Configurable (usually 255 characters)
- **Examples**: Titles, names, URLs
- **Editor**: Simple text input

#### **Text (Long)**
- **Usage**: Multi-paragraph content
- **Max Length**: Unlimited
- **Examples**: Body content, descriptions
- **Editor**: CKEditor with formatting options

#### **Text with Summary**
- **Usage**: Content with optional teaser
- **Max Length**: Unlimited
- **Examples**: Article body with excerpt
- **Editor**: CKEditor + summary textarea

### Reference Fields

#### **Entity Reference**
- **Usage**: Link to other content
- **Options**: Autocomplete or dropdown
- **Examples**: Related articles, categories
- **Display**: Shows referenced item titles

#### **Media Reference** 
- **Usage**: Images, videos, documents
- **Options**: Upload or select from library
- **Examples**: Hero images, attachments
- **Display**: Media player/image display

### Date Fields

#### **Date**
- **Usage**: Simple date selection
- **Format**: Date picker
- **Examples**: Publication date, deadlines

#### **Smart Date**
- **Usage**: Events with start/end times
- **Features**: Timezone support, recurring
- **Examples**: Event schedules, meetings

### Boolean Fields

#### **Checkbox**
- **Usage**: Yes/No options
- **Examples**: Featured content, visibility toggles
- **Display**: Checked/unchecked box

### Number Fields

#### **Integer**
- **Usage**: Whole numbers
- **Examples**: Read time, quantities
- **Validation**: Numeric only

#### **Decimal**
- **Usage**: Numbers with decimals
- **Examples**: Prices, ratings
- **Precision**: Configurable decimal places

### List Fields

#### **Select List**
- **Usage**: Predefined options
- **Options**: Single or multiple select
- **Examples**: Content status, categories
- **Configuration**: Admin-defined options

## Common Field Configurations

### Summary/Deck Fields
- **Type**: Text (Long)
- **Format**: Headless Plain AI
- **Purpose**: SEO descriptions, teasers
- **Length**: 160-200 characters recommended

### Body Content Fields
- **Type**: Text (Long) 
- **Format**: Headless Clean
- **Purpose**: Main article/page content
- **Features**: Full rich text editing

### Image Fields
- **Type**: Image or Media Reference
- **Requirements**: JPG/PNG formats
- **Recommendations**: Set focal points
- **Alt Text**: Always required for accessibility

### Link/CTA Fields
- **Type**: Link
- **Requirements**: Valid URLs with titles
- **Best Practice**: Use descriptive link text
- **Protocol**: HTTPS preferred

## Field Display Patterns

### Required vs Optional
- **Required**: Must be completed before saving
- **Optional**: Can be left blank
- **Conditional**: Required based on other field values

### Translatable Fields
- **Purpose**: Multi-language content
- **Behavior**: Separate values per language
- **Examples**: Title, body, summary

### Revisioned Fields
- **Purpose**: Track content changes
- **Behavior**: Stored with content revisions
- **Examples**: Most content fields

## Text Format Comparison

### When to Use Each Format

#### **Headless Clean**
✅ **Use for:**
- Article body content
- Page descriptions  
- Rich content that needs formatting
- Content created by editors/authors

❌ **Don't use for:**
- User-generated content
- Simple text that doesn't need formatting
- Fields displayed in listings

#### **Headless Plain AI**
✅ **Use for:**
- Summary/deck fields
- Meta descriptions
- Image captions
- Brief descriptions
- Any field that needs AI assistance but minimal HTML

❌ **Don't use for:**
- Long-form content needing rich formatting
- User-generated content
- Legacy content

#### **Headless Safe**
✅ **Use for:**
- User-generated content
- Comments and reviews
- Bios from external sources
- Content that needs basic formatting but strict security

❌ **Don't use for:**
- Editorial content (use Headless Clean instead)
- Simple text (use Headless Plain AI instead)

#### **Plain Text**
✅ **Use for:**
- Fields requiring strict HTML escaping
- System-generated content that might contain unsafe HTML
- Security-sensitive text fields
- Legacy fields that need HTML protection
- Technical fields like code snippets, tokens, or IDs

❌ **Don't use for:**
- New content fields (use Headless Plain AI instead)
- Content that would benefit from AI assistance
- User-facing content that needs basic formatting

## AI Writing Features

### Available in Headless Clean Format
- **Text Completion**: Auto-complete sentences and paragraphs
- **Rewriting**: Improve existing content
- **Tone Adjustment**: Modify writing style
- **Translation**: Convert between languages
- **Spelling/Grammar**: Fix errors and improve clarity
- **Summarization**: Create summaries of long content

### Available in Headless Plain AI Format
- **Text Completion**: Auto-complete for summaries
- **Translation**: Language conversion
- **Limited Features**: Focused on simple text improvement

### Not Available in Other Formats
- **Headless Safe**: Security over convenience, no AI
- **Plain Text**: Strict security, no formatting or AI