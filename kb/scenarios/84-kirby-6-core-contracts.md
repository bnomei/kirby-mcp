# Kirby 6 PHP, storage and HTTP contracts

Use these contracts when writing plugins, migrations, custom models or API endpoints. For the upgrade sequence and compatibility mappings, read `kirby://kb/scenarios/83-migrate-kirby-5-to-6`.

## Forms and typed updates

Construct forms with named arguments, fill existing/default values, submit input, validate, then obtain storage values:

```php
$form = new Kirby\Form\Form(fields: $fields, model: $page);
$form->fill(input: $existing, defaults: true);
$form->submit(input: $input);
$form->validate();
$stored = $form->toStoredValues();
```

`toFormValues()` is the editor representation; it is not necessarily the content-file representation. `validate()` throws `Kirby\Exception\FormValidationException`, a subclass of `InvalidArgumentException`. Keep field-level errors rather than converting every failure into a generic success response.

PHP `$model->update()` and `Form::submit()` accept supported objects/collections for blueprint fields: files/pages/users for pickers, structure/blocks collections, content fields and `DateTime` values. The field class performs the conversion. This does **not** make PHP objects valid JSON MCP arguments: use the appropriate `kirby://fields/update-schema` resource and JSON payload for MCP calls. Display fields are never content-write targets.

Use explicit validation for bulk updates when invalid data must be rejected. Invalid option values are preserved rather than cleaned automatically. Non-numeric number input stores an empty value rather than zero; number/range `toNumber()` returns null for empty input. `Str::float()` returns null for non-numeric input, so distinguish absent/invalid values from zero.

`Txt::encodeValue()` encodes booleans as `true`/`false` strings and an empty array as an empty string. Do not assume direct text serialization is the same as a field's storage conversion. Quote dates in nested YAML. Read back values using their field converters and compare intended semantics, not old byte formatting.

## Custom field and model APIs

Resolve classes with `Kirby\Form\Field::resolve($type)` and construct them with named arguments. Pass model/siblings at construction; a custom constructor must forward remaining `mixed ...$args`. Define typed `#[Prop]` properties and defaults, with optional getters/setters/export names. `config()` supplies configuration defaults; blueprint options take precedence. Nested forms implement `ProvidesNestedForm`.

The registered name determines field type. Use canonical picker names in PHP lookups; old picker names only normalize in blueprints. Use `Kirby\Form\FieldOptions`, `Kirby\Blueprint\Blueprint` and `Kirby\Api\Api` in new code. Custom `ModelWithContent` subclasses must implement `apiUrl()`. `Kirby\Cms\Model` is the shared base for content models and languages.

Core content-field methods and validators are real PHP methods; custom methods/validators must use non-conflicting names. Unknown field methods throw `BadMethodCallException`. Do not enumerate removed core field-method alias registries to discover callable methods.

`App::models()` and `Uuid::index()` generators are keyed by ID. Lazy collections skip items that fail hydration. `Fields` and sibling behavior use `Kirby\Cms\Collection`; do not assume Toolkit collection inheritance. `Fieldset::name()` falls back to a labelized type.

Use `Uuid::from()` for UUID strings, `Permalink::from()` for permalink strings and `Uuid::for()` for model objects. See `kirby://glossary/uuid`. Use `Language::isLastLanguage()` for the only remaining language; `isLast()` means last in the collection.

## Failures and return types

- Impossible actions throw `AbilityException`; permission denial and invalid input are distinct failures. Guards expose availability/executability checks. See `kirby://glossary/permissions`.
- Invalid template changes throw `InvalidArgumentException`; a missing home page during app resolution indicates `LogicException`. `Find::user()` throws `UserNotFoundException`.
- Kirby exception constructors accept named arguments (or a message string); `isTranslated()` is removed. Preserve `previous` when wrapping failures.
- `Request::domain()` and EXIF timestamps can be null. `Site::modified()`/`Dir::modified()` and `File::realpath()` can return false. Check before string/date operations.
- JSON encoding and `toJson()` helpers throw on invalid data. Invalid UTF-8 is not an empty successful response. `Response::json()` requires a boolean `pretty` argument.
- `Dom::query()` throws for an invalid selector; `File::sha1()` throws for a missing file. `Version::contentFile()` requires plain-text storage. `Dir::realpath()` rejects the directory itself as its containment argument.
- Trusted Panel text may be `HtmlString` rather than string, including translated UI/field text. Cast explicitly when a raw string is required, and avoid identity comparisons for independently created value objects. `V::value()` returns `true` or an error array.

## Sessions and authentication

Store serializable scalar/array data in sessions, not objects. Use `Kirby\Session\Data`, `Store`, `FileStore`, and `Sessions::factory()`. Handler access is `$kirby->sessions()`; `find($token)` selects a session, `create(['mode' => 'manual'])` creates a manual session, `detect()`/`track()` handle detection/tracking and `cookie()->name()`/`domain()` expose cookie properties.

Cookies are HMAC-SHA256 signed and secure by default on HTTPS. Do not weaken secure cookies to work around an incorrect proxy/HTTPS setup. A Kirby 5 upgrade invalidates previous session cookies and in-flight auth challenges; users log in again.

Use `$kirby->auth()->methods()`, `challenges()` and `limits()` for enabled methods/challenges and rate-limit operations. Custom methods use `Auth::guard()` for rate limiting and failure handling; method registration keys must match `type()`. Challenge secrets belong in hashed private `Pending` data, not exported props. Pending is not authenticated. See `kirby://kb/panel/compat-k5-k6-migration` and `kirby://kb/scenarios/43-user-registration-and-login`.

## HTTP and files

Content representations support `Accept` negotiation; see `kirby://glossary/content-representation`. `VolatileHeaders::append()` expects an array as its third argument. `qr()` accepts a site/page/file object or URL string.

MIME mappings use registered types: JavaScript `text/javascript`, XML `application/xml`, gzip/tgz `application/gzip`, ZIP `application/zip`, PSD `image/vnd.adobe.photoshop`, Excel `application/vnd.ms-excel`, PowerPoint `application/vnd.ms-powerpoint`, EXE `application/vnd.microsoft.portable-executable`, WBXML `application/vnd.wap.wbxml` and XSL `application/xslt+xml`. Update exact-string allowlists deliberately; a recognized MIME type does not authorize an upload. `application/octet-stream` maps to `exe`, not CSV; the removed `application/x-gzip-compressed` mapping returns false.

Additional mappings include WebAssembly, WebVTT, web manifests, APNG, EPUB, Atom and archive/audio formats. `Html::tag()` no longer treats obsolete `command`, `keygen` and `param` elements as void. Inspect actual output before changing HTML post-processing.

## Sources and coverage

This reference distills developer-facing contracts from the **6.0.0-beta.1** changelog, rechecked October 15, 2026. It complements task-specific guides rather than copying every internal refactor or cosmetic bug fix. Future prereleases require another source comparison, not an assumption that these contracts are frozen.

- https://getkirby.com/releases/6/changelog
- https://getkirby.com/releases/6/more-details
- https://getkirby.com/releases/6/migration/site-developers
- https://getkirby.com/releases/6/migration/plugin-developers/fields
