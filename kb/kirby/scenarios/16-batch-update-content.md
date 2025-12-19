# Scenario: Batch update content safely (one-off migration scripts)

## Goal

Perform a one-time update across many pages/files, e.g.:

- rename fields
- normalize values
- migrate structure/layout field data

Kirby is file-based, so batch changes usually require scripted loops.

## Inputs to ask for

- Backup status (must-have before running)
- Target collection (entire site index vs subset like `page('blog')->childrenAndDrafts()`)
- Multi-language considerations (which languages to read/write)
- Desired safety:
  - admin-only trigger
  - chunking to avoid timeouts
  - logfile/list tracking for big sites

## Internal tools/resources to use

- For small edits: `kirby_read_page_content` + `kirby_update_page_content` (requires runtime install + confirm)
- For large migrations: implement a temporary route as in the cookbook recipe
- Confirm the temporary route exists (and later that it’s removed): `kirby_routes_index(patternContains='batch')` (requires `kirby_runtime_install`)
- Validate changes by rendering: `kirby_render_page`

## Implementation steps

1. Create a temporary admin-only route (project config) to run the loop.
2. Keep the loop defensive:
   - check field existence before reading/writing
   - handle missing pages/files gracefully
3. For big sites, chunk processing and store the list of page IDs in a log file so `site()->index()` runs only once.
4. Remove the route/logfile when done.

## Examples

### Admin-only batch route (basic)

`site/config/config.php`

```php
<?php

return [
  'routes' => [
    [
      'pattern' => 'batch-update-content',
      'action' => function () {
        if (kirby()->user() && kirby()->user()->isAdmin()) {
          foreach (site()->index(true) as $page) {
            if (!$page->content()->has('myfield')) continue;

            $old = $page->content()->myfield()->value();
            $page->update(['myfield' => 'Prefix ' . $old]);
          }
        }
      }
    ],
  ],
];
```

### Chunked processing with a YAML list (large sites)

Use the cookbook approach that stores a list of page IDs in `logs/` and processes it in slices.

## Verification

- Trigger the route URL while logged in as admin and confirm updated content files on disk.
- Re-render affected pages with `kirby_render_page`.
- Delete the temporary route afterwards.
- Confirm the route is removed with `kirby_routes_index(patternContains='batch')` (it should no longer appear).

## Glossary quick refs

- kirby://glossary/content
- kirby://glossary/route
- kirby://glossary/field
- kirby://glossary/language

## Links

- Cookbook: Batch updating content: https://getkirby.com/docs/cookbook/development-deployment/batch-update
- Reference: `$page->update()`: https://getkirby.com/docs/reference/objects/cms/page/update
