# Comprehensive Field Reference Documentation

**Last Updated**: December 8, 2025  
**Version**: 2.0  
**Purpose**: Complete reference for all content management fields

> **⚠️ Previous Version**: The previous field types reference has been moved to [FIELD_FORMATS_GUIDE.md](FIELD_FORMATS_GUIDE.md)
> 
> **📋 This Document**: Now provides comprehensive documentation for all content fields, addressing the gaps identified in FIELD_DOCUMENTATION_GAPS.md

This guide documents all 54+ fields in the CMS, filling the previously identified documentation gaps.

---

## Table of Contents

1. [Critical Fields](#critical-fields)
   - [Persona & Targeting](#persona--targeting)
   - [SEO & Meta Fields](#seo--meta-fields) 
   - [Media Fields](#media-fields)
   - [Call-to-Action Fields](#call-to-action-fields)
2. [Content Organization](#content-organization)
3. [Technical & System Fields](#technical--system-fields)
4. [Field Usage by Content Type](#field-usage-by-content-type)
5. [Best Practices](#best-practices)

---

## Critical Fields

### Persona & Targeting

#### `field_personas`
**Type**: Entity Reference (Taxonomy Term)  
**Cardinality**: Unlimited  
**Used by**: Article, Basic Page, Event, Landing Page, Resource

**Purpose**: Define target audiences for content to enable personalized experiences and targeted content delivery.

**Configuration**:
- References taxonomy terms from the "Personas" vocabulary
- Multiple personas can be selected per piece of content
- Used for content filtering and audience-specific recommendations

**Usage Guidelines**:
```
✅ DO:
- Select all relevant personas for the content
- Use specific persona terms (e.g., "CTO", "Marketing Manager")  
- Consider primary and secondary audiences

❌ DON'T:
- Leave this field empty for public-facing content
- Use overly broad persona categories
- Select personas that don't match the content intent
```

**Examples**:
- **Article about AI implementation**: Select "CTO", "Technical Director", "Innovation Manager"
- **Case study**: Select personas matching the client profile and target readers
- **Resource/whitepaper**: Select personas based on the document's intended audience

---

### SEO & Meta Fields

#### `field_meta_description`
**Type**: String (255 characters max)  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Custom meta description for search engines and social media previews.

**Usage Guidelines**:
- **Length**: 150-160 characters for optimal display
- **Content**: Compelling summary that includes target keywords
- **Unique**: Each page should have a unique meta description

**Examples**:
```
✅ Good: "Learn how AI automation reduced processing time by 75% for Fortune 500 companies. Download our comprehensive implementation guide."

❌ Poor: "This page contains information about our services and solutions."
```

#### `field_seo_title`
**Type**: String (255 characters max)  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Custom SEO title override for search engine results pages (SERPs).

**Usage Guidelines**:
- **Length**: 50-60 characters for optimal display
- **Format**: Primary Keyword | Secondary Keyword | Brand
- **Unique**: Every page should have a unique SEO title

**Examples**:
```
✅ Good: "AI Implementation Guide | Enterprise Automation | Wilkes & Liberty"

❌ Poor: "Untitled Page | Wilkes & Liberty"
```

#### `field_canonical`
**Type**: Link  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Specify the canonical URL to prevent duplicate content issues.

**Usage Guidelines**:
- Use when content exists on multiple URLs
- Point to the preferred/original version
- Include protocol (https://)
- Use absolute URLs

**Examples**:
- Original article: Leave empty (self-canonical)
- Syndicated content: Point to original source
- Duplicate pages: Point to preferred version

#### `field_noindex`
**Type**: Boolean  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Prevent search engines from indexing specific content.

**Usage Guidelines**:
```
✅ Use for:
- Draft/work-in-progress content
- Internal-only pages
- Duplicate test content
- Private resources

❌ Don't use for:
- Published public content
- Important landing pages
- Resource pages meant for discovery
```

#### `field_breadcrumb_label`
**Type**: String (255 characters max)  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Custom text for breadcrumb navigation instead of the page title.

**Usage Guidelines**:
- **Length**: Keep under 30 characters for breadcrumbs
- **Context**: Should make sense in navigation hierarchy
- **Clarity**: More specific than page title when needed

**Examples**:
```
Page Title: "Comprehensive Guide to Enterprise AI Implementation Strategies"
Breadcrumb Label: "AI Implementation Guide"

Page Title: "Case Study: How TechCorp Reduced Costs by 40% Using Our Platform"  
Breadcrumb Label: "TechCorp Case Study"
```

---

### Media Fields

#### `field_hero_image`
**Type**: Entity Reference (Media)  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Primary featured image displayed prominently on the page.

**Usage Guidelines**:
- **Dimensions**: Recommended 1920x1080px (16:9 ratio)
- **Format**: WebP preferred, JPG/PNG acceptable
- **Size**: Under 500KB optimized
- **Content**: Should visually represent the page content

**Examples**:
- **Article**: Relevant illustration or photo supporting the topic
- **Case Study**: Client logo, project photo, or results visualization
- **Service Page**: Service illustration or team photo

#### `field_social_image`
**Type**: Entity Reference (Media)  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Specific image optimized for social media sharing (og:image).

**Usage Guidelines**:
- **Dimensions**: 1200x630px (Facebook/LinkedIn) or 1200x675px (Twitter)
- **Format**: PNG or JPG
- **Text**: Include title/branding overlay if needed
- **Contrast**: Ensure text is readable on all backgrounds

**Examples**:
- **Article**: Hero image with article title overlay
- **Resource**: Cover design with download CTA
- **Case Study**: Results/statistics visualization

#### `field_image` (Article-specific)
**Type**: Image  
**Cardinality**: Single  
**Used by**: Article

**Purpose**: Inline content image separate from hero image.

**Usage Guidelines**:
- **Purpose**: Supporting visual content within article body
- **Placement**: Referenced in article body text
- **Alt text**: Descriptive alt text required

---

### Call-to-Action Fields

#### `field_primary_cta`
**Type**: Link  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Primary action you want users to take on the page.

**Usage Guidelines**:
- **Priority**: Most important conversion action
- **Placement**: Prominently displayed, typically above fold
- **Text**: Action-oriented language (Download, Contact, Learn More)
- **Tracking**: Include UTM parameters for tracking

**Examples**:
```
✅ Good CTAs:
- "Download Implementation Guide" → /resources/ai-guide?utm_source=article
- "Schedule Strategy Call" → /contact?utm_campaign=case-study  
- "Start Free Trial" → /trial?utm_medium=landing-page

❌ Poor CTAs:
- "Click Here" 
- Generic "Learn More" without context
- Internal links without tracking
```

#### `field_secondary_cta`
**Type**: Link  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Alternative action for users not ready for the primary CTA.

**Usage Guidelines**:
- **Priority**: Less prominent than primary CTA
- **Purpose**: Nurture/education focused
- **Placement**: Lower priority visual position
- **Complementary**: Should support the primary CTA goal

**Examples**:
```
Primary: "Schedule Demo" 
Secondary: "View Case Studies"

Primary: "Download Whitepaper"
Secondary: "Subscribe to Newsletter"

Primary: "Contact Sales"  
Secondary: "Browse Resources"
```

---

## Content Organization

### `field_category`
**Type**: Entity Reference (Taxonomy Term)  
**Cardinality**: Unlimited  
**Used by**: Article, Resource

**Purpose**: Categorize content for organization, filtering, and navigation.

**Usage Guidelines**:
- Select 1-3 most relevant categories per piece of content
- Use existing taxonomy terms consistently
- Categories should reflect user navigation patterns

### `field_tags`
**Type**: Entity Reference (Taxonomy Term)  
**Cardinality**: Unlimited  
**Used by**: Article, Resource

**Purpose**: Detailed topical tags for content discoverability and related content suggestions.

**Usage Guidelines**:
- Use 5-10 tags per piece of content
- Include both broad topics and specific keywords
- Consider what users might search for

### `field_publish_date`
**Type**: Date  
**Cardinality**: Single  
**Used by**: Article, Resource, Event

**Purpose**: Control when content becomes publicly visible.

**Usage Guidelines**:
- Future dates: Content will be published automatically
- Past dates: Content appears published with that date
- Empty: Uses content creation date

### `field_summary`
**Type**: Text (Long) with Headless Plain AI format  
**Cardinality**: Single  
**Used by**: Article, Resource, Landing Page

**Purpose**: Brief content overview for listings, previews, and meta descriptions fallback.

**Usage Guidelines**:
- **Length**: 150-200 characters optimal
- **Content**: Compelling summary that works standalone
- **Keywords**: Include primary target keywords naturally
- **Action**: End with value proposition when appropriate

### `field_featured`
**Type**: Boolean  
**Cardinality**: Single  
**Used by**: Article, Resource, Event

**Purpose**: Mark content for prominent display in featured content sections.

**Usage Guidelines**:
- Use sparingly (2-4 pieces featured at a time)
- Rotate featured content regularly
- Choose high-quality, representative content

---

## Technical & System Fields

### `field_external_url`
**Type**: Link  
**Cardinality**: Single  
**Used by**: Resource

**Purpose**: Link to external resources (PDFs, external websites, tools) instead of local content.

**Usage Guidelines**:
- Use absolute URLs with protocol (https://)
- Verify links work and remain valid
- Consider using for gated resources, external tools, partner content

### `field_weight`
**Type**: Integer  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Control display order in listings (lower numbers appear first).

**Usage Guidelines**:
- **Default**: 0 (neutral weight)
- **Promote**: Negative numbers (-1, -5, -10)
- **Demote**: Positive numbers (1, 5, 10)
- **Range**: Use consistent increments (5s or 10s)

### `field_override_title`
**Type**: String  
**Cardinality**: Single  
**Used by**: All content types

**Purpose**: Display different title in navigation/listings while keeping original title for SEO.

**Usage Guidelines**:
- Use when title is too long for navigation
- Create shorter, clearer versions for menus
- Maintain meaning and context

### `field_status`
**Type**: List (Text)  
**Cardinality**: Single  
**Used by**: Resource, Event

**Purpose**: Track content lifecycle status beyond published/unpublished.

**Options**:
- **Draft**: Work in progress
- **Review**: Ready for editorial review
- **Approved**: Approved for publication
- **Archive**: Outdated but preserved

---

## Field Usage by Content Type

### Article
**Primary Fields**: `body`, `field_hero_image`, `field_personas`, `field_category`  
**SEO Fields**: `field_meta_description`, `field_seo_title`, `field_social_image`  
**Organization**: `field_summary`, `field_tags`, `field_featured`, `field_publish_date`  
**CTAs**: `field_primary_cta`, `field_secondary_cta`  
**Special**: `field_image` (inline content image)

### Resource
**Primary Fields**: `body`, `field_hero_image`, `field_personas`, `field_external_url`  
**SEO Fields**: `field_meta_description`, `field_seo_title`, `field_social_image`  
**Organization**: `field_summary`, `field_category`, `field_tags`, `field_featured`  
**CTAs**: `field_primary_cta`, `field_secondary_cta`  
**Tracking**: `field_status`

### Basic Page
**Primary Fields**: `body`, `field_hero_image`, `field_personas`  
**SEO Fields**: `field_meta_description`, `field_seo_title`, `field_breadcrumb_label`  
**CTAs**: `field_primary_cta`, `field_secondary_cta`  
**Navigation**: `field_override_title`, `field_weight`

### Landing Page
**Primary Fields**: `body`, `field_hero_image`, `field_personas`, `field_summary`  
**SEO Fields**: `field_meta_description`, `field_seo_title`, `field_social_image`  
**CTAs**: `field_primary_cta`, `field_secondary_cta`  
**Tracking**: All UTM and conversion tracking fields

### Event
**Primary Fields**: `body`, `field_hero_image`, `field_personas`, `field_publish_date`  
**SEO Fields**: `field_meta_description`, `field_seo_title`, `field_social_image`  
**Organization**: `field_featured`, `field_status`  
**CTAs**: `field_primary_cta`, `field_secondary_cta`

---

## Best Practices

### Content Strategy
1. **Persona-First**: Always select appropriate personas before creating content
2. **SEO Optimization**: Complete meta descriptions and SEO titles for all public content
3. **Visual Hierarchy**: Use hero images consistently across content types
4. **Call-to-Action**: Every piece of content should have a clear primary CTA

### Editorial Workflow
1. **Status Tracking**: Use `field_status` to manage editorial workflow
2. **Publishing Schedule**: Set `field_publish_date` for planned content releases
3. **Quality Control**: Review all fields before publishing, especially SEO fields
4. **Content Organization**: Consistently categorize and tag content for discoverability

### Technical Implementation
1. **Image Optimization**: Compress and optimize all media before upload
2. **Link Tracking**: Include UTM parameters in all CTAs for analytics
3. **Accessibility**: Provide alt text for all images, ensure good color contrast
4. **Performance**: Use appropriate text formats - don't over-engineer simple content

### Maintenance
1. **Regular Audits**: Review and update featured content regularly
2. **Link Checking**: Verify external URLs remain valid
3. **Content Refresh**: Update older content to maintain relevance
4. **Analytics Review**: Use CTA performance data to optimize conversion paths

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
