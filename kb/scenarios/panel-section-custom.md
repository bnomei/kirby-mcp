# Scenario: Custom display field (replaces Kirby 5 Panel sections)

Kirby 5 legacy `sections` plugins are unsupported in Kirby 6. For a list of links, use a `DisplayField` class and a Vue 3 field component. This preserves the old article path for migration discovery.

## PHP class and registration

```php
<?php

use Kirby\Form\Field\DisplayField;
use Kirby\Reflection\Attributes\Prop;

class LinksField extends DisplayField
{
    #[Prop]
    protected array $links = [];
}

Kirby::plugin('example/links', [
    'fields' => ['links' => LinksField::class],
]);
```

For production plugins, namespace and autoload the class. Pass only authorized links with validated URLs. Cheap `links` data arrives with the field's props; no legacy `this.load()` request is needed.

## Vue registration

```js
panel.plugin('example/links', {
  fields: {
    links: {
      inheritAttrs: false,
      props: { label: String, links: Array },
      template: `<k-field :label="label"><k-collection :items="links" /></k-field>`,
    },
  },
});
```

## Blueprint

```yaml
fields:
  usefulLinks:
    type: links
    label: Getting started
    links:
      - text: Documentation
        link: https://getkirby.com/releases/6
        icon: book
```

Use a unique name across all tabs/columns. `usefulLinks` is a display entry, not a content key to update. A list of selected page references needs `pagepicker` instead.

## Verification

Inspect roots, plugin inventory and raw blueprint through MCP; open the Panel and check rendered links, plain-text escaping and restricted roles. Confirm saving does not persist a display value. For expensive or refreshed data use `api()` and `endpoints.field` as described in `kirby://kb/panel/patterns-section-load`.

## Links

- `kirby://kb/panel/reference-sections`
- `kirby://kb/panel/tooling-kirbyup`
- https://getkirby.com/releases/6/migration/plugin-developers/sections
