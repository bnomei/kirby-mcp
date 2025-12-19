# Scenario: Show random content (shuffle + pick)

## Goal

Display random content like:

- a random featured project on the homepage
- a random selection of related articles
- a random header image

## Inputs to ask for

- What to randomize (pages/files)
- How many items to show (1, N)
- Whether items must be constrained (listed only, same template, tagged, etc.)

## Internal tools/resources to use

- Validate output: `kirby_render_page`
- If the base set is a named collection: `kirby_collections_index`
- If constraints depend on fields, confirm field names:
  - `kirby://blueprint/{encodedId}` / `kirby_blueprint_read`

## Implementation steps

1. Build the constrained collection first (filter + sort).
2. Randomize with `shuffle()`.
3. Use `first()` for “pick one”, or `limit($n)` for “pick N”.

## Examples

### Random single page from a section

```php
$randomProject = page('projects')->children()->listed()->shuffle()->first();
```

### Random N pages

```php
$randomProjects = page('projects')->children()->listed()->shuffle()->limit(3);
```

### Random image (current page)

```php
$randomImage = $page->images()->shuffle()->first();
```

## Verification

- Refresh a few times and confirm results change.
- Confirm the constrained collection is non-empty (fallback if empty).

## Glossary quick refs

- kirby://glossary/collection
- kirby://glossary/content
- kirby://glossary/blueprint
- kirby://glossary/field

## Links

- Cookbook: Random content: https://getkirby.com/docs/cookbook/collections/random-content
- Reference: `$pages->shuffle()`: https://getkirby.com/docs/reference/objects/pages/shuffle
