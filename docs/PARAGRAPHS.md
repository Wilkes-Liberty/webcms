# Paragraphs Types Setup Guide

**Updated:** March 2026
**Version:** 1.0

Paragraphs allow flexible, reusable, and structured content sections. This is especially useful for Product and Service pages because:

- Capabilities and Use Cases need to be repeatable and well-formatted.
- They support mission-focused storytelling for defense and government audiences.
- They give content creators clean control without needing Layout Builder or complex coding.

## Prerequisites

Before starting, ensure these modules are installed and enabled:

- **Paragraphs** (core module)
- **Paragraphs Browser** (highly recommended for easy selection)
- **Entity Reference Revisions**
- **Field Group** (already in use)
- **Media** (for optional icons)

Also enable **Paragraphs Library** if you want reusable paragraph templates later.

---

## Step 1: Create the "Capability" Paragraph Type

**Machine Name:** `capability`
**Label:** Capability
**Purpose:** Individual capability item repeated under the "Key Capabilities" section.

### Fields to Create

| Field Label | Machine Name | Field Type | Required | Notes |
|---|---|---|---|---|
| Capability Title | `field_capability_title` | Text (plain) | Yes | Short, benefit-focused title |
| Description | `field_capability_description` | Text (formatted, long) | Yes | 1–3 sentences explaining the capability |
| Mission Benefit | `field_mission_benefit` | Text (plain) | No | One-line mission impact (e.g., "Enhances decision velocity") |
| Icon | `field_icon` | Media (Image) | No | Optional — use 64×64px SVG or PNG |

### Recommended Settings

- Under **Manage Form Display**, arrange as: Title → Description → Mission Benefit → Icon
- Under **Manage Display**, use "Default" and hide labels if desired for clean frontend rendering.

---

## Step 2: Create the "Outcome" Paragraph Type (for Solutions & Case Studies)

**Machine Name:** `outcome`
**Label:** Outcome
**Purpose:** Quantifiable results / proof points used on Solution and Case Study pages.

### Fields to Create

| Field Label     | Machine Name          | Field Type          | Required | Notes |
|-----------------|-----------------------|---------------------|----------|-------|
| Metric Value    | `field_metric_value`  | Text (plain)        | No       | Big number, e.g. "65%" or "4×" |
| Metric Label    | `field_metric_label`  | Text (plain)        | No       | Short description of the metric |
| Mission Benefit | `field_mission_benefit` | Text (formatted, long) | No     | One or two sentences explaining the benefit |

### Recommended Settings

- Under **Manage Form Display**: Metric Value → Metric Label → Mission Benefit
- Under **Manage Display**: Use "Default". The frontend (`OutcomeParagraph.tsx`) renders the metric very large with the label below it and the benefit as supporting text.

---

## Step 3: Create the "Use Case" Paragraph Type

**Machine Name:** `use_case`
**Label:** Use Case
**Purpose:** Showcase real-world applications for defense, government, and critical infrastructure clients.

### Fields to Create

| Field Label | Machine Name | Field Type | Required | Notes |
|---|---|---|---|---|
| Use Case Title | `field_use_case_title` | Text (plain) | Yes | e.g., "Secure Data Platform for Tactical Operations" |
| Industry/Sector | `field_sector` | Taxonomy Reference | No | Reference the "Target Sectors" vocabulary |
| Challenge | `field_challenge` | Text (formatted, long) | Yes | Client's problem |
| Solution | `field_solution` | Text (formatted, long) | Yes | How we helped |
| Results & Impact | `field_results` | Text (formatted, long) | Yes | Quantified outcomes + mission benefit |

### Recommended Settings

- Use a clean layout: Title → Sector → Challenge → Solution → Results

---

## Step 3: Add Paragraph Fields to Product and Service Content Types

### For Product Content Type

1. Go to **Structure → Content types → Product → Manage fields**
2. Add new field:
   - Label: **Key Capabilities**
   - Machine name: `field_key_capabilities`
   - Field type: **Paragraphs**
3. Settings:
   - Allowed paragraph types: **Only "Capability"**
   - Number of values: **Unlimited**
4. (Optional) Add a second field:
   - Label: **Use Cases**
   - Machine name: `field_use_cases`
   - Field type: **Paragraphs**
   - Allowed paragraph types: **Only "Use Case"**
   - Number of values: **Unlimited**

### For Service Content Type

Repeat the same process, adding at minimum:

- `field_key_capabilities` (Paragraphs → Capability)
- `field_use_cases` (recommended)

---

## Step 4: Configure Display & Field Groups

1. Go to **Structure → Content types → Product → Manage display**
2. Create a new **Field Group** (using Field Group module):
   - Group type: **HTML element** or **Tabs**
   - Label: **Key Capabilities**
   - Place `field_key_capabilities` inside it
3. Repeat for **Use Cases**
4. Set the Paragraph field format to **Paragraphs Summary** or a custom view mode for clean output.

**Recommended:** Create a view mode called "Card" for Capability paragraphs for a clean bullet-style display.

---

## Step 5: Content Creator Instructions

When creating or editing a **Product** or **Service** page:

1. Scroll to the **Key Capabilities** section.
2. Click **Add Capability** and fill in each item.
3. Use the **Mission Benefit** field to tie each capability directly to client mission success.
4. Repeat the process in the **Use Cases** section for storytelling.

### Best Practices

- Keep each Capability Title under 6 words.
- Always include a Mission Benefit statement.
- Use Use Cases to tell defense/government-relevant stories — focus on outcomes and sovereignty.
- Use icons consistently for visual appeal.
