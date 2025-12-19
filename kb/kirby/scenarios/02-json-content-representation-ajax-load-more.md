# Scenario: Add a JSON content representation (optionally for “Load more” Ajax)

## Goal
Expose a `.json` representation for a page template, typically to:
- provide a lightweight “project-local API” for frontend JS
- implement “load more” pagination (HTML snippets + JSON metadata)

## Inputs to ask for
- Which page/template gets the representation (e.g. `photography`, `blog`)
- Collection/query to paginate (e.g. listed children, filtered pages)
- Page size (`limit`) and sort order
- Response shape:
  - `more` boolean?
  - `html` string to append?
  - `json` array of items?

## Internal tools/resources to use
- Confirm paths: `kirby://roots` (or `kirby_roots`)
- Find existing templates/controllers: `kirby_templates_index`, `kirby_controllers_index`
- Render and inspect JSON output: `kirby_render_page` with `contentType: json`
- If you need example patterns quickly: `kirby_search` for “content representations”

## Implementation steps
1. Create a representation controller (optional but recommended):
   - `site/controllers/<template>.json.php`
2. Create the representation template:
   - `site/templates/<template>.json.php`
3. If you’re doing “load more”:
   - update the HTML template to output initial items + a button and pagination metadata
   - add a JS fetch that hits `<page>.json/page:<n>` and appends `html`/`json`

## Examples (from the “Load more with Ajax” cookbook pattern)

### JSON controller
`site/controllers/photography.json.php`

```php
<?php

return function ($page) {
    $limit      = 4;
    $projects   = $page->children()->listed()->paginate($limit);
    $pagination = $projects->pagination();

    return [
        'projects' => $projects,
        'more'     => $pagination->hasNextPage(),
        'html'     => '',
        'json'     => [],
    ];
};
```

### JSON template
`site/templates/photography.json.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

foreach ($projects as $project) {
    $html .= snippet('project', ['project' => $project], true);

    $json[] = [
        'title' => $project->title()->value(),
        'url'   => $project->url(),
    ];
}

echo json_encode([
    'more' => $more,
    'html' => $html,
    'json' => $json,
]);
```

## Verification
- Render the JSON representation via MCP:
  - `kirby_render_page` with `id: <page id>` and `contentType: json`
- If your JS uses the `page` URL param:
  - macOS/Linux: `.../photography.json/page:2`
  - Windows (semi-colon separator): `.../photography.json/page;2`

## Glossary quick refs

- kirby://glossary/content-representation
- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/pagination

## Links
- Guide: Content representations: https://getkirby.com/docs/guide/templates/content-representations
- Cookbook: Generating JSON: https://getkirby.com/docs/cookbook/content-representations/generating-json
- Cookbook: Load more with Ajax: https://getkirby.com/docs/cookbook/content-representations/ajax-load-more
- Guide: Controllers (incl. representation controllers): https://getkirby.com/docs/guide/templates/controllers
