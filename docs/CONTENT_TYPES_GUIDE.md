# Content Types Guide for Content Creators

**Updated:** March 2026
**Version:** 2.0 — Mission-Focused Architecture

This guide is the official reference for all content creators at Wilkes Liberty. It defines all content types, their strategic purpose, and detailed field usage to ensure consistent, high-quality, mission-aligned content across the platform.

---

## Overview

The CMS includes 11 content types supporting both marketing/operational needs and our core product and service offerings.

| Content Type | Purpose | Primary Use Cases |
|---|---|---|
| **Article** | Time-sensitive posts | News, press releases, blog posts, announcements |
| **Basic Page** | Static informational content | About pages, policies, legal, general information |
| **Career** | Job postings | Open positions and career opportunities |
| **Case Study** | Customer success stories | Client testimonials and project showcases |
| **Event** | Webinars and events | Upcoming/past events and registration |
| **Landing Page** | Marketing campaigns | Lead generation and campaign-specific pages |
| **Person** | Staff profiles | Team bios and author profiles |
| **Resource** | Downloadable/gated content | Whitepapers, eBooks, guides, reports |
| **Service** | Consulting and implementation offerings | Professional services and custom solutions |
| **Product** *(new)* | Self-deployable sovereign technology platforms | Infrastructure platforms, CMS, Search, Identity, Data, Observability |
| **Solution** *(new)* | Branded packages bundling Products + Services | Mission-aligned offerings (e.g., "Sovereign Mission Edge for Tactical Operations") — analogous to GDIT Digital Accelerators or Palantir Offerings |

---

## Common Field Groups

All content types share these core field groups. New tabs added in v2.0 are marked accordingly.

### 🎯 Content Tab
- **Title** — The main heading (auto-generated, always required)
- **Body** — Main content using the rich text editor
- **Summary/Deck** — Brief description for teasers and SEO

### 🖼️ Media Tab
- **Hero Image** — Featured image for the page
- **Social Share Image** — Optimized image for social media

### 🔗 CTAs Tab
- **Primary CTA** — Main call-to-action button/link
- **Secondary CTA** — Optional additional action

### 🔍 SEO Tab
- **SEO Title Override** — Custom title for search engines
- **Meta Description** — Search result description
- **Canonical URL** — Preferred URL for duplicate content
- **Breadcrumb Label Override** — Custom breadcrumb text
- **Robots: noindex** — Hide from search engines

### 🎖️ Mission Impact Tab *(new in v2.0)*
- **Mission Impact** (`field_mission_impact`) — Formatted long text. Describes how the offering enhances, streamlines, or facilitates the client's mission. **Required for all Product and Service pages.**
- **Defense & Government Relevance** (`field_defense_relevance`) — Formatted text. Specific value proposition for defense contractors and government organizations.

### ⚡ Key Capabilities Tab *(new in v2.0)*
- **Key Capabilities** (`field_key_capabilities`) — Paragraphs field (recommended) or multi-value text. Structured list of primary capabilities. **Required for Product pages; strongly recommended for Service pages.**

### 🚀 Deployment & Sovereignty Tab *(new in v2.0 — Product content type primarily)*
- **Deployment Options** (`field_deployment_options`) — Multi-value text or Paragraphs. Supported environments: On-Premises, Private Cloud, Hybrid, Air-Gapped, etc.
- **Sovereignty Features** (`field_sovereignty_features`) — Formatted text. Highlights data control, zero-trust architecture, and independence from third-party vendors.

### ⚙️ Technical Tab
- **CDN Cache Tags** — Advanced caching controls
- **Revalidate After** — Cache refresh timing
- **Preview Token** — Secure preview access

### 🏷️ Classification Tab
- **Primary Industry** — Target industry audience
- **Personas** — Target user personas for content personalization
- **Tags** — Topic and technology tags
- **Technologies** — Related technologies
- **Compliance Frameworks** *(enhanced)* — Regulatory and compliance classification
- **Target Sectors** *(new)* — Current production terms (11): Defense, Federal Government, State & Local, Critical Infrastructure, Enterprise, Federal Civilian, Department of Defense, Intelligence Community, Federal Health, Federal Financial. (Note: "State & Local" appears twice in some queries — verify exact unique list.)

### 📐 Layout Tab
- **Template/Layout** — Page template selection
- **Design Variant** — Visual theme variation
- **Visibility** — Control where content appears

### 🔧 Advanced Tab
- **Parent Content** — Hierarchical content relationships
- **Show Table of Contents** — Auto-generate page TOC
- **Content Review Date** — Track when content was last reviewed

---

# Content Type Details

## 1. 📰 Article

**Purpose:** Time-sensitive posts that surface in listings and feeds
**Best for:** News, press releases, blog posts, announcements

### When to Use Articles
- ✅ News announcements and press releases
- ✅ Blog posts and thought leadership
- ✅ Time-sensitive updates
- ✅ Content that should appear in RSS feeds
- ❌ Static pages that rarely change
- ❌ Landing pages for campaigns

### Fields Specific to Articles

#### `field_news_category`
**Purpose:** Categorize articles for filtering and organization
**Required:** No
**Usage:** Select the appropriate category (News, Press Release, Blog, etc.)

#### `field_tags`
**Purpose:** Free-form tagging for flexible categorization
**Required:** No
**Usage:** Enter comma-separated tags: `Amsterdam, Mexico City, "Cleveland, Ohio"`
**Tip:** Use quotes around multi-word tags

#### `field_read_time`
**Purpose:** Help readers estimate time commitment
**Required:** No
**Usage:** Enter whole minutes only (3, 5, 7)

### SEO Best Practices for Articles
- Keep titles under 60 characters
- Write compelling meta descriptions (150–160 characters)
- Use the summary field for social sharing and teasers
- Add relevant tags for discoverability

---

## 2. 📄 Basic Page

**Purpose:** Static informational content rendered headlessly
**Best for:** About pages, policies, general company information

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

**Purpose:** Job postings with application details
**Best for:** Open positions, job descriptions, career opportunities

### When to Use Career Pages
- ✅ Active job postings
- ✅ Detailed role descriptions
- ✅ Department-specific opportunities
- ❌ General career information (use Basic Page)
- ❌ Company culture content (use Article or Basic Page)

### Career-Specific Fields

#### `field_apply_url`
**Purpose:** Direct link to external application system
**Required:** No
**Usage:** Full URL to application form or job board listing

#### `field_job_location`
**Purpose:** Work location for the position
**Required:** No
**Usage:** "Chicago, IL", "Remote", "Hybrid - New York"

#### `field_job_type`
**Purpose:** Type of employment arrangement
**Options:** Full-time, Part-time, Contract, Temporary, Internship

#### `field_department`
**Purpose:** Organizing department for the role
**Required:** No
**Usage:** Select from predefined departments

#### `field_seniority`
**Purpose:** Experience level required
**Options:** Entry Level, Mid-Level, Senior, Lead, Executive

### Writing Job Descriptions
1. Start with a compelling summary
2. List key responsibilities clearly
3. Specify required qualifications
4. Include preferred qualifications separately
5. Mention benefits and company culture
6. End with clear application instructions

---

## 4. 📈 Case Study

**Purpose:** Customer success stories showcasing outcomes
**Best for:** Client testimonials, project showcases, results documentation

### When to Use Case Studies
- ✅ Successful client projects
- ✅ Before/after transformations
- ✅ Quantified business results
- ✅ Industry-specific examples
- ❌ General service descriptions
- ❌ Theoretical examples

### Case Study Structure
1. **Challenge** — What problem did the client face?
2. **Solution** — How did you address it?
3. **Results** — What outcomes were achieved?
4. **Metrics** — Include specific numbers when possible

### Related Content Strategy
- Link to relevant services and products
- Tag appropriate industries and technologies
- Include related case studies for cross-promotion

---

## 5. 📅 Event

**Purpose:** Webinars and events with scheduling details
**Best for:** Upcoming events, past events, registration pages

### Event-Specific Fields

#### `field_event_date`
**Purpose:** Start and end date/time with timezone support
**Required:** No
**Features:** Supports all-day events and multiple timezones

#### `field_event_type`
**Purpose:** Categorize the type of event
**Options:** Webinar, Conference, Workshop, Networking, etc.

### Event Content Best Practices
- Include agenda or schedule details
- Mention speakers and their credentials
- Specify registration requirements
- Provide clear date, time, and location
- Include technical requirements for virtual events

---

## 6. 🎯 Landing Page

**Purpose:** Flexible marketing pages for campaigns
**Best for:** Conversion-focused pages, campaign-specific content

### When to Use Landing Pages
- ✅ Marketing campaigns
- ✅ Lead generation
- ✅ Product launches
- ✅ A/B testing scenarios
- ❌ General information pages
- ❌ Long-form content

### Landing Page Components

#### `field_components`
**Purpose:** Add and reorder content sections using paragraphs
**Available components:**
- Hero sections with CTAs
- Feature lists
- Testimonials
- FAQ sections
- Text blocks
- Image galleries

### Landing Page Strategy
- Keep focused on a single conversion goal
- Use compelling headlines and subheadings
- Include social proof and testimonials
- Minimize navigation distractions
- Test different variants

---

## 7. 👤 Person

**Purpose:** Staff profiles for team pages and bylines
**Best for:** Team member bios, author profiles, speaker information

### Person-Specific Fields

#### `field_bio`
**Purpose:** Professional biography
**Text Format:** Headless Safe (structured content)
**Usage:** Write in third person, focus on professional achievements

#### `field_job_title`
**Purpose:** Current position title
**Required:** No
**Usage:** Use official title as it appears in company directory

#### `field_photo`
**Purpose:** Professional headshot
**Requirements:** Minimum 800×800 pixels, JPG/PNG
**Best Practice:** Use high-quality, professional photos with focal points set

#### `field_department`
**Purpose:** Organizational department
**Usage:** Select appropriate department for team organization

#### `field_expertise`
**Purpose:** Areas of professional expertise
**Usage:** Tag relevant topics, services, industries, and technologies

#### Social Links
- **`field_linkedin`** — Full LinkedIn profile URL
- **`field_github`** — GitHub profile for developers
- **`field_website`** — Personal or portfolio website

#### `field_show_in_directory`
**Purpose:** Control public visibility
**Usage:** Enable to show on public team pages

### Writing Professional Bios
1. Use third person perspective
2. Lead with current role and key achievements
3. Include relevant experience and education
4. Mention notable projects or recognitions
5. Keep concise but informative
6. End with a personal touch if appropriate

---

## 8. 📋 Resource

**Purpose:** Downloadable or gated content assets
**Best for:** eBooks, whitepapers, checklists, templates

### Resource-Specific Fields

#### `field_resource_type`
**Purpose:** Categorize the type of resource
**Options:** eBook, Whitepaper, Checklist, Template, Guide, Report

### Resource Content Strategy
- Create compelling titles that indicate value
- Write detailed descriptions of what readers will learn
- Include clear benefit statements
- Use gated forms for lead generation
- Provide immediate access after form submission

---

## 9. 🔧 Service *(updated in v2.0)*

**Purpose:** Professional consulting, implementation, and managed services
**Best for:** Describing service offerings — Infrastructure Engineering, Headless CMS Implementation, Zero-Trust Consulting, AI Integration, etc.

### When to Use Service Pages
- ✅ Consulting and advisory engagements
- ✅ Implementation and delivery services
- ✅ Managed operations offerings
- ❌ Self-deployable technology platforms (use Product instead)

### Service-Specific Fields *(updated)*

#### `field_mission_impact` *(required)*
**Purpose:** How the service enhances, streamlines, or facilitates mission execution
**Usage:** Focus on client outcomes — avoid feature lists; lead with impact

#### `field_defense_relevance`
**Purpose:** Specific value proposition for defense contractors and government organizations
**Usage:** Strongly recommended for all service pages given our target market

#### `field_key_capabilities`
**Purpose:** Structured list of service capabilities
**Type:** Paragraphs field
**Usage:** Use the Capability paragraph type for consistent formatting

#### `field_related_products`
**Purpose:** Entity reference to related Product content
**Usage:** Link to any products that support or complement this service

### Service Content Best Practices
- Lead every page with Mission Impact — frame benefits around the client's mission, not our capabilities
- Always link to relevant Products and Case Studies
- Focus on outcomes and sovereignty, not feature lists
- Include clear next steps for prospects

---

## 10. 🚀 Product *(new in v2.0)*

**Purpose:** Dedicated pages for self-deployable sovereign technology platforms
**Best for:** Sovereign Infrastructure Platform, Liberty Headless CMS Platform, Enterprise Search Platform, Fortis Zero-Trust Identity Platform, Apex Secure Data Platform, Vigilance Mission Observability Suite

### When to Use Product Pages
- ✅ Permanent platform and product pages
- ✅ Self-deployable Infrastructure-as-Code solutions
- ✅ Technology platforms clients can license or deploy themselves
- ❌ One-time consulting engagements (use Service instead)
- ❌ General service descriptions (use Service instead)

### Product-Specific Fields

#### Mission Impact Tab

**`field_mission_impact`** *(required)*
**Purpose:** How the product enhances, streamlines, and facilitates the client's mission
**Usage:** Required on all Product pages. Use language that connects the platform to operational outcomes — mission resilience, decision velocity, sovereignty.

**`field_defense_relevance`**
**Purpose:** Specific value for defense contractors and government organizations
**Usage:** Strongly recommended. Speak to zero-trust, air-gapped deployment, data sovereignty, and compliance.

#### Key Capabilities Tab

**`field_key_capabilities`** *(required)*
**Purpose:** Structured list of platform capabilities
**Type:** Paragraphs — use the Capability paragraph type
**Usage:** Required on all Product pages. Each capability should be named, described in 1–3 sentences, and include a Mission Benefit statement.

#### Deployment & Sovereignty Tab

**`field_deployment_options`**
**Purpose:** List all supported deployment environments
**Usage:** On-Premises, Private Cloud, Hybrid, Air-Gapped, etc.

**`field_sovereignty_features`**
**Purpose:** Highlight data control, encryption, zero-trust, and vendor independence
**Usage:** This is a key differentiator for defense and government audiences — be specific

#### Relationship Fields

**`field_related_services`** — Entity reference to related Service pages
**`field_related_case_studies`** — Entity reference to supporting Case Studies

### Product Content Strategy
- Emphasize sovereignty, mission resilience, and operational independence
- Clearly communicate deployability anywhere (on-prem, private cloud, hybrid, air-gapped)
- Every product page must have a strong Mission Impact statement
- Include CTAs for demos and consultations
- Link to related Services and Case Studies

---

## 11. 🧩 Solution *(new in v2.0)*

**Purpose:** Branded, deployable solution packages that bundle one or more Products with one or more Services into a mission-aligned offering
**Best for:** Productized go-to-market packages — analogous to GDIT Digital Accelerators or Palantir Offerings (e.g., "Sovereign Mission Edge for Tactical Operations" bundling Apex + Vigilance + integration services)

### Solution vs. Product vs. Service

The Service / Product / Solution trio describes three distinct things in the catalog. Pick the right one before drafting:

| Type | What it is | Example |
|---|---|---|
| **Service** | The **doing** — a consulting, implementation, or managed engagement | "Headless CMS Implementation," "Zero-Trust Advisory" |
| **Product** | The **thing** — a self-deployable sovereign platform we license or install | "Apex Secure Data Platform," "Fortis Zero-Trust Identity Platform" |
| **Solution** | The **offering** — a branded package combining Products + Services applied to a specific outcome or sector | "Sovereign Mission Edge for Tactical Operations" (Apex + Vigilance + integration services) |

Note: there is also a `solutions` **taxonomy** referenced by every content type via `field_solutions`. The taxonomy *tags* supporting content with the Solution it relates to; the **content type** documented here *is* the Solution page.

### When to Use Solution Pages
- ✅ Branded packages that bundle multiple Products and/or Services
- ✅ Sector-specific or mission-specific offerings with a named go-to-market identity
- ✅ Pages where the value prop is the combination, not any single Product or Service
- ❌ A single Product (use Product instead)
- ❌ A single consulting engagement (use Service instead)
- ❌ Tagging existing content with a solution area (use the `field_solutions` taxonomy reference)

### Solution-Specific Fields

#### Content Tab

**`field_mission_impact`** *(required pattern, matches Product/Service)*
**Purpose:** How the bundled package enhances, streamlines, or facilitates the client's mission
**Usage:** Lead with the combined outcome enabled by the package — not the individual components

**`field_key_capabilities`** *(Paragraphs — `capability` bundle)*
**Purpose:** Structured list of the capabilities the package delivers
**Usage:** Use the Capability paragraph type. Each capability should name the bundled outcome, not just restate a Product or Service feature.

#### Proof Points Tab *(unique to Solution / Case Study)*

**`field_outcomes`** (Paragraphs — `outcome` bundle)
**Purpose:** Quantifiable outcomes delivered by the package
**Usage:** Add one Outcome paragraph per measurable result (e.g., "92% reduction in deployment time"). Pairs naturally with linked Case Studies.

#### Classification Tab

**`field_primary_capability`** — Entity reference to a single term in the Capabilities taxonomy. The dominant capability the package leads with.
**`field_platform`** — Entity reference to a Platforms taxonomy term. Which platform brand this Solution belongs to (matches the field's use on Product / Service / Case Study).
**`field_solutions`** — Entity reference to Solutions taxonomy terms (the cross-content tagging vocabulary). Use this to associate the Solution page with its parent solution area(s).
**`field_industries`, `field_target_sectors`, `field_compliance`, `field_personas`, `field_technologies`, `field_categories`** — Standard classification, used identically to Product/Service.

#### Layout Tab

**`field_related`** — Entity reference for cross-linking. Use to surface the Products and Services bundled into this Solution (Drupal does not enforce the package composition; this field carries it).
**`field_parent`** — Hierarchical relationship if the Solution belongs to a parent offering family.
**`field_template`, `field_visibility`** — Standard layout controls.

#### Other Tabs

Solution shares the standard **Media, CTAs, SEO, Technical, and Editorial** tabs with Product and Service. Field set and behavior are identical — see those sections above.

### Solution Content Strategy
- Lead with the bundled outcome. The reader should understand within the first paragraph what is in the package and what it delivers.
- Use `field_related` to surface the Products and Services included in the package — this is the field that carries the "what's in the box" relationship.
- Use **Key Outcomes** (`field_outcomes`) for measurable proof. If you have a Case Study that proves an outcome, link it via `field_related`.
- Every Solution should map to at least one Persona and at least one Target Sector — packages are sold to specific audiences.
- Don't restate Product or Service copy verbatim. The Solution page sells the combination; the Product and Service pages sell the components.

### Solution vs. `solutions` Taxonomy — quick disambiguation
- **Solution content type** (this section) → the page that *is* the branded package.
- **`solutions` taxonomy** → a tagging vocabulary that lets any Article, Case Study, Resource, etc. say "this content relates to Solution X."
- Both exist intentionally and complement each other. A Solution page may reference its own taxonomy term via `field_solutions` to participate in cross-content listings.

---

# Field Reference

For complete field specifications, usage guidelines, and examples across all 54+ fields, see [FIELD_REFERENCE.md](FIELD_REFERENCE.md).

## Most Important Fields by Content Type

### Product *(new)*
**Essential:** `field_mission_impact`, `field_key_capabilities`, `field_hero_image`, `field_primary_cta`
**Recommended:** `field_defense_relevance`, `field_deployment_options`, `field_sovereignty_features`, `field_related_services`

### Service *(updated)*
**Essential:** `field_mission_impact`, `field_key_capabilities`, `body`, `field_primary_cta`
**Recommended:** `field_defense_relevance`, `field_related_products`, `field_meta_description`

### Solution *(new)*
**Essential:** `field_mission_impact`, `field_key_capabilities`, `field_outcomes`, `field_related`, `field_primary_cta`
**Recommended:** `field_primary_capability`, `field_platform`, `field_industries`, `field_target_sectors`, `field_personas`, `field_meta_description`

### Article
**Essential:** `body`, `field_hero_image`, `field_personas`, `field_meta_description`
**Recommended:** `field_summary`, `field_tags`, `field_primary_cta`, `field_social_image`

### Basic Page
**Essential:** `body`, `field_meta_description`, `field_personas`
**Recommended:** `field_hero_image`, `field_primary_cta`, `field_breadcrumb_label`

### Landing Page
**Essential:** `body`, `field_personas`, `field_primary_cta`, `field_hero_image`
**Recommended:** `field_summary`, `field_meta_description`, `field_secondary_cta`, `field_social_image`

### Case Study
**Essential:** `body`, `field_hero_image`, `field_industry`, `field_primary_cta`
**Recommended:** `field_summary`, `field_technologies`, `field_related`

### Resource
**Essential:** `body`, `field_personas`, `field_primary_cta`, `field_resource_type`
**Recommended:** `field_summary`, `field_hero_image`, `field_industry`, `field_compliance`

### Field Usage Tips
1. **Start with core fields** — body content, personas, and primary CTA first
2. **SEO** — always complete `field_meta_description` and `field_seo_title` for public content
3. **Mission framing** — for Product and Service pages, complete Mission Impact before anything else
4. **Visuals** — use `field_hero_image` consistently; set focal points for responsive display
5. **Audience targeting** — select appropriate personas and target sectors to enable personalization
6. **Conversion** — every piece of content should have a clear primary CTA

---

# Content Creation Workflow

## Before You Start
1. Determine the appropriate content type
2. Gather all necessary assets (images, links, copy)
3. Plan your content structure and outline
4. Identify target audience, personas, and goals

## During Creation
1. Fill in required fields first
2. Optimize for SEO (title, meta description, summary)
3. Add relevant classifications, personas, and target sectors
4. Include appropriate media and CTAs
5. For Product and Service: complete Mission Impact tab

## Before Publishing
1. Preview content on different devices
2. Check all links and references
3. Verify SEO elements are complete
4. Ensure proper categorization and persona targeting
5. Test any forms or interactive elements

## After Publishing
1. Monitor performance metrics
2. Update content as needed
3. Check for broken links periodically
4. Review and refresh on the Content Review Date

---

# SEO Best Practices

## Title Optimization
- Keep under 60 characters
- Include primary keywords
- Make it compelling and clickable
- Avoid keyword stuffing

## Meta Descriptions
- Write 150–160 characters
- Include a call to action
- Summarize the page's value proposition
- Use sentence case

## Content Structure
- Use heading hierarchy (H1 → H2 → H3)
- Keep paragraphs short and scannable
- Include relevant internal links
- Optimize images with alt text

## Technical SEO
- Set canonical URLs for duplicate content
- Use noindex for private/draft pages
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

## Creating a Product Page
1. Choose Product content type
2. Draft the Mission Impact statement first — this frames everything else
3. Add Key Capabilities using the Capability paragraph type
4. Complete Deployment Options and Sovereignty Features
5. Add hero image and Primary CTA (demo/consultation)
6. Link to related Services and Case Studies
7. Complete SEO fields and Target Sectors
8. Preview and publish

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

## Content Not Appearing
- Check publication status
- Verify date settings
- Review visibility settings
- Check user permissions

## Images Not Loading
- Verify file format (JPG/PNG/WebP)
- Check file size limits
- Ensure focal points are set
- Clear cache after updates

## SEO Issues
- Complete all SEO fields
- Check canonical URLs
- Review noindex settings
- Validate structured data

## Form Problems
- Test all form submissions
- Check required field settings
- Verify email notifications
- Review spam filtering

---

# Getting Help

- **Documentation** — Internal knowledge base and this guide
- **Technical Support** — Platform team for CMS or field configuration issues
- **Content Strategy** — Editorial team for content planning and messaging
- **Field Reference** — See [FIELD_REFERENCE.md](FIELD_REFERENCE.md) for detailed field specs

The goal is creating valuable, accessible content that serves our audience while advancing mission objectives. When in doubt: lead with mission impact, focus on client outcomes, and communicate sovereignty.
