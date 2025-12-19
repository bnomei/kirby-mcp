# KirbyTag (aliases: `(link: …)`, `(image: …)`, `kirbytags`)

## Meaning

KirbyTags are KirbyText “shortcodes” that let editors insert dynamic elements (links, images, embeds, etc.) into text fields.

Kirby ships with built-in KirbyTags and you can register custom tags via the `kirbytags` plugin extension.

## In prompts (what it usually implies)

- “Add a custom KirbyTag” means: create a plugin that registers a tag in the `kirbytags` extension.
- “This tag should output HTML” means: implement the tag callback and ensure the field is rendered as KirbyText.
- “Tag not parsed” often means: the field isn’t rendered via KirbyText (`kt()` missing).

## Variants / aliases

- Built-in tag list: `kirbytags` reference docs
- Tag helpers: `kirbytag()`, `kirbytags()`
- Plugin extension: `kirbytags`

## Example

```php
Kirby::plugin('acme/tags', [
    'kirbytags' => [
        'year' => [
            'html' => fn () => date('Y'),
        ],
    ],
]);
```

## MCP: Inspect/verify

- Use `kirby_online` for the specific tag name or behavior you’re implementing.
- Use `kirby_plugins_index` to see which plugins may define custom KirbyTags.
- Use `kirby://extension/kirbytags` for the official extension structure and examples.
- Render a page with `kirby_render_page` to confirm the tag output in context.

## Related terms

- kirby://glossary/kirbytext
- kirby://glossary/plugin
- kirby://glossary/field-method

## Links

- https://getkirby.com/docs/reference/text/kirbytags
- https://getkirby.com/docs/reference/plugins/extensions/kirbytags
- https://getkirby.com/docs/guide/content/text-formatting#kirbytext
