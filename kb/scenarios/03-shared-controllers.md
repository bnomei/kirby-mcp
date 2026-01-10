# Scenario: Share controller data (site controller + shared controllers)

## Goal

Reduce duplication across controllers by:

- putting global variables in the site controller
- composing controllers for “post type” variants (video/image/quote, etc.)

## Inputs to ask for

- Which templates/controllers should share data
- What is truly global (belongs in `site.php`) vs shared only by a subset
- Any variant naming scheme (e.g. `post-video`, `article.video`)

## Internal tools/resources to use

- Find current controllers: `kirby_controllers_index`
- Confirm controller root path: `kirby://roots` (or `kirby_roots`)
- Verify output doesn’t error: `kirby_render_page`

## Implementation steps

1. Global/shared across all templates:
   - add `site/controllers/site.php` and return shared variables
2. Shared across a subset:
   - create a “base” controller (e.g. `post.php`)
   - in variant controllers, call the base controller via `$kirby->controller(...)`
   - merge arrays (Kirby Toolkit `A::merge`) and return the combined data
   - pass shared data as the first merge argument so variant values overwrite defaults
3. In templates, use the controller variables you returned; avoid re-running the same queries

## Examples

### Site controller (global defaults)

`site/controllers/site.php`

```php
<?php

return function ($site) {
    return [
        'metaDescription' => $site->description()->value(),
    ];
};
```

### Base controller (shared by post variants)

`site/controllers/post.php`

```php
<?php

return function ($page) {
    return [
        'titleTag' => $page->title()->value(),
    ];
};
```

### Variant controller (compose/extend base)

`site/controllers/post-video.php`

```php
<?php

use Kirby\Toolkit\A;

return function ($page, $pages, $site, $kirby) {
    $post = $kirby->controller('post', compact('page', 'pages', 'site', 'kirby'));

    $video = $page->videourl()->value();

    return A::merge($post, compact('video'));
};
```

## Verification

- Render pages using each variant template and confirm:
  - shared variables exist everywhere you expect
  - variant-only variables don’t leak into unrelated templates

## Glossary quick refs

- kirby://glossary/controller
- kirby://glossary/template
- kirby://glossary/roots

## Links

- Cookbook: Shared controllers: https://getkirby.com/docs/cookbook/development-deployment/shared-controllers
- Quicktip: Keep your code DRY: https://getkirby.com/docs/quicktips/keep_your_code_dry
- Guide: Controllers (site controller + merging): https://getkirby.com/docs/guide/templates/controllers
