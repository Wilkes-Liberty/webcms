# Field Types & Formats Quick Reference

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
- Plain Text and Headless Safe formats do not include AI assistance

## Advanced Field Types

### Taxonomy Reference Fields

#### **Entity Reference (Taxonomy)**
- **Usage**: Link content to categorization terms
- **Cardinality**: Single or multiple values
- **Examples**: Compliance frameworks, capabilities, solutions
- **Configuration**: Target specific vocabularies
- **Auto-create**: Can allow new term creation

#### **Common Vocabularies**
- **Compliance**: Regulatory frameworks and standards
- **Capabilities**: Business capabilities and services  
- **Solutions**: Specific solution offerings
- **Topics**: General subject matter tags
- **Tech Stack**: Technology and platform tags

### System and Technical Fields

#### **Integer Fields with Ranges**
- **Usage**: Numeric values with validation
- **Examples**: Cache TTL, read time estimates
- **Validation**: Min/max values, step increments
- **Prefix/Suffix**: Units of measurement

#### **List String Fields**
- **Usage**: Predefined option sets
- **Configuration**: Admin-defined allowed values
- **Examples**: A/B variants, templates, visibility levels
- **Display**: Dropdown or radio buttons

### User Account References

#### **User Entity Reference**
- **Usage**: Link content to user accounts
- **Security**: Internal mapping only
- **Examples**: Author attribution, account associations
- **Privacy**: Not displayed publicly

## Field Validation Rules

### String Fields
- Maximum character limits (typically 255)
- Required field validation
- Pattern matching (URLs, emails)
- Case sensitivity options

### Text Fields
- Text format restrictions
- Content length guidelines
- Link validation
- HTML filtering and security

### Reference Fields
- Valid entity references only
- Bundle restrictions (specific content types)
- Access permission checks
- Auto-create permissions

### Media Fields
- File type restrictions (JPG, PNG, etc.)
- File size limits
- Image dimension requirements
- Focal point settings

### Integer Fields
- Numeric validation
- Min/max value constraints
- Step increment validation
- Null value handling

## Field Usage Patterns by Category

### Content Classification Fields
- **Taxonomy References**: Use for consistent categorization
- **Multiple Values**: Allow for comprehensive tagging
- **Controlled Vocabularies**: Maintain consistency across teams
- **Examples**: Compliance, capabilities, solutions, topics

### Technical System Fields  
- **Cache Controls**: Use sparingly and coordinate with platform team
- **Preview Tokens**: System-managed, don't edit manually
- **Advanced Fields**: Require technical knowledge and permissions
- **Examples**: CDN cache tags, revalidate TTL, preview tokens

### Testing and Analytics Fields
- **A/B Variants**: Use for conversion optimization
- **Campaign Tracking**: Enable detailed analytics attribution
- **Consistent Naming**: Follow campaign naming conventions
- **Examples**: A/B variants, UTM campaigns

### Layout and Design Fields
- **Template Selection**: Match template to content purpose
- **Theme Variants**: Use for brand consistency or special campaigns
- **Visibility Controls**: Manage content access appropriately
- **Examples**: Templates, design variants, visibility settings

### Content Relationship Fields
- **Hierarchical Organization**: Create logical content structures
- **Cross-References**: Link related content for discovery
- **Author Attribution**: Connect content to creators
- **Examples**: Parent content, related items, user associations

## Best Practices Summary

### Content Creation
1. **Choose appropriate field types** for your content needs
2. **Use correct text formats** based on content purpose
3. **Fill required fields** before optional ones
4. **Leverage AI assistance** where available
5. **Optimize for SEO** in summary and title fields
6. **Tag compliance content** appropriately for governance
7. **Set up A/B testing** for high-impact pages

### Field Management
1. **Keep field purposes clear** with good descriptions
2. **Use consistent naming** across content types
3. **Set appropriate validation** rules
4. **Configure sensible defaults** where possible
5. **Document custom configurations** for team reference
6. **Coordinate technical fields** with platform team
7. **Maintain taxonomy vocabularies** with regular reviews

### Quality Assurance
1. **Preview content** before publishing
2. **Test all links and references**
3. **Verify media displays** correctly
4. **Check mobile formatting**
5. **Validate SEO elements**
6. **Review compliance tagging** for accuracy
7. **Test A/B variants** for proper tracking

---

*This reference guide is updated regularly. For the most current information, consult the main Content Types Guide or contact the platform team.*
