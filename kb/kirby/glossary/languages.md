# Languages (aliases: `$languages`, `Kirby\Cms\Languages`, `$kirby->languages()`)

## Meaning

“Languages” refers to the collection of configured languages in a multi-language Kirby site. You get it from `$kirby->languages()` and can then find/sort/iterate language objects.

## In prompts (what it usually implies)

- “List all languages” means iterating `$kirby->languages()`.
- “Find language by code” means `$languages->find('de')`.
- “How many languages exist?” means `$languages->count()`.

## Variants / aliases

- `$kirby->languages()` (collection)
- `$languages->find('de')`
- `$languages->sortBy('name')`

## Example

```php
<?php foreach ($kirby->languages() as $language): ?>
  <a href="<?= $language->url() ?>"><?= $language->name()->escape() ?></a>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Confirm multi-language config via `kirby://config/languages` (requires `kirby_runtime_install`).
- Use `kirby_eval` to inspect language codes/names quickly:
  - example: `return kirby()->languages()->pluck('code');`

## Related terms

- kirby://glossary/language
- kirby://glossary/i18n

## Links

- https://getkirby.com/docs/reference/objects/cms/languages
- https://getkirby.com/docs/guide/languages
