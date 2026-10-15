# Async display-field loading (replaces Kirby 5 section load)

Kirby 5 legacy sections used `this.load()`/`useSection()`. Kirby 6 removes custom sections: use a `DisplayField`, send cheap settings as props and fetch expensive data through `api()`. Do not retain a `/sections/...` route.

## PHP endpoint

Inside your `DisplayField` class, an empty route pattern exposes the field endpoint itself:

```php
public function api(): array
{
    $field = $this;
    return [
        [
            'pattern' => '',
            'method' => 'GET',
            'action' => fn () => [
                'text' => 'Updated: ' . $field->model()->modified('Y-m-d H:i'),
            ],
        ],
    ];
}
```

This small example demonstrates the endpoint; cheap modified text can normally go directly in `props()`. Keep site-wide queries and external calls out of constructors/setters. Capture `$field`: route action `$this` is the API object.

## Vue 3 component

```js
panel.plugin('example/modified', {
  fields: {
    modified: {
      inheritAttrs: false,
      props: { label: String, endpoints: Object },
      data() {
        return { text: '', error: '', loading: true };
      },
      async created() {
        try {
          const response = await this.$api.get(this.endpoints.field);
          this.text = response.text;
        } catch (error) {
          this.error = 'Could not load the display';
        } finally {
          this.loading = false;
        }
      },
      template: `<k-field :label="label"><p>{{ loading ? 'Loading…' : error || text }}</p></k-field>`,
    },
  },
});
```

For components that refresh while mounted, watch the relevant model/language context, discard stale responses and clean up listeners/timers on unmount. Do not reconstruct page-only URLs: `endpoints.field` also supports nested fields and other models.

## Verify

Test loading, success, empty data, failed requests, navigation and authorization. Display data is not content: verify `hasValue: false` and no content writes. Registration and blueprint examples: `kirby://kb/panel/reference-sections`.

## Source

- https://getkirby.com/releases/6/migration/plugin-developers/sections
