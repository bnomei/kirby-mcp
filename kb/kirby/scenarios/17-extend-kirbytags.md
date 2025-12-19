# Scenario: Reuse/extend existing KirbyTags (custom tag wrappers)

## Goal

Create custom KirbyTags that reuse the implementation of existing tags (e.g. `image`) while:

- wrapping the HTML in extra markup
- adding attributes (like `srcset`)
- tweaking output via string replacement

## Inputs to ask for

- Which original tag to extend (`image`, etc.)
- New tag name (e.g. `custom-image`, `imageset`)
- Desired markup/attribute changes

## Internal tools/resources to use

- Confirm plugin path: `kirby://roots` (or `kirby_roots`)
- Inspect plugins: `kirby_plugins_index`
- Render a page containing the tag: `kirby_render_page`

## Implementation steps

1. Create a plugin (or add to an existing one).
2. Read the original tag definition from `Kirby\\Text\\KirbyTag::$types[...]`.
3. Register a new tag under `tags` using the original `attr` and/or `html` callables.

## Examples

### Wrap the original `image` output

`site/plugins/custom-tags/index.php`

```php
<?php

use Kirby\Cms\App as Kirby;
use Kirby\Text\KirbyTag;

$originalTag = KirbyTag::$types['image'];

Kirby::plugin('your/plugin', [
  'tags' => [
    'custom-image' => [
      'attr' => $originalTag['attr'],
      'html' => function ($tag) use ($originalTag) {
        $markup = $originalTag['html']($tag);
        return '<div class="imagewrapper">' . $markup . '</div>';
      },
    ],
  ],
]);
```

## Verification

- Use the custom tag in content (KirbyText) and confirm it renders.

## Glossary quick refs

- kirby://glossary/kirbytag
- kirby://glossary/plugin
- kirby://glossary/roots

## Links

- Quicktip: Reusing KirbyTags: https://getkirby.com/docs/quicktips/extending-kirbytags
- Reference: KirbyTags: https://getkirby.com/docs/reference/text/kirbytags
