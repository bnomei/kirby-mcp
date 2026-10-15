# Panel fields in Kirby 6

Baseline: Kirby `6.0.0-beta.1`. Every custom field is a PHP class plus a Vue 3 component. Kirby 5 legacy arrays of `props`/`computed`/`methods` are removed; rewrite them as properties/getters/methods before rebuilding the frontend.

## Choose the storage contract

- `Kirby\Form\Field\DisplayField`: UI only, no stored value; replaces custom sections. Core examples include `info`, `stats`, `buttons`, `pagelist`, `filelist`.
- `ValueField`: stores a value without an editor input (e.g. hidden).
- `InputField`: editor input, validation and a stored value. It extends `ValueField`.
- Extend a concrete core class such as `TextField` when its storage/validation already fits. The old PHP `extends: text` array option is removed.

`Field::hasValue()` is final and follows `ValueField` ancestry. A display field is not a hidden content field; never include it in content update payloads. MCP retains display fields in `fieldSchemas` with `hasValue: false`, while unknown/static types may have `null`.

## Minimal text field

`site/plugins/hello/index.php`:

```php
<?php

use Kirby\Form\Field\TextField;
use Kirby\Reflection\Attributes\Prop;

class HelloField extends TextField
{
    #[Prop]
    protected string $message = 'Hello';
}

Kirby::plugin('example/hello', [
    'fields' => ['hello' => HelloField::class],
]);
```

For packaged plugins, namespace/autoload the class. Properties with `#[Prop]` become blueprint options and exported props; use getters for translations/templates and `#[Prop(export: false)]` for backend-only options. Merge `parent::props()` if adding computed props. Do not do expensive work in constructors/setters: fields are created during content updates too.

`src/components/HelloField.vue`:

```html
<template>
  <k-field v-bind="$props">
    <p>{{ message }}</p>
    <k-text-input :name="name" :value="value" @input="$emit('input', $event)" />
  </k-field>
</template>

<script>
  export default {
    inheritAttrs: false,
    props: { label: String, name: String, value: String, message: String },
    emits: ['input'],
  };
</script>
```

```js
import HelloField from './components/HelloField.vue';

panel.plugin('example/hello', {
  fields: { hello: HelloField },
});
```

Kirby retains `value`/`input` even on Vue 3. Do not use Vue 3's default component `v-model` on Kirby inputs; it binds the wrong prop/event. Declare `emits` to avoid bubbling native `InputEvent` objects becoming stored values.

## Values, validation and endpoints

When extending `InputField` directly, declare a typed `$value` property with a default. `fill()` reads stored or PHP values; `submit()` handles input; `toFormValue()` supplies the editor; `toStoredValue()` supplies content. Override `validations()` for additional rules and `isEmptyValue()` when the default empty-value definition is unsuitable. Test strings loaded from disk as well as typed Panel input.

Replace legacy `api` closures or `routes()` with `api(): array`. Capture the field before creating an action (the action's `$this` is the API object), or use `$this->field()` inside it. Vue components receive `endpoints.field`:

```js
const response = await this.$api.get(this.endpoints.field + '/preview');
```

Use `dialogs()`/`drawers()` and the same field endpoint prefix. Nested fields need `ProvidesNestedForm` and a `form()` implementation (often supplied by `Kirby\Form\Mixin\Fields` or `ObjectField`). Never construct a page-only endpoint when the field can be nested or used on a file/user/site.

## Verification

Inspect `kirby_plugins_index`, add the field to a blueprint, then test Panel save/reload, validation, empty/default values, nested placement and restricted roles. Runtime schemas should resolve the class and its `hasValue` correctly. Build with kirbyup 4 / Vue 3; do not bundle a second Vue runtime.

## Links

- https://getkirby.com/releases/6/migration/plugin-developers/fields
- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
- https://getkirby.com/releases/6/migration/plugin-developers/trusted-html
- `kirby://kb/panel/reference-sections`
- `kirby://kb/panel/tooling-kirbyup`
