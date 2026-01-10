# Scenario: Expose JSON for “Populate Figma designs” workflows

## Goal

Create a JSON endpoint (content representation) that exports structured content so tools like Figma plugins can “pull” data and populate designs.

This is still a normal Kirby content representation:

- `blog.php` (base template)
- `blog.json.php` (JSON representation)

## Inputs to ask for

- Which page serves as the endpoint (e.g. `blog`)
- Which fields should be exported (title, date, image URL, excerpt, …)
- Which JSON keys the design layers will use (must match in Figma)
- Count/limit and sorting requirements
- Whether output needs HTML decoding/sanitization for the target consumer

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Verify the base template exists: `kirby_templates_index`
- Validate JSON output: `kirby_render_page` with `contentType: json`

## Implementation steps

1. Add a JSON representation template:
   - `site/templates/<template>.json.php` (e.g. `blog.json.php`)
2. Build the collection and map fields into a plain array with keys that match the Figma layer names.
3. Encode as JSON with `json_encode()`.
4. If needed, normalize/strip HTML (`Html::decode()`, `excerpt()`, etc.).

## Examples (from the cookbook recipe)

`site/templates/blog.json.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

$posts = $page->children()->listed()->sortBy('date', 'desc')->limit(20);
$json  = [];

foreach ($posts as $post) {
  $json[] = [
    'title'  => Html::decode($post->title()),
    'imagen' => $post->image()?->resize(1080, null, 80)->url(),
    'date'   => $post->date()->toDate('Y-m-d'),
    'text'   => Html::decode($post->text()->excerpt(150)),
  ];
}

echo json_encode($json);
```

## Verification

- Open `/blog.json` (or render via MCP) and confirm it outputs valid JSON.
- Ensure image URLs are absolute and publicly reachable if a third-party tool needs them.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/roots
- kirby://glossary/content-representation
- kirby://glossary/field

## Links

- Cookbook: Populate Figma designs: https://getkirby.com/docs/cookbook/content-representations/figma-auto-populate
- Guide: Content representations: https://getkirby.com/docs/guide/templates/content-representations
