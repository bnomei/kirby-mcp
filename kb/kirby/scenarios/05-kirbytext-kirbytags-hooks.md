# Scenario: Add KirbyText/KirbyTags hooks (pre/post processing)

## Goal

Modify text parsing output by hooking into KirbyText/KirbyTags processing, e.g.:

- rewrite/normalize raw text before parsing
- adjust rendered HTML after parsing (e.g. add anchors to headings)

## Inputs to ask for

- Which hook(s) you want (`kirbytext:*` vs `kirbytags:*`)
- Whether changes should affect all KirbyText fields or only specific ones
- Exact transformation rules (string replacements, regex, DOM-safe transforms, etc.)
- Whether this belongs in `site/config/config.php` (project-only) or a plugin (reusable)

## Internal tools/resources to use

- Hook name discovery: `kirby://hooks`
- Hook reference: `kirby://hook/{name}`
- Verify the effect: `kirby_render_page`
- Check plugin presence: `kirby_plugins_index`

## Implementation steps

1. Decide where to register:
   - project-only: `site/config/config.php`
   - reusable: `site/plugins/<plugin>/index.php`
2. Pick the right stage:
   - `kirbytext:before` → raw text before anything else
   - `kirbytags:before` → before KirbyTags are parsed
   - `kirbytags:after` → after KirbyTags, before Markdown/SmartyPants
   - `kirbytext:after` → final rendered HTML
3. Keep hook callbacks small and deterministic; always return the modified string.

## Examples

### Register in config

`site/config/config.php`

```php
<?php

return [
    'hooks' => [
        'kirbytext:after' => function (string $text) {
            return str_replace('<h2>', '<h2 class="h2">', $text);
        },
    ],
];
```

### Register in a plugin

`site/plugins/text-hooks/index.php`

```php
<?php

use Kirby\Cms\App as Kirby;

Kirby::plugin('acme/text-hooks', [
    'hooks' => [
        'kirbytext:before' => function (string $text) {
            return str_replace('---', '—', $text);
        },
    ],
]);
```

## Verification

- Render a page that outputs KirbyText and confirm the transformation is visible.

## Glossary quick refs

- kirby://glossary/kirbytext
- kirby://glossary/kirbytag
- kirby://glossary/hook
- kirby://glossary/plugin

## Links

- Quicktip: Order of KirbyText/KirbyTags hooks: https://getkirby.com/docs/quicktips/kirbytext-hooks
- Reference: Hooks overview: https://getkirby.com/docs/reference/plugins/hooks
- Reference: `kirbytext:before`: https://getkirby.com/docs/reference/plugins/hooks/kirbytext-before
- Reference: `kirbytext:after`: https://getkirby.com/docs/reference/plugins/hooks/kirbytext-after
- Reference: `kirbytags:before`: https://getkirby.com/docs/reference/plugins/hooks/kirbytags-before
- Reference: `kirbytags:after`: https://getkirby.com/docs/reference/plugins/hooks/kirbytags-after
