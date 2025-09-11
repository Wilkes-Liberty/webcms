# Content Types Guide for Content Creators

This guide provides detailed information about all content types available in the CMS, their intended purposes, and how to use each field effectively.

## Overview

Our content management system includes 9 content types designed for different purposes:

| Content Type | Purpose | Primary Use Cases |
|--------------|---------|-------------------|
| **Article** | Time-sensitive posts | News, press releases, blog posts, announcements |
| **Basic Page** | Static informational content | About pages, policies, general information |
| **Career** | Job postings | Open positions, job descriptions, applications |
| **Case Study** | Customer success stories | Client testimonials, project showcases |
| **Event** | Webinars and events | Upcoming events, past events, registration |
| **Landing Page** | Marketing campaigns | Conversion-focused pages with components |
| **Person** | Staff profiles | Team member bios, author profiles |
| **Resource** | Downloadable content | eBooks, whitepapers, checklists, guides |
| **Service** | Company offerings | Service descriptions, capabilities |

---

## Common Field Groups

Most content types share these field groups for consistency:

### 🎯 **Content Tab**
- **Title** - The main heading (auto-generated, always required)
- **Body** - Main content using rich text editor
- **Summary/Deck** - Brief description for teasers and SEO

### 🖼️ **Media Tab**
- **Hero Image** - Featured image for the page
- **Social Share Image** - Optimized image for social media sharing

### 🔗 **CTAs Tab**
- **Primary CTA** - Main call-to-action button/link
- **Secondary CTA** - Optional additional action

### 🔍 **SEO Tab**
- **SEO Title Override** - Custom title for search engines
- **Meta Description** - Search result description
- **Canonical URL** - Preferred URL for duplicate content
- **Breadcrumb Label Override** - Custom breadcrumb text
- **Robots: noindex** - Hide from search engines

### ⚙️ **Technical Tab**
- **CDN Cache Tags** - Advanced caching controls
- **Revalidate After** - Cache refresh timing
- **Preview Token** - Secure preview access

### 🏷️ **Classification Tab**
- **Primary Industry** - Target industry audience
- **Personas** - Target user personas for content personalization
- **Tags** - Topic and technology tags
- **Technologies** - Related technologies
- **Compliance** - Regulatory and compliance framework classifications

### 📐 **Layout Tab**
- **Template/Layout** - Page template selection
- **Design Variant** - Visual theme variation
- **Visibility** - Control where content appears

### 🧪 **Testing & Analytics Tab**
- **A/B Variant** - A/B testing variant designation
- **UTM Bucket/Campaign** - Campaign tracking for analytics

### 🔧 **Advanced Tab**
- **Parent Content** - Hierarchical content relationships
- **Show Table of Contents** - Auto-generate page TOC
- **Content Review Date** - Track when content was last reviewed

---

# Content Type Details

## 1. 📰 Article

**Purpose**: Time-sensitive posts that surface in listings and feeds
**Best for**: News, press releases, blog posts, announcements

### When to Use Articles
- ✅ News announcements and press releases
- ✅ Blog posts and thought leadership
- ✅ Time-sensitive updates
- ✅ Content that should appear in RSS feeds
- ❌ Static pages that rarely change
- ❌ Landing pages for campaigns

### Key Fields Specific to Articles

#### **News Category** (`field_news_category`)
- **Purpose**: Categorize articles for filtering and organization
- **Required**: No
- **Usage**: Select appropriate category (News, Press Release, Blog, etc.)

#### **Tags** (`field_tags`)
- **Purpose**: Free-form tagging for flexible categorization
- **Required**: No  
- **Usage**: Enter comma-separated tags: "Amsterdam, Mexico City, \"Cleveland, Ohio\""
- **Tip**: Use quotes around multi-word tags

#### **Estimated Read Time** (`field_read_time`)
- **Purpose**: Help readers estimate time commitment
- **Required**: No
- **Usage**: Enter whole minutes only (3, 5, 7)

### SEO Best Practices for Articles
- Keep titles under 60 characters
- Write compelling meta descriptions (150-160 characters)
- Use the summary field for social sharing and teasers
- Add relevant tags for discoverability

---

## 2. 📄 Basic Page  

**Purpose**: Static informational content rendered headlessly
**Best for**: About pages, policies, general company information

### When to Use Basic Pages
- ✅ Company information and policies
- ✅ Static content that rarely changes
- ✅ Support and help documentation
- ✅ Legal pages and terms
- ❌ Time-sensitive announcements
- ❌ Content that needs complex layouts

### Content Strategy for Basic Pages
- Focus on clear, scannable content structure
- Use headings (H2, H3) to organize information
- Keep paragraphs short and focused
- Include relevant internal links

---

## 3. 💼 Career

**Purpose**: Job postings with application details
**Best for**: Open positions, job descriptions, career opportunities

### When to Use Career Pages
- ✅ Active job postings
- ✅ Detailed role descriptions
- ✅ Department-specific opportunities
- ❌ General career information (use Basic Page)
- ❌ Company culture content (use Article or Basic Page)

### Career-Specific Fields

#### **Apply URL** (`field_apply_url`)
- **Purpose**: Direct link to external application system
- **Required**: No
- **Usage**: Full URL to application form or job board listing

#### **Location** (`field_job_location`)
- **Purpose**: Work location for the position
- **Required**: No
- **Usage**: "Chicago, IL", "Remote", "Hybrid - New York"

#### **Employment Type** (`field_job_type`)
- **Purpose**: Type of employment arrangement
- **Options**: Full-time, Part-time, Contract, Temporary, Internship

#### **Department** (`field_department`)
- **Purpose**: Organizing department for the role
- **Required**: No
- **Usage**: Select from predefined departments

#### **Seniority** (`field_seniority`)
- **Purpose**: Experience level required
- **Options**: Entry Level, Mid-Level, Senior, Lead, Executive

### Writing Job Descriptions
1. Start with a compelling summary
2. List key responsibilities clearly
3. Specify required qualifications
4. Include preferred qualifications separately
5. Mention benefits and company culture
6. End with clear application instructions

---

## 4. 📈 Case Study

**Purpose**: Customer success stories showcasing outcomes
**Best for**: Client testimonials, project showcases, results documentation

### When to Use Case Studies
- ✅ Successful client projects
- ✅ Before/after transformations
- ✅ Quantified business results
- ✅ Industry-specific examples
- ❌ General service descriptions
- ❌ Theoretical examples

### Case Study Structure
1. **Challenge**: What problem did the client face?
2. **Solution**: How did you address it?
3. **Results**: What outcomes were achieved?
4. **Metrics**: Include specific numbers when possible

### Related Content Strategy
- Link to relevant services
- Tag appropriate industries and technologies
- Include related case studies for cross-promotion

---

## 5. 📅 Event

**Purpose**: Webinars and events with scheduling details
**Best for**: Upcoming events, past events, registration pages

### Event-Specific Fields

#### **Event Date** (`field_event_date`)
- **Purpose**: Start and end date/time with timezone support
- **Required**: No
- **Features**: Supports all-day events and multiple timezones

#### **Event Type** (`field_event_type`)
- **Purpose**: Categorize the type of event
- **Options**: Webinar, Conference, Workshop, Networking, etc.

### Event Content Best Practices
- Include agenda or schedule details
- Mention speakers and their credentials
- Specify registration requirements
- Provide clear date, time, and location
- Include technical requirements for virtual events

---

## 6. 🎯 Landing Page

**Purpose**: Flexible marketing pages for campaigns
**Best for**: Conversion-focused pages, campaign-specific content

### When to Use Landing Pages
- ✅ Marketing campaigns
- ✅ Lead generation
- ✅ Product launches
- ✅ A/B testing scenarios
- ❌ General information pages
- ❌ Long-form content

### Landing Page Components

#### **Components** (`field_components`)
- **Purpose**: Add and reorder content sections using paragraphs
- **Available Components**:
  - Hero sections with CTAs
  - Feature lists
  - Testimonials
  - FAQ sections
  - Text blocks
  - Image galleries

### Landing Page Strategy
- Keep focused on single conversion goal
- Use compelling headlines and subheadings
- Include social proof and testimonials
- Minimize navigation distractions
- Test different variants

---

## 7. 👤 Person

**Purpose**: Staff profiles for team pages and bylines
**Best for**: Team member bios, author profiles, speaker information

### Person-Specific Fields

#### **Bio** (`field_bio`)
- **Purpose**: Professional biography
- **Text Format**: Headless Safe (structured content)
- **Usage**: Write in third person, focus on professional achievements

#### **Job Title** (`field_job_title`)
- **Purpose**: Current position title
- **Required**: No
- **Usage**: Use official title as it appears in company directory

#### **Photo** (`field_photo`)
- **Purpose**: Professional headshot
- **Requirements**: Minimum 800×800 pixels, JPG/PNG
- **Best Practice**: Use high-quality, professional photos with focal points set

#### **Department** (`field_department`)
- **Purpose**: Organizational department
- **Usage**: Select appropriate department for team organization

#### **Expertise** (`field_expertise`)
- **Purpose**: Areas of professional expertise
- **Usage**: Tag relevant topics, services, industries, and technologies

#### **Social Links**:
- **LinkedIn** (`field_linkedin`): Full LinkedIn profile URL
- **GitHub** (`field_github`): GitHub profile for developers
- **Website** (`field_website`): Personal or portfolio website

#### **Show in Directory** (`field_show_in_directory`)
- **Purpose**: Control public visibility
- **Usage**: Enable to show on public team pages

### Writing Professional Bios
1. Use third person perspective
2. Lead with current role and key achievements
3. Include relevant experience and education
4. Mention notable projects or recognitions
5. Keep concise but informative
6. End with personal touch if appropriate

---

## 8. 📋 Resource

**Purpose**: Downloadable or gated content assets
**Best for**: eBooks, whitepapers, checklists, templates

### Resource-Specific Fields

#### **Resource Type** (`field_resource_type`)
- **Purpose**: Categorize the type of resource
- **Options**: eBook, Whitepaper, Checklist, Template, Guide, Report

### Resource Content Strategy
- Create compelling titles that indicate value
- Write detailed descriptions of what readers will learn
- Include clear benefit statements
- Use gated forms for lead generation
- Provide immediate access after form submission

---

## 9. 🔧 Service

**Purpose**: Company offerings and capabilities
**Best for**: Service descriptions, capability pages

### Service Content Best Practices
- Focus on client benefits, not just features
- Include specific deliverables and outcomes
- Use case studies and testimonials
- Link to related technologies and solutions
- Provide clear next steps for prospects

---

# Additional Field Documentation

The following fields are available across multiple content types but require specific guidance:
---

## 🎭 Personas Field

**Purpose**: Target specific user personas for content personalization and audience segmentation  
**Available on**: Article, Basic Page, Event, Landing Page, Resource  
**Field Type**: Taxonomy reference (multiple values allowed)  
**Vocabulary**: Persona

### What Are Personas?

Personas represent your target audience segments - fictional characters that embody the characteristics, needs, and behaviors of your real users. The Personas field lets you tag content for specific audience types, enabling:

- **Content Personalization**: Show relevant content based on user persona
- **Analytics Segmentation**: Track content performance by audience type
- **Content Strategy**: Plan content that addresses specific user needs
- **Marketing Automation**: Trigger persona-specific campaigns

### When to Use Personas Tagging

✅ **Use personas for**:
- Content targeting specific user types (decision-makers, technical users, end users)
- Marketing materials designed for particular audience segments
- Educational content tailored to experience levels
- Industry-specific content targeting role-based audiences
- A/B testing content variations for different user types

❌ **Don't use personas for**:
- General informational content applicable to all audiences
- Internal documentation or administrative pages
- Content without clear audience targeting intent
- Generic company information (unless specifically targeted)

### Common Persona Examples

#### Business/Role-Based Personas
- **Decision Maker** - C-level executives, managers with purchasing authority
- **Technical Implementer** - Developers, system administrators, IT professionals
- **End User** - Daily users of products/services, operational staff
- **Evaluator** - Analysts, consultants who research and recommend solutions
- **Procurement** - Purchasing departments, contract negotiators

#### Experience Level Personas  
- **Beginner** - New to the topic/industry, needs foundational information
- **Intermediate** - Some experience, looking to expand knowledge/capabilities
- **Advanced** - Expert-level, seeks specialized or cutting-edge information

#### Industry/Sector Personas
- **Healthcare Professional** - Doctors, nurses, healthcare administrators
- **Financial Services** - Bankers, insurance professionals, fintech companies
- **Manufacturing** - Plant managers, operations directors, supply chain professionals
- **Government** - Public sector employees, contractors, policy makers
- **Education** - Teachers, administrators, education technology users

### Content Creation Examples

#### Marketing Landing Page
```
Title: "Cloud Security Solutions for Enterprise"
Personas: Decision Maker, Technical Implementer
Usage: Page addresses both business value (for executives) and technical specifications (for IT teams)
```

#### Educational Article
```
Title: "Getting Started with API Integration"
Personas: Beginner, Technical Implementer  
Usage: Introductory content for developers new to API integration
```

#### Industry Case Study
```
Title: "Healthcare Data Compliance Success Story"
Personas: Healthcare Professional, Decision Maker
Usage: Targets healthcare decision-makers with relevant compliance concerns
```

#### Resource Download
```
Title: "Security Audit Checklist"
Personas: Technical Implementer, Advanced
Usage: Detailed technical resource for experienced security professionals
```

### Best Practices for Persona Tagging

1. **Be Specific**: Choose personas that genuinely reflect your content's intended audience
2. **Use Multiple When Appropriate**: Content can serve multiple personas if it addresses different needs
3. **Consider the Full Journey**: Tag content for personas at different stages (awareness, evaluation, decision)
4. **Keep Personas Updated**: Review and refresh persona definitions as your audience evolves
5. **Align with Marketing**: Coordinate persona usage with your marketing team's definitions
6. **Test and Measure**: Use analytics to validate persona effectiveness

### Integration with Other Fields

**Personas work well with**:
- **Industry** - Combine persona + industry for precise targeting (e.g., "Decision Maker" + "Healthcare")
- **Primary Capability** - Match personas to relevant business capabilities
- **Campaign** - Track persona-specific campaign performance
- **A/B Variant** - Test different messaging for different personas
- **Compliance** - Ensure persona-targeted content meets relevant regulations

### Content Strategy Applications

#### Audience Segmentation
- Create content tracks for each major persona
- Develop persona-specific content calendars
- Plan conversion paths tailored to persona needs

#### Personalization
- Show relevant content based on user persona preferences
- Customize CTAs and messaging for different audiences
- Adapt content depth and technical level

#### Analytics & Optimization
- Track engagement rates by persona
- Identify content gaps for underserved personas
- Optimize conversion paths for each audience segment

---

## 🌅 Media Fields Documentation

### Hero Image (`field_hero_image`)

**Purpose**: Featured image displayed prominently on the page  
**Available on**: All content types except Person  
**Field Type**: Media reference (image)  
**Requirements**: Minimum 1200x630 pixels, JPG/PNG formats

#### When to Use Hero Images
- ✅ Landing pages and marketing content
- ✅ Articles and case studies for visual impact
- ✅ Event pages to showcase the event
- ❌ Administrative or policy pages
- ❌ Content where images don't add value

#### Hero Image Best Practices
1. **Size**: Use high-resolution images (1200x630 minimum, 1920x1080 ideal)
2. **Focal Point**: Always set focal points for responsive cropping
3. **Brand Alignment**: Ensure images align with brand guidelines
4. **Text Overlay**: Consider text overlay space in image composition
5. **Loading**: Optimize file size for fast loading while maintaining quality

### Social Image (`field_social_image`)  

**Purpose**: Optimized image for social media sharing (Facebook, Twitter, LinkedIn)  
**Available on**: All content types except Person  
**Field Type**: Media reference (image)  
**Optimal Size**: 1200x630 pixels (Facebook/LinkedIn), 1200x675 (Twitter)

#### Social Image Strategy
- Use different image than hero image when beneficial
- Include key messaging or branding elements
- Consider how image appears in social media previews
- Test social previews using platform debugging tools

### Article Image (`field_image`)

**Purpose**: Inline images within article content (separate from hero image)  
**Available on**: Article content type only  
**Field Type**: Media reference (image, multiple values)  
**Usage**: Supporting visuals, diagrams, screenshots

---

## 🔍 SEO Fields Documentation

### SEO Title Override (`field_seo_title`)

**Purpose**: Custom title for search engine results (overrides default title)  
**Available on**: All content types except Person  
**Field Type**: Text string (60 characters recommended)  
**Usage**: When you need different title for SEO than the displayed page title

#### SEO Title Best Practices
1. **Length**: Keep under 60 characters for full display in search results
2. **Keywords**: Include primary keywords naturally
3. **Uniqueness**: Each page should have unique SEO title
4. **Clarity**: Make titles clear and compelling for searchers
5. **Brand**: Consider including brand name for brand recognition

### Meta Description (`field_meta_description`)

**Purpose**: Description shown in search engine results  
**Available on**: All content types except Person  
**Field Type**: Text string (155 characters recommended)  
**Usage**: Compelling summaries to improve click-through rates

#### Meta Description Guidelines
1. **Length**: 150-155 characters for optimal display
2. **Action-Oriented**: Include calls-to-action when appropriate
3. **Value Proposition**: Clearly state what users will gain
4. **Keywords**: Include relevant keywords naturally
5. **Uniqueness**: Every page needs unique meta description

### Canonical URL (`field_canonical`)

**Purpose**: Specify preferred URL for duplicate or similar content  
**Available on**: All content types except Person  
**Field Type**: Link field  
**Usage**: SEO management for content appearing at multiple URLs

#### When to Use Canonical URLs
- Content accessible through multiple URL paths
- Duplicate content prevention
- Campaign landing pages with tracking parameters
- Syndicated or republished content

### Breadcrumb Label Override (`field_breadcrumb_label`) 

**Purpose**: Custom text for breadcrumb navigation (overrides page title)  
**Available on**: All content types except Person  
**Field Type**: Text string (short)  
**Usage**: When page title is too long for breadcrumb display

### Search Engine Visibility (`field_noindex`)

**Purpose**: Hide pages from search engine indexing  
**Available on**: All content types except Person  
**Field Type**: Boolean checkbox  
**Usage**: Draft content, private pages, duplicate content

#### Use Noindex For
- ✅ Draft or work-in-progress content
- ✅ Thank you pages and confirmation pages  
- ✅ Internal-only documentation
- ✅ Test pages and staging content
- ❌ Regular public content (defeats SEO purpose)

---

## 🎯 Call-to-Action Fields

### Primary CTA (`field_primary_cta`)

**Purpose**: Main action you want users to take on the page  
**Available on**: All content types except Person  
**Field Type**: Link field with URL and display text  
**Usage**: Drive conversions and user engagement

#### Primary CTA Best Practices
1. **Action-Oriented**: Use verbs ("Download", "Request Demo", "Get Started")
2. **Value-Clear**: Users should understand what they'll get
3. **Prominent Placement**: Position prominently on the page
4. **Single Focus**: One primary action per page
5. **A/B Testing**: Test different CTA text and positioning

#### Primary CTA Examples by Content Type
- **Landing Page**: "Request Demo", "Start Free Trial", "Download Guide"
- **Article**: "Read Related Article", "Contact Expert", "Learn More"
- **Case Study**: "Request Similar Results", "Schedule Consultation"
- **Resource**: "Download Now", "Access Full Report"
- **Service**: "Get Quote", "Schedule Consultation", "View Pricing"

### Secondary CTA (`field_secondary_cta`)

**Purpose**: Alternative action for users not ready for primary conversion  
**Available on**: All content types except Person  
**Field Type**: Link field with URL and display text  
**Usage**: Provide softer engagement options

#### Secondary CTA Strategy
- **Lower Commitment**: "Learn More", "View Examples", "Read FAQ"
- **Alternative Path**: Different way to engage with your content/services
- **Nurturing**: Move users further down the conversion funnel
- **Information**: Provide additional details or related content

---

## 🏷️ Additional Classification Fields

### Industry (`field_industry`)

**Purpose**: Target specific industry verticals  
**Available on**: Article, Basic Page, Case Study, Event, Landing Page, Resource  
**Field Type**: Taxonomy reference (multiple values)  
**Usage**: Industry-specific content targeting and organization

#### Common Industries
- Healthcare & Life Sciences
- Financial Services & Banking  
- Manufacturing & Industrial
- Government & Public Sector
- Education & Research
- Technology & Software
- Retail & E-commerce
- Energy & Utilities

### Technologies (`field_technologies`)

**Purpose**: Tag content with relevant technology stack elements  
**Available on**: All content types except Person  
**Field Type**: Taxonomy reference (multiple values)  
**Usage**: Technical content categorization and filtering

#### Technology Examples
- **Platforms**: AWS, Microsoft Azure, Google Cloud
- **Languages**: JavaScript, Python, Java, C#
- **Frameworks**: React, Angular, .NET, Spring
- **Databases**: PostgreSQL, MongoDB, MySQL
- **Tools**: Docker, Kubernetes, Jenkins

### Related Content (`field_related`)

**Purpose**: Link to related content for cross-promotion and navigation  
**Available on**: All content types except Person  
**Field Type**: Entity reference (multiple content items)  
**Usage**: Improve content discoverability and user engagement

#### Related Content Strategy
1. **Relevance**: Choose genuinely related content
2. **Content Mix**: Include different content types (articles, case studies, resources)
3. **User Journey**: Consider user's next logical step
4. **Fresh Content**: Regularly update related content selections
5. **Performance**: Monitor click-through rates on related content

### Content Summary (`field_summary`)

**Purpose**: Brief summary or excerpt of the main content  
**Available on**: All content types except Person  
**Field Type**: Text field with Headless Plain AI format  
**Usage**: SEO meta descriptions, content teasers, social sharing descriptions

#### Summary Field Best Practices
1. **Length**: 150-200 characters optimal for most uses
2. **Value-Focused**: Clearly state what users will gain from the content
3. **Keyword-Rich**: Include relevant keywords naturally
4. **Standalone**: Should make sense without reading the full content
5. **Compelling**: Encourage users to read the full content

#### Summary Examples by Content Type
- **Article**: "Learn the top 5 strategies for improving API security, including authentication best practices and common vulnerability prevention."
- **Case Study**: "Discover how Company X reduced data processing time by 60% using cloud-native architecture and automated workflows."
- **Landing Page**: "Request a demo of our enterprise security platform and see how we protect sensitive data for Fortune 500 companies."
- **Resource**: "Download our comprehensive guide to GDPR compliance, featuring checklists, templates, and real-world implementation examples."

### Main Content (`body`)

**Purpose**: Primary content of the page or article  
**Available on**: All content types  
**Field Type**: Text field with Headless Clean format  
**Features**: Full rich text editing, AI writing assistance, media embedding

#### Content Creation Best Practices
1. **Structure**: Use heading hierarchy (H2, H3, H4) for organization
2. **Scannable**: Break up text with bullet points, numbered lists, and short paragraphs
3. **Media Integration**: Include relevant images, videos, or diagrams
4. **Internal Links**: Link to related content and resources
5. **Call-to-Actions**: Include relevant CTAs within the content flow
6. **AI Assistance**: Leverage AI features for content improvement and editing

#### Content Length Guidelines by Type
- **Article**: 800-2000 words for comprehensive coverage
- **Basic Page**: 300-800 words for clear information
- **Landing Page**: 400-1200 words focused on conversion
- **Case Study**: 600-1500 words with detailed results
- **Resource**: Variable based on resource type and depth

---

## 🛡️ Compliance Field
**Purpose**: Tag content with relevant regulatory and compliance frameworks
**Available on**: Articles, Basic Pages, Case Studies, Landing Pages, Resources
**Field Type**: Taxonomy reference (multiple values allowed)

### When to Use Compliance Tagging
- ✅ Content discussing regulatory requirements
- ✅ Industry-specific compliance topics (healthcare, finance, etc.)
- ✅ Content requiring audit trails
- ✅ Materials referencing specific frameworks or standards
- ❌ General informational content without regulatory implications

### Common Compliance Framework Examples
- **GDPR** - General Data Protection Regulation
- **CCPA** - California Consumer Privacy Act
- **HIPAA** - Health Insurance Portability and Accountability Act
- **SOC 2** - Service Organization Control 2
- **PCI DSS** - Payment Card Industry Data Security Standard
- **ISO 27001** - Information Security Management
- **NIST** - National Institute of Standards and Technology
- **FedRAMP** - Federal Risk and Authorization Management Program

### Content Creation Examples

#### Healthcare Client Case Study
```
Compliance: HIPAA, HITECH Act
Example: "Our client needed to ensure patient data handling met HIPAA requirements while implementing new telehealth capabilities."
```

#### Financial Services Article
```
Compliance: PCI DSS, SOX, GDPR
Example: "This payment processing solution addresses PCI DSS Level 1 compliance requirements for enterprise merchants."
```

#### Government Solution Resource
```
Compliance: FedRAMP, NIST Cybersecurity Framework
Example: "Download our FedRAMP compliance checklist to prepare your cloud services for government deployment."
```

### Governance Best Practices
1. **Assign compliance tags during content creation** - Don't retroactively tag unless doing a comprehensive audit
2. **Use consistent terminology** - Stick to official framework names and acronyms
3. **Update when standards change** - Review compliance tags when frameworks are updated
4. **Consider legal review** - Have compliance-tagged content reviewed by legal team if making specific claims
5. **Track for auditing** - Use compliance tags to generate audit reports when needed

---

## 🧪 A/B Testing and Campaign Fields

### A/B Variant Field (`field_ab_variant`)

**Purpose**: Designate content variations for A/B testing
**Available on**: All content types except Person
**Field Type**: Select list (single value)
**Options**: Control, Variant A, Variant B

#### When to Use A/B Testing
- ✅ Landing pages for marketing campaigns
- ✅ High-traffic pages where conversion matters
- ✅ Testing different headlines, CTAs, or layouts
- ✅ Email newsletter content variations
- ❌ Static informational pages with low traffic
- ❌ Content that changes frequently

#### A/B Testing Workflow Example

**Scenario**: Testing different approaches for a product landing page

1. **Create Control Version**
   - Set A/B Variant: "Control"
   - UTM Bucket/Campaign: "product-launch-control"
   - Use existing headline and CTA approach

2. **Create Variant A**
   - Duplicate the control page
   - Set A/B Variant: "Variant A" 
   - UTM Bucket/Campaign: "product-launch-variant-a"
   - Test: Benefit-focused headline

3. **Create Variant B**
   - Duplicate the control page
   - Set A/B Variant: "Variant B"
   - UTM Bucket/Campaign: "product-launch-variant-b"
   - Test: Feature-focused headline

4. **Monitor and Analyze**
   - Track conversion rates through analytics
   - Use UTM campaigns to segment traffic
   - Run test for statistically significant period
   - Promote winning variant

### UTM Bucket/Campaign Field (`field_campaign`)

**Purpose**: Tag content for analytics tracking and campaign attribution
**Available on**: All content types except Person
**Field Type**: Text string (255 characters max)

#### Campaign Naming Best Practices
- Use lowercase and hyphens: `product-launch-q4-2024`
- Include time periods for limited campaigns: `webinar-series-march-2024`
- Specify channels: `email-newsletter-weekly`, `social-media-linkedin`
- Be descriptive but concise: `case-study-manufacturing-roi`

#### Campaign Integration Examples

**Email Marketing Campaign**
```
Campaign: email-newsletter-q4-2024
Usage: Track performance of content featured in quarterly newsletter
```

**Social Media Campaign**
```
Campaign: social-thought-leadership-linkedin
Usage: Track engagement from LinkedIn thought leadership posts
```

**Event-Driven Campaign**
```
Campaign: tradeshow-booth-manufacturing-2024
Usage: Track leads generated from tradeshow materials
```

---

## 📐 Layout and Design Fields

### Template/Layout Field (`field_template`)

**Purpose**: Control page layout and structure
**Available on**: All content types except Person
**Options**: Default, Wide, No Sidebar, Landing

#### Template Selection Guide
- **Default**: Standard page layout with sidebar for navigation
- **Wide**: Full-width layout for content-heavy pages
- **No Sidebar**: Clean layout without navigation sidebar
- **Landing**: Conversion-optimized layout for campaigns

### Design Variant Field (`field_theme_variant`)

**Purpose**: Apply visual theme variations
**Available on**: All content types except Person  
**Options**: Light, Dark, Brand A

#### Design Variant Use Cases
- **Light**: Standard light theme (default)
- **Dark**: High-contrast dark theme for technical content
- **Brand A**: Alternative brand treatment for special campaigns

### Visibility Field (`field_visibility`)

**Purpose**: Control content access and discoverability
**Available on**: All content types except Person
**Options**: Public, Login required, Role: Partner

#### Visibility Settings Guide
- **Public**: Available to all website visitors
- **Login required**: Only authenticated users can view
- **Role: Partner**: Restricted to users with partner role

---

## 🔗 Advanced Content Relationships

### Parent Content Field (`field_parent`)

**Purpose**: Create hierarchical content relationships
**Available on**: All content types except Person
**Field Type**: Entity reference to other content

#### Hierarchical Content Examples
- **Service > Case Study**: Link case studies to related services
- **Resource Series**: Connect related whitepapers or guides
- **Campaign Landing Pages**: Group related campaign materials

### Show Table of Contents (`field_show_toc`)

**Purpose**: Auto-generate navigational table of contents
**Available on**: All content types except Person
**Field Type**: Boolean (checkbox)

#### When to Enable TOC
- ✅ Long-form articles with multiple sections
- ✅ Technical documentation
- ✅ Comprehensive guides and resources
- ❌ Short articles or basic pages
- ❌ Landing pages focused on conversion

### Content Review Date (`field_reviewed_on`)

**Purpose**: Track content freshness and review cycles
**Available on**: All content types except Person
**Field Type**: Date field

#### Content Review Best Practices
1. **Set review dates** when content is created or significantly updated
2. **Use for content audits** to identify stale content needing updates
3. **Coordinate with subject matter experts** for technical content reviews
4. **Update regularly** for compliance-sensitive content

---

## 🏢 Business Classification Fields

These taxonomy fields help categorize content according to business capabilities and solutions:

### Primary Capability (`field_primary_capability`)

**Purpose**: Tag content with the main business capability it addresses
**Available on**: Most content types (varies by business focus)
**Field Type**: Taxonomy reference (single value)

#### Examples
- Data Analytics
- Cloud Infrastructure 
- Cybersecurity
- Digital Transformation
- Process Automation

### Solutions (`field_solutions`)

**Purpose**: Associate content with specific solution offerings
**Available on**: Most content types
**Field Type**: Taxonomy reference (multiple values allowed)

#### Examples
- CRM Integration
- E-commerce Platform
- Business Intelligence Dashboard
- Mobile App Development
- API Management

### General Taxonomy/Tags (`field_taxonomy`)

**Purpose**: Broad topic and technology categorization 
**Available on**: Most content types
**Field Type**: Taxonomy reference (multiple values allowed)
**Vocabularies**: Topics, Tech Stack

#### Topic Examples
- Artificial Intelligence
- Machine Learning
- DevOps
- User Experience
- Data Privacy

#### Tech Stack Examples  
- React
- Node.js
- AWS
- Kubernetes
- PostgreSQL

---

## ⚙️ Technical and System Fields

### CDN Cache Tags (`field_cache_tags`)

**Purpose**: Advanced CDN caching control for high-performance content delivery
**Available on**: All content types except Person
**Field Type**: Text string (comma-separated)

⚠️ **Advanced Field**: Coordinate with platform team before use

#### Guidelines
- Limit to 10 tags maximum
- Use for frequently accessed content requiring custom cache behavior
- Examples: `homepage,featured-content,product-updates`

### Revalidate After (`field_revalidate_ttl`)

**Purpose**: Override default CDN cache expiration timing
**Available on**: All content types except Person  
**Field Type**: Integer (seconds)
**Range**: 300-3600 seconds typically

#### When to Use
- Time-sensitive content (earnings reports, press releases)
- Frequently updated pages
- Campaign landing pages with limited runs
- **Default value (0)**: Use system default caching

#### Common Values
- **300 seconds (5 min)**: Breaking news, live events
- **900 seconds (15 min)**: Press releases, announcements  
- **3600 seconds (1 hour)**: Campaign pages, limited-time offers

### Preview Token (`field_preview_token`)

**Purpose**: Secure token for content previews before publication
**Available on**: All content types except Person
**Field Type**: Text string

🔒 **Security Note**: Never share preview tokens externally

#### Use Cases
- Client review of draft content
- Internal stakeholder approval workflows
- Testing content before publication
- System-generated, do not manually edit

---

## 👤 Person-Specific Fields

### Associated User (`field_user`)

**Purpose**: Link Person content to a user account for author attribution
**Available on**: Person content type only
**Field Type**: User account reference

#### When to Use
- Staff members who create content
- Authors who need byline attribution
- Team members with system accounts
- **Privacy Note**: Not displayed publicly, used for internal mapping only

#### Author Mapping Examples
```
Person: "John Smith, Senior Developer"
User Account: john.smith@company.com
Result: Articles by John show proper attribution
```

---

# Field Type Reference

## Text Fields

### **Rich Text (Body)**
- **Editor**: Full CKEditor with AI assistance
- **Format**: Headless Clean (safe HTML with rich editing)
- **Features**: Headings, lists, links, media embedding, AI writing assistance

### **Plain Text (Summary/Deck)**
- **Editor**: Simple textarea with AI assistance
- **Format**: Headless Plain AI (minimal HTML, AI-powered)
- **Best for**: Descriptions, meta content, teasers

### **String Fields**
- **Usage**: Short text without formatting
- **Examples**: Titles, labels, URLs

## Reference Fields

### **Entity References**
- **Purpose**: Link to other content or taxonomy terms
- **Usage**: Select existing items or create new ones
- **Examples**: Related content, categories, tags

### **Media References**
- **Purpose**: Add images, videos, documents
- **Usage**: Upload new files or select from media library
- **Best Practice**: Always set focal points for images

## Date/Time Fields

### **Standard Dates**
- **Purpose**: Simple date selection
- **Usage**: Publication dates, review dates

### **Smart Dates**
- **Purpose**: Events with start/end times and timezones
- **Features**: All-day events, recurring events, timezone handling

---

# Content Creation Workflow

## Before You Start
1. Determine the appropriate content type
2. Gather all necessary assets (images, links, etc.)
3. Plan your content structure and outline
4. Identify target audience and goals

## During Creation
1. Fill in required fields first
2. Use AI assistance for writing and editing
3. Optimize for SEO (title, meta, summary)
4. Add relevant classifications and tags
5. Include appropriate media and CTAs

## Before Publishing
1. Preview content on different devices
2. Check all links and references
3. Verify SEO elements are complete
4. Ensure proper categorization
5. Test any forms or interactive elements

## After Publishing
1. Monitor performance metrics
2. Update content as needed
3. Check for broken links periodically
4. Review and refresh content regularly

---

# SEO Best Practices

## Title Optimization
- Keep under 60 characters
- Include primary keywords
- Make it compelling and clickable
- Avoid keyword stuffing

## Meta Descriptions
- Write 150-160 characters
- Include a call to action
- Summarize page value proposition
- Use sentence case formatting

## Content Structure
- Use heading hierarchy (H1 → H2 → H3)
- Keep paragraphs short and scannable
- Include relevant internal links
- Optimize images with alt text

## Technical SEO
- Set canonical URLs for duplicate content
- Use noindex for private/duplicate pages
- Configure proper breadcrumbs
- Optimize page loading speed

---

# Common Workflows

## Publishing a News Article
1. Choose Article content type
2. Write compelling headline and summary
3. Add hero image and set focal point
4. Select appropriate news category
5. Tag with relevant topics/technologies
6. Set publication date and author
7. Preview and publish

## Creating a Landing Page
1. Choose Landing Page content type
2. Plan conversion flow and CTAs
3. Add components in logical order
4. Configure A/B testing if needed
5. Set up campaign tracking
6. Test all forms and links
7. Launch and monitor performance

## Adding Team Members
1. Choose Person content type
2. Upload professional headshot
3. Write third-person bio
4. Add social media links
5. Set department and expertise
6. Enable directory visibility
7. Link to user account if needed

---

# Troubleshooting

## Common Issues

### **Content Not Appearing**
- Check publication status
- Verify date settings
- Review visibility settings
- Check user permissions

### **Images Not Loading**
- Verify file format (JPG/PNG)
- Check file size limits
- Ensure focal points are set
- Clear cache after updates

### **SEO Issues**
- Complete all SEO fields
- Check canonical URLs
- Review noindex settings
- Validate structured data

### **Form Problems**
- Test all form submissions
- Check required field settings
- Verify email notifications
- Review spam filtering

---

# Getting Help

## Support Resources
- **Documentation**: Internal knowledge base
- **Training**: Regular content creator sessions  
- **Technical Support**: Platform team for technical issues
- **Content Strategy**: Editorial team for content planning

## Best Practices Community
- Share successful content examples
- Collaborate on content planning
- Regular feedback and improvement sessions
- Stay updated on new features and workflows

Remember: The goal is creating valuable, accessible content that serves our audience while supporting business objectives. When in doubt, focus on user value and clear communication.
