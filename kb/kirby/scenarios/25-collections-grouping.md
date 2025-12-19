# Scenario: Group collections for archive views (year/month/etc.)

## Goal
Group collections to build archive-style views like:
- blog posts grouped by year
- events grouped by month
- images grouped by a metadata field (e.g. photographer)

## Inputs to ask for
- What to group (pages/files)
- Group key: field name (`groupBy('year')`) or derived value (callback)
- Sorting within groups (e.g. date desc)
- How to render group headers and item lists

## Internal tools/resources to use
- If the base set is a named collection: `kirby_collections_index`
- Verify the grouping field exists:
  - `kirby://blueprint/{encodedId}` or `kirby_blueprint_read`
  - `kirby://page/content/{encodedIdOrUuid}` for real content values
- Validate output: `kirby_render_page`

## Implementation steps
1. Build and filter the collection first (avoid empty group keys).
2. Use `groupBy('<field>')` when the field value is already what you want.
3. Use `group(fn ($item) => ...)` for derived keys (e.g. year from date).
4. Render with nested loops: outer loop for groups, inner loop for items.

## Examples

### Simple: group by a field value
```php
$years = page('projects')->children()->listed()->groupBy('year');
```

### Derived grouping: group by year from a `date` field
```php
$groups = page('blog')->children()->listed()->group(function ($article) {
    return $article->date()->toDate('Y');
});
```

### Group images by a metadata field
```php
$groups = $page->children()->images()->groupBy('photographer');
```

## Verification
- Render the archive page and confirm:
  - groups exist for all expected keys
  - empty/missing keys don’t break the output

## Glossary quick refs

- kirby://glossary/collection
- kirby://glossary/field
- kirby://glossary/blueprint

## Links
- Cookbook: Grouping collections: https://getkirby.com/docs/cookbook/collections/grouping-collections
- Reference: `$pages->groupBy()`: https://getkirby.com/docs/reference/objects/pages/group-by
- Reference: `$pages->group()`: https://getkirby.com/docs/reference/objects/pages/group
