# Kirby KB content creation — plan

We are creating a new KB collection under `/kb/content` focused on content creation guidance for Kirby Panel fields. This KB explains how each field stores data in content files and how to write back safely with `kirby_update_page_content`.

This complements:

- `kb/kirby/glossary/` (terminology)
- `kb/kirby/scenarios/` (task playbooks)

## Goals

- Provide field-by-field, code-level instructions for read/write and merge behavior.
- Capture real storage formats (YAML/JSON/plain) from actual saved content.
- Provide safe update payload examples for `kirby_update_page_content`.
- Document tricky fields (blocks/layout/writer/structure, files/pages/users, link, object, entries) with verified storage.

## Scope / non-goals

### In scope

- All core Panel field types from https://getkirby.com/docs/reference/panel/fields
- Storage formats in content files and runtime `Field` values
- Merge/append/replace strategies per field type
- `content.uuid` and reference id behavior (pages/files/users)
- Single-language and multi-language content storage

### Out of scope

- Reproducing the full Panel field option docs
- Custom field UI/JS implementation details (only storage format matters)
- Non-core, plugin-specific fields (document separately)

## Folder organization

- `kb/kirby/update-schema/<field>.md` (one field type per file, lowercase slug)
- Optional: `kb/content/INDEX.md` (alphabetical index + categories)
- Resource mapping (future): `kirby://field/{field}/update-schema` -> `kb/kirby/update-schema/<field>.md`
- Search: ensure `kirby_search` includes `kb/content` like it does for glossary/scenarios.

## Field guide format

Each field doc should be short, operational, and storage-focused:

- **Field summary** (what editors see in Panel)
- **Storage format** (raw content file representation; include a real snippet)
- **Runtime value** (`$field->value()` / `toX()` output shape)
- **Update payload** (what to pass to `kirby_update_page_content`)
- **Merge strategy** (replace vs append vs merge; when to preserve existing data)
- **Edge cases** (uuid vs id, multi-language, empty/null behavior)
- **MCP: Inspect/verify** (tools/resources in this repo)
- **Glossary quick refs** (links to relevant terms)
- **Links** (official Kirby docs)

### Template

```md
# <Field type> (type: <field>)

## Field summary

## Storage format

## Runtime value

## Update payload (kirby_update_page_content)

## Merge strategy

## Edge cases

## MCP: Inspect/verify

## Glossary quick refs

## Links
```

## Field inventory (core Panel fields)

From https://getkirby.com/docs/reference/panel/fields:

- blocks
- checkboxes
- color
- date
- email
- entries
- files
- gap
- headline
- hidden
- info
- layout
- line
- link
- list
- multiselect
- number
- object
- pages
- radio
- range
- select
- slug
- stats
- structure
- tags
- tel
- text
- textarea
- time
- toggle
- toggles
- url
- users
- writer

Track plugin/custom fields separately (append later).

## Field categories (for merge strategy and storage expectations)

- Scalar text/number/time: text, textarea, email, url, tel, number, date, time, color, slug, range
- Option lists: select, radio, toggle, toggles, checkboxes, multiselect, tags
- References: pages, files, users, link (uuid vs id behavior)
- Complex structured: structure, object, list, entries, blocks, layout, writer
- UI-only or display-only: gap, headline, line, info, stats (verify if they persist at all)
- Hidden: verify storage and default handling

## Research workflow (how to populate each field doc)

1. Confirm field options from docs and `kirby://field/<type>`.
2. Use the content lab test suite to save sample values via:
   - Panel UI
   - `kirby_update_page_content`
   - `kirby_eval` (when direct API calls are needed)
3. Capture raw content file values and runtime conversions.
4. Record differences between update payloads and stored values.
5. Document merge-safe update patterns and pitfalls.

## KB cross-linking audit (update existing docs)

We need to scan existing KB glossary/scenarios that mention `kirby_update_page_content` and add references to the content-field resource template (`kirby://field/{field}/update-schema`) or at least note that the template exists for field-specific storage guidance.

Suggested steps:

- Search KB for `kirby_update_page_content` usage.
- For each match, add a short note or link pointing to `kirby://field/{field}/update-schema` (or the relevant field doc under `kb/kirby/update-schema/`).
- Ensure glossary entries for blocks/layout/structure (and any field-heavy scenarios) reference the content field guidance.

## Content lab test suite plan (single plugin)

Goal: a repeatable way to observe how Kirby stores each field value.

- Add a new test plugin (single file) under:
  - `tests/cms/site/plugins/mcp-content-lab/index.php`
- Provide a test blueprint via plugin `blueprints` extension:
  - `pages/field-lab` with sections that include every core field type
  - Group fields by category so the Panel is usable
- Add a route or CLI command in the plugin to dump:
  - Raw content file path and contents
  - `page('field-lab')->content()->data()` for parsed values
- Provide helper functions (in the plugin) to:
  - Create/reset the `field-lab` page
  - Seed default values programmatically (for quick tests)
- Add Pest integration tests (optional but recommended):
  - Create/update values via `Page::create()` and `$page->update()`
  - Read content file contents from disk
  - Assert expected storage format per field type
- Consider an in-memory variant:
  - Use `Page::create()` + `$page->save()` on a temp root
  - Capture the written content string without relying on Panel
  - Compare with Panel-saved values to spot differences

## Glossary quick refs (use in field docs)

- kirby://glossary/content
- kirby://glossary/field
- kirby://glossary/blocks-field
- kirby://glossary/layout-field
- kirby://glossary/structure-field
- kirby://glossary/blocks
- kirby://glossary/layout
- kirby://glossary/yaml
- kirby://glossary/uuid
- kirby://glossary/pages
- kirby://glossary/files
- kirby://glossary/users

## Rollout phases

1. Complex fields first (highest risk for update payloads): blocks, layout, structure, writer, object, list, entries
2. Reference fields: pages, files, users, link (cover uuid/id and query options)
3. Multi-value option fields: tags, multiselect, checkboxes, toggles
4. Simple scalar fields: text, textarea, email, url, tel, date, time, number, color, slug, range
5. UI-only fields: gap, line, headline, info, stats (confirm storage behavior)

## Appendix A: Blocks field notes (seed content)

### Blueprint setup

Add a blocks field to any blueprint:

```yaml
fields:
  text:
    type: blocks
```

Customize allowed block types:

```yaml
fields:
  text:
    type: blocks
    fieldsets:
      - heading
      - text
      - image
      - gallery
```

Group block types:

```yaml
fields:
  text:
    type: blocks
    fieldsets:
      text:
        label: Text
        type: group
        fieldsets:
          - heading
          - text
          - list
      media:
        label: Media
        type: group
        fieldsets:
          - image
          - video
```

### Rendering in templates

Render all blocks at once:

```php
<?= $page->text()->toBlocks() ?>
```

Loop through blocks for custom control:

```php
<?php foreach ($page->text()->toBlocks() as $block): ?>
  <div id="<?= $block->id() ?>" class="block block-type-<?= $block->type() ?>">
    <?= $block ?>
  </div>
<?php endforeach ?>
```

### Stored data format

Blocks are stored as JSON in content files. Example structure:

```json
[
  {
    "id": "dbef763a-2a53-4e51-80a7-04c0a1ebc897",
    "type": "heading",
    "isHidden": false,
    "content": {
      "level": "h2",
      "text": "What's Maegazine?"
    }
  },
  {
    "id": "98b70f61-81d6-4774-b9dc-9c9502a12587",
    "type": "text",
    "isHidden": false,
    "content": {
      "text": "<p>Far far away, behind the word mountains...</p>"
    }
  },
  {
    "id": "f5e90755-aa8f-4ce8-8444-2ac89f24ee65",
    "type": "quote",
    "isHidden": false,
    "content": {
      "text": "That's a brand new sketchbook...",
      "citation": "The writer"
    }
  },
  {
    "id": "34d9d36b-a0ff-47dd-b4ea-5ed3a24db990",
    "type": "image",
    "isHidden": false,
    "content": {
      "location": "kirby",
      "image": ["file://mHEVVr6xtDc3gIip"],
      "alt": "",
      "caption": "",
      "ratio": "",
      "crop": "false"
    }
  }
]
```

### Block properties

| Property | Description                                         |
| -------- | --------------------------------------------------- |
| id       | Unique UUID for each block                          |
| type     | Block type (e.g., text, heading, image)             |
| isHidden | Whether the block is hidden from output             |
| content  | Object containing fields specific to the block type |

Each block type has its own content structure based on its blueprint fields.

## Appendix B: Layout field notes (seed content)

### Using the layout field

The layout field extends blocks with multi-column support for complex page layouts.

### Blueprint setup

Basic layout field:

```yaml
fields:
  layout:
    type: layout
```

Custom layouts and fieldsets:

```yaml
fields:
  layout:
    type: layout
    layouts:
      - '1/1'
      - '1/2, 1/2'
      - '1/3, 1/3, 1/3'
      - '1/4, 3/4'
      - '2/3, 1/3'
    fieldsets:
      - heading
      - text
      - image
      - quote
```

### Layout settings

Add custom attributes (class, ID, background, etc.) to each row:

```yaml
fields:
  layout:
    type: layout
    layouts:
      - '1/1'
      - '1/2, 1/2'
    settings:
      fields:
        class:
          type: text
        id:
          type: text
        background:
          type: select
          options:
            light: Light
            dark: Dark
```

### Rendering in templates

```php
<?php foreach ($page->layout()->toLayouts() as $layout): ?>
  <section class="grid <?= $layout->class() ?>" id="<?= $layout->id() ?>">
    <?php foreach ($layout->columns() as $column): ?>
      <div class="column" style="--span:<?= $column->span() ?>">
        <?= $column->blocks() ?>
      </div>
    <?php endforeach ?>
  </section>
<?php endforeach ?>
```

### Stored data format

Example structure:

```json
[
  {
    "id": "d34c0490-20b0-43bd-ac79-e79f8d760e80",
    "attrs": [],
    "columns": [
      {
        "id": "d33ca1fe-ba51-4af0-bd3c-c3aefed1ae97",
        "width": "1/1",
        "blocks": [
          {
            "id": "ab524415-3a32-4d2b-ba2d-9d2272362138",
            "type": "image",
            "isHidden": false,
            "content": {
              "location": "kirby",
              "image": ["file://8RxIAFzJekgWfpFn"],
              "ratio": "21/9",
              "crop": "true"
            }
          }
        ]
      }
    ]
  },
  {
    "id": "23cbf9df-075d-4a14-a1ea-8d19c0eb47f7",
    "attrs": [],
    "columns": [
      {
        "id": "cca87bd9-28f9-4b90-84de-6502ec2e9688",
        "width": "1/3",
        "blocks": [
          { "type": "heading", "content": { "level": "h2", "text": "What's Maegazine?" } },
          { "type": "text", "content": { "text": "<p>Far far away...</p>" } }
        ]
      },
      {
        "id": "162ff072-cdee-41fd-adb9-b688aa53e9bd",
        "width": "1/3",
        "blocks": [
          { "type": "heading", "content": { "level": "h2", "text": "Our values" } },
          { "type": "text", "content": { "text": "<p>Even the all-powerful...</p>" } }
        ]
      },
      {
        "id": "503ba624-ece4-4e1c-81d7-f5ad7924e546",
        "width": "1/3",
        "blocks": [
          { "type": "heading", "content": { "level": "h2", "text": "How we work" } },
          { "type": "text", "content": { "text": "<p>The Big Oxmox...</p>" } }
        ]
      }
    ]
  }
]
```

### Structure overview

| Level        | Property                    | Description                                   |
| ------------ | --------------------------- | --------------------------------------------- |
| Layout (row) | id                          | Unique UUID for the row                       |
| Layout (row) | attrs                       | Settings fields (class, id, background, etc.) |
| Layout (row) | columns                     | Array of columns in this row                  |
| Column       | id                          | Unique UUID for the column                    |
| Column       | width                       | Column width (e.g., 1/2, 1/3, 2/3)            |
| Column       | blocks                      | Array of blocks inside this column            |
| Block        | id, type, isHidden, content | Same structure as blocks field                |

### Key difference from blocks field

| Field  | Structure                        |
| ------ | -------------------------------- |
| Blocks | Flat array of blocks             |
| Layout | Rows → Columns → Blocks (nested) |

## Appendix C: Pages and files field notes (seed content)

### Pages field

Select one or more pages from your site tree.

#### Blueprint setup

```yaml
fields:
  related:
    label: Related Pages
    type: pages
```

With options:

```yaml
fields:
  related:
    label: Related Pages
    type: pages
    max: 3
    multiple: true
    query: site.find('notes').children
    info: "{{ page.date.toDate('Y-m-d') }}"
```

| Property  | Description                                                              |
| --------- | ------------------------------------------------------------------------ |
| multiple  | true (default) for multi-select, false for single                        |
| max / min | Limit number of selections                                               |
| query     | Filter available pages (e.g., page.siblings, site.find('blog').children) |
| store     | uuid (default) or id                                                     |
| subpages  | true (default) to allow navigating into subpages                         |

#### Rendering in templates

Single page:

```php
<?php if ($related = $page->related()->toPage()): ?>
  <a href="<?= $related->url() ?>"><?= $related->title() ?></a>
<?php endif ?>
```

Multiple pages:

```php
<?php foreach ($page->related()->toPages() as $related): ?>
  <a href="<?= $related->url() ?>"><?= $related->title() ?></a>
<?php endforeach ?>
```

#### Stored data format

Single page (UUID by default):

```yaml
related: page://aBc123XyZ
```

Multiple pages (YAML list):

```yaml
related:
  - page://aBc123XyZ
  - page://dEf456UvW
```

With `store: id`:

```yaml
related:
  - notes/my-first-note
  - notes/another-note
```

### Files field

Select one or more files with optional upload support.

#### Blueprint setup

```yaml
fields:
  gallery:
    label: Gallery
    type: files
```

With options:

```yaml
fields:
  cover:
    label: Cover Image
    type: files
    multiple: false
    query: page.images
    layout: cards
    uploads:
      parent: page
      template: cover
```

| Property  | Description                                                |
| --------- | ---------------------------------------------------------- |
| multiple  | true (default) for multi-select, false for single          |
| max / min | Limit number of selections                                 |
| query     | Filter available files (e.g., page.images, page.documents) |
| store     | uuid (default) or id                                       |
| layout    | list (default) or cards                                    |
| uploads   | Upload config or false to disable                          |

#### Rendering in templates

Single file:

```php
<?php if ($cover = $page->cover()->toFile()): ?>
  <img src="<?= $cover->url() ?>" alt="<?= $cover->alt() ?>">
<?php endif ?>
```

Multiple files:

```php
<?php foreach ($page->gallery()->toFiles() as $image): ?>
  <img src="<?= $image->resize(800)->url() ?>" alt="">
<?php endforeach ?>
```

#### Stored data format

Single file (UUID by default):

```yaml
cover: file://8RxIAFzJekgWfpFn
```

Multiple files (YAML list):

```yaml
gallery:
  - file://8RxIAFzJekgWfpFn
  - file://mHEVVr6xtDc3gIip
```

With `store: id` (filename):

```yaml
gallery:
  - image-1.jpg
  - image-2.jpg
```

Cross-page file reference:

```yaml
cover: photography/albums/trees/tree.jpg
```

### Summary: Storage comparison

| Field  | Default store | Single value  | Multiple values    |
| ------ | ------------- | ------------- | ------------------ |
| pages  | uuid          | page://aBc123 | YAML list of UUIDs |
| files  | uuid          | file://aBc123 | YAML list of UUIDs |
| blocks | —             | JSON array    | JSON array         |
| layout | —             | JSON array    | JSON array         |

## Appendix D: Kirby fields summary (grouped by storage)

### Plain text (single string)

| Field    | Purpose           | Stored value            |
| -------- | ----------------- | ----------------------- |
| text     | Single-line input | Hello World             |
| textarea | Multi-line input  | Line 1\nLine 2          |
| slug     | URL-safe string   | my-page-slug            |
| email    | Email address     | hello@example.com       |
| url      | URL               | https://example.com     |
| tel      | Phone number      | +1 234 567 890          |
| color    | Color picker      | #ff0000 or rgb(255,0,0) |
| hidden   | Hidden value      | Any string              |

Example:

```yaml
title: Hello World
email: hello@example.com
color: '#ff0000'
```

### Numbers

| Field  | Purpose       | Stored value |
| ------ | ------------- | ------------ |
| number | Numeric input | 42 or 3.14   |
| range  | Slider input  | 50           |

Example:

```yaml
price: 29.99
volume: 75
```

### Date & time (string)

| Field | Purpose     | Stored value |
| ----- | ----------- | ------------ |
| date  | Date picker | 2025-12-19   |
| time  | Time picker | 14:30:00     |

Example:

```yaml
published: 2025-12-19
starttime: 14:30:00
```

### Single selection (string)

| Field   | Purpose       | Stored value |
| ------- | ------------- | ------------ |
| select  | Dropdown      | option-key   |
| radio   | Radio buttons | option-key   |
| toggle  | On/off switch | true / false |
| toggles | Button group  | option-key   |

Example:

```yaml
category: news
featured: true
status: draft
```

### Multiple selection (comma-separated or YAML list)

| Field       | Purpose               | Stored value     |
| ----------- | --------------------- | ---------------- |
| checkboxes  | Multiple checkboxes   | option1, option2 |
| multiselect | Multi-select dropdown | option1, option2 |
| tags        | Tag input             | tag1, tag2, tag3 |

Comma-separated (default):

```yaml
tags: design, code, kirby
```

Or YAML list:

```yaml
categories:
  - news
  - tutorials
```

### Rich text (HTML string)

| Field  | Purpose              | Stored value                        |
| ------ | -------------------- | ----------------------------------- |
| writer | Inline rich text     | <p>Hello <strong>World</strong></p> |
| list   | Bullet/numbered list | <ul><li>Item 1</li></ul>            |

Example:

```yaml
intro: '<p>Welcome to our <em>website</em></p>'
features: '<ul><li>Fast</li><li>Simple</li></ul>'
```

### Reference fields (UUID or ID)

| Field | Purpose      | Stored value                      |
| ----- | ------------ | --------------------------------- |
| users | Select users | user://aBc123 or user@example.com |

Single user:

```yaml
author: user://aBc123XyZ
```

Multiple users:

```yaml
team:
  - user://aBc123XyZ
  - user://dEf456UvW
```

### Structured data (YAML/JSON)

| Field     | Purpose                  | Stored value         |
| --------- | ------------------------ | -------------------- |
| structure | Repeatable entries       | YAML list of objects |
| object    | Single object            | YAML object          |
| link      | Link with type/text      | YAML object          |
| entries   | Picker for mixed content | YAML list            |

Structure (repeatable rows):

```yaml
team:
  - name: John Doe
    role: Developer
    email: john@example.com
  - name: Jane Smith
    role: Designer
    email: jane@example.com
```

Object (single entry):

```yaml
address:
  street: Main Street 1
  city: New York
  zip: '10001'
```

Link:

```yaml
cta:
  type: url
  value: 'https://example.com'
  text: 'Learn more'
```

### UI-only (no storage)

| Field    | Purpose            |
| -------- | ------------------ |
| gap      | Visual spacing     |
| headline | Section header     |
| info     | Help text block    |
| line     | Horizontal divider |
| stats    | Display statistics |

These fields are for Panel organization only and don't store content.

### Quick reference table

| Storage type     | Fields                                               |
| ---------------- | ---------------------------------------------------- |
| Plain string     | text, textarea, slug, email, url, tel, color, hidden |
| Number           | number, range                                        |
| Date/Time string | date, time                                           |
| Single option    | select, radio, toggle, toggles                       |
| Comma list       | checkboxes, multiselect, tags                        |
| HTML string      | writer, list                                         |
| UUID reference   | pages, files, users                                  |
| JSON array       | blocks, layout                                       |
| YAML object/list | structure, object, link, entries                     |
| No storage       | gap, headline, info, line, stats                     |
