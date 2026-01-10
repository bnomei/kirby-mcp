# Scenario: Auto-create subpages with hooks (subpage builder)

## Goal

Automatically create a set of subpages whenever a page of a certain type is created.

Typical use cases:

- “note” pages always get `gallery` and `info` subpages
- “project” pages always get a fixed tree of child pages

## Inputs to ask for

- Which page types should trigger auto-subpage creation
- Exact subpages to create (`uid`, `title`, `template`, optional sort number)
- Whether subpages should be published immediately or kept as drafts
- Whether to backfill existing pages that already exist

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect hooks/extensions: `kirby://hooks` / `kirby://hook/{name}`
- Inspect blueprints: `kirby_blueprints_index` + `kirby://blueprint/{encodedId}`
- Validate behavior: `kirby_render_page` (for any listing that shows subpages)

## Implementation steps

1. Add a custom blueprint option (e.g. `subpage_builder`) that defines subpages.
2. Add a hook in `site/config/config.php` or a plugin:
   - `page.create:after` → call `buildPageTree($page)`
3. Implement `buildPageTree()` (typically in a plugin file) that:
   - reads `$page->blueprint()->subpage_builder()`
   - creates children with `$page->createChild([...])`
   - skips creation if a child with the same `uid` already exists
   - optionally publishes and sorts them
   - recurses for nested trees

## Examples (cookbook pattern)

### Blueprint option

```yaml
subpage_builder:
  - title: Gallery
    uid: gallery
    template: gallery
    num: 1
  - title: Info
    uid: info
    template: info
    num: 2
```

### Hook entry point

```php
return [
  'hooks' => [
    'page.create:after' => function ($page) {
      buildPageTree($page);
    }
  ]
];
```

## Verification

- Create a new page of the target template in the Panel.
- Confirm the expected subpages appear automatically.

## Glossary quick refs

- kirby://glossary/hook
- kirby://glossary/blueprint
- kirby://glossary/template
- kirby://glossary/plugin

## Links

- Cookbook: Subpage builder: https://getkirby.com/docs/cookbook/content-structure/subpage-builder
- Reference: Hooks: https://getkirby.com/docs/reference/plugins/hooks
- Reference: `$page->blueprint()`: https://getkirby.com/docs/reference/objects/cms/page/blueprint
