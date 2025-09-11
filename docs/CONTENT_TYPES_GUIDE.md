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
- **Personas** - Target user personas
- **Tags** - Topic and technology tags
- **Technologies** - Related technologies

### 📐 **Layout Tab**
- **Template/Layout** - Page template selection
- **Design Variant** - Visual theme variation
- **Visibility** - Control where content appears

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
