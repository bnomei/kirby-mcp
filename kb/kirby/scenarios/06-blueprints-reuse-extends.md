# Scenario: Reuse & extend blueprints with mixins (`extends`)

## Goal
Reduce duplication across blueprints by factoring repeated parts into reusable “mixin” blueprints and composing them via `extends`.

Common outcomes:
- consistent SEO/meta fields across many page types
- reusable page/file sections (e.g. “Articles” list)
- reusable tab/layout structures

## Inputs to ask for
- Which blueprint(s) to refactor (ids like `pages/article`, `pages/blog`, `site`, `files/...`)
- Which parts are repeated (fields, field groups, sections, tabs, entire layout)
- Where mixins should live (`site/blueprints/fields`, `sections`, `tabs`, `layouts`)
- Any customization points (labels, query, status, hiding/unsetting fields)

## Internal tools/resources to use
- Discover where blueprints are loaded from: `kirby_blueprints_index` (and optionally `kirby_blueprints_loaded`)
- Read individual blueprints (including plugin overrides): `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`
- Panel reference for building blocks:
  - `kirby://fields`, `kirby://sections`
- Render for sanity: `kirby_render_page` (frontend) + open Panel manually for blueprint UI

## Implementation steps
1. Identify duplication:
   - find repeated tabs/sections/fields across multiple blueprints
2. Create mixin blueprints in the right folder:
   - `site/blueprints/fields/*.yml` (single field or `type: group`)
   - `site/blueprints/sections/*.yml`
   - `site/blueprints/tabs/*.yml`
   - `site/blueprints/layouts/*.yml`
3. Replace inline definitions with mixin references:
   - simple reuse: `foo: fields/bar`
   - extended reuse: `foo: { extends: fields/bar, ...overrides... }`
4. Unset inherited parts if needed:
   - set a nested key to `false` to remove it
5. For “composed” field blueprints:
   - use `extends:` with a list to merge multiple partials into a single field definition

## Examples

### Reuse a field blueprint
`/site/blueprints/fields/dishes.yml`

```yaml
label: Dishes
type: structure
fields:
  dish:
    label: Dish
    type: text
    width: 1/3
```

`/site/blueprints/pages/restaurant-menu.yml`

```yaml
title: Restaurant Menu

fields:
  starters: fields/dishes
```

### Extend a mixin and override a property
```yaml
fields:
  starters:
    extends: fields/dishes
    label: Starters
```

### Reuse a group of fields (`type: group`)
`/site/blueprints/fields/meta.yml`

```yaml
type: group
fields:
  date:
    type: date
    time: true
```

Inside a `fields` section:

```yaml
sections:
  meta:
    type: fields
    fields:
      meta: fields/meta
```

### Reuse and extend a pages section
`/site/blueprints/sections/articles.yml`

```yaml
type: pages
parent: site.find("blog")
label: Blog
layout: list
```

`/site/blueprints/pages/blog.yml`

```yaml
sections:
  drafts:
    extends: sections/articles
    status: draft
```

### Reuse a tab mixin
`/site/blueprints/tabs/seo.yml`

```yaml
label: SEO
icon: search
fields:
  seoTitle:
    label: SEO Title
    type: text
```

Use it:

```yaml
tabs:
  content:
    label: Content
    preset: page
  seo: tabs/seo
```

### Unset parts of an extended mixin

```yaml
tabs:
  seo:
    extends: tabs/seo
    fields:
      seoDescription: false
```

### Multiple `extends` at once (compose a field blueprint)

```yaml
fields:
  layout:
    type: layout
    extends:
      - layout/layouts
      - layout/fieldsets
      - layout/settings
```

## Verification
- `kirby_blueprint_read` the target blueprint and confirm it resolves without errors.
- In the Panel, open a page that uses the blueprint and confirm tabs/sections/fields render as expected.

## Glossary quick refs

- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/field
- kirby://glossary/section

## Links
- Guide: Reusing & extending blueprints: https://getkirby.com/docs/guide/blueprints/extending-blueprints
- Guide: Blueprint query language (for `query:` options): https://getkirby.com/docs/guide/blueprints/query-language
