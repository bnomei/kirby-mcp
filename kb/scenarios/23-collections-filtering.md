# Scenario: Filter collections (pages/files/users) safely

## Goal

Filter Kirby collections (most commonly `Pages`) based on:

- field values (`filterBy()`)
- more complex logic (`filter()` with a callback)
- multi-value fields like tags (`in` operator + separator)

## Inputs to ask for

- What you are filtering (pages, files, users)
- Where the collection comes from (e.g. `$page->children()->listed()`)
- Whether it’s a named collection (e.g. `collection('articles')`)
- The filter criteria (field name, operator, value(s))
- Whether missing values should be excluded (avoid errors on empty fields)

## Internal tools/resources to use

- Confirm paths/roots: `kirby://roots` (or `kirby_roots`)
- If the base set is a named collection: `kirby_collections_index`
- Verify field names/types quickly:
  - `kirby://blueprint/{encodedId}` (or `kirby_blueprint_read`) for expected fields
  - `kirby://page/content/{encodedIdOrUuid}` (or `kirby_read_page_content`) for real content keys
- Validate output: `kirby_render_page` (render the listing page)

## Implementation steps

1. Build the base collection first (keep it readable):
   - e.g. `$items = page('projects')->children()->listed();`
   - avoid `site()->index()` on large sites; prefer the smallest base set
2. Prefer `filterBy()` for simple cases (faster, more declarative).
3. Use `filter()` with a callback for computed criteria (dates, cross-field conditions).
4. For multi-value fields stored as comma-separated strings (tags):
   - use `filterBy('tags', 'in', $needles, ',')` or normalize to arrays with `split()`.
5. Filter out empty/missing fields before grouping/processing to avoid warnings.

## Examples

### Basic: filter by a single-value field

```php
$projects = page('projects')->children()->listed();
$featured = $projects->filterBy('featured', true);
```

### Date logic: items after “now”

```php
$events = page('events')->children()->listed();
$upcoming = $events->filterBy('date', 'date >', time());
```

### Multi-value field: related siblings by shared tags

```php
$related = $page->siblings(false)->filterBy(
    'tags',
    'in',
    $page->tags()->split(','),
    ','
);
```

### Filter by template (or exclude templates)

```php
$articles = page('blog')->children()->listed()->filterBy('template', 'article');

$events = page('events')
    ->children()
    ->listed()
    ->filterBy('template', 'not in', ['concert', 'exhibition']);
```

## Verification

- Render the page that outputs the filtered collection and confirm:
  - expected count
  - no errors when fields are missing/empty

## Glossary quick refs

- kirby://glossary/collection
- kirby://glossary/field
- kirby://glossary/template
- kirby://glossary/roots

## Links

- Cookbook: Filtering collections: https://getkirby.com/docs/cookbook/collections/filtering
- Reference: `$pages->filterBy()`: https://getkirby.com/docs/reference/objects/pages/filter-by
- Reference: `$pages->filter()`: https://getkirby.com/docs/reference/objects/pages/filter
