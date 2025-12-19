# Scenario: Sort collections (pages/files/users)

## Goal
Sort collections in a predictable way for:
- listings (blog, projects, events)
- files (galleries, downloads)
- users (directories, member lists)

## Inputs to ask for
- What is being sorted (pages/files/users)
- Sort keys and direction (asc/desc)
- Special ordering needs (custom order arrays, numeric vs string)
- Whether sorting should be editor-controlled (manual sort)

## Internal tools/resources to use
- Inventory existing patterns: `kirby_collections_index`, `kirby_templates_index`, `kirby_controllers_index`, `kirby_models_index`
- Validate listing output: `kirby_render_page`
- If sorting depends on a field, confirm it exists:
  - `kirby://blueprint/{encodedId}` or `kirby_blueprint_read`

## Implementation steps
1. Start from an explicit base collection (don’t chain too much in templates).
2. Use `sortBy()` with a direction:
   - `sortBy('date', 'desc')`
3. For descending order you can also `flip()` the collection.
4. For complex sorting:
   - use multiple sort keys in `sortBy()`
   - or sort by a page model method (computed sort key)
5. For files, use `sortBy('sort')` to respect manual file sorting.

## Examples

### Sort by field value
```php
$products = page('products')->children()->listed()->sortBy('price')->flip();
```

### Sort by multiple fields
```php
$books = page('books')->children()->listed()->sortBy('lastname', 'asc', 'firstname', 'asc');
```

### Sort by a computed page model method
`site/models/project.php`
```php
<?php

use Kirby\Cms\Page;

class ProjectPage extends Page
{
    public function countImages(): int
    {
        return $this->images()->count();
    }
}
```

```php
$projects = $page->children()->listed()->sortBy('countImages', 'desc');
```

### Sort files by manual sort field
```php
$files = $page->files()->sortBy('sort');
```

## Verification
- Render the listing and confirm order is stable across requests.
- If sorting is editor-controlled, verify Panel sorting changes the output.

## Glossary quick refs

- kirby://glossary/collection
- kirby://glossary/field
- kirby://glossary/template
- kirby://glossary/blueprint

## Links
- Cookbook: Sorting: https://getkirby.com/docs/cookbook/collections/sorting
- Reference: `$pages->sortBy()`: https://getkirby.com/docs/reference/objects/pages/sort-by
- Guide: Page models (computed methods): https://getkirby.com/docs/guide/templates/page-models
