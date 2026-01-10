# Scenario: Scaffold a new page type (template + blueprint + controller)

## Goal

Add a new Kirby “page type” with:

- a frontend template (PHP)
- a Panel blueprint (YAML)
- optional controller/model code for reusable logic

## Inputs to ask for

- Page type/template name (lowercase; e.g. `event`, `project`, `article.video`)
- Required fields (e.g. `headline`, `text`, `date`, `cover`, `gallery`)
- Panel UI expectations (tabs? files section? subpages?)
- Any existing page type to copy/extend

## Internal tools/resources to use

- Discover project roots first: `kirby://roots` (or `kirby_roots`)
- Inventory what already exists:
  - `kirby_templates_index`, `kirby_controllers_index`, `kirby_models_index`
  - `kirby_blueprints_index` + `kirby://blueprint/{encodedId}` (or `kirby_blueprint_read`)
- Panel building blocks reference:
  - `kirby://fields`, `kirby://sections`
- Validate rendering: `kirby_render_page`

## Implementation steps

1. Resolve paths (don’t assume `site/`):
   - use `kirby://roots` and read `templates`, `controllers`, `models`, `blueprints`
2. Check for name collisions:
   - make sure the new template/blueprint/controller/model names don’t already exist
3. Create the blueprint:
   - `site/blueprints/pages/<template>.yml`
   - use valid field names (alphanumeric/underscore; avoid method name collisions; don’t redefine top-level `title`)
4. Create the template:
   - `site/templates/<template>.php`
5. Add an optional controller for template variables:
   - `site/controllers/<template>.php`
6. Add an optional page model for reusable page methods:
   - file path: `site/models/<template>.php`
   - class name: `<Template>Page` (strip dashes/underscores in the class name)
7. Keep logic out of templates where possible:
   - extract repeated markup into snippets (`site/snippets/...`)

## Examples

### Minimal page blueprint

`site/blueprints/pages/event.yml`

```yaml
title: Event

tabs:
  content:
    label: Content
    columns:
      main:
        width: 2/3
        sections:
          fields:
            type: fields
            fields:
              headline:
                label: Headline
                type: text
              text:
                label: Text
                type: textarea
      sidebar:
        width: 1/3
        sections:
          files:
            type: files
            label: Files
```

### Minimal template

`site/templates/event.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php snippet('header') ?>

<article>
  <h1><?= $page->headline()->or($page->title())->escape() ?></h1>
  <?= $page->text()->kt() ?>
</article>

<?php snippet('footer') ?>
```

### Optional controller (prepare variables)

`site/controllers/event.php`

```php
<?php

return function ($page) {
    return [
        'cover' => $page->images()->first(),
    ];
};
```

## Verification

- Render a page of that type:
  - `kirby_render_page` with `id` set to a page that uses the template, and `contentType: html`
- Verify blueprint resolves and is valid YAML:
  - read `kirby://blueprint/pages%2Fevent` (encoded id example) or call `kirby_blueprint_read` with `id: pages/event`

## Glossary quick refs

- kirby://glossary/page
- kirby://glossary/template
- kirby://glossary/blueprint
- kirby://glossary/controller

## Links

- Templates: https://getkirby.com/docs/guide/templates/basics
- Controllers: https://getkirby.com/docs/guide/templates/controllers
- Snippets: https://getkirby.com/docs/guide/templates/snippets
- Page models: https://getkirby.com/docs/guide/templates/page-models
- Blueprint intro: https://getkirby.com/docs/guide/blueprints/introduction
- Blueprint fields: https://getkirby.com/docs/guide/blueprints/fields
