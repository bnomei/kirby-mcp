# Info field (type: info)

## Field summary

UI-only help text in the Panel; no content is stored.

## Storage format

No storage. This field does not write to content files.

## Runtime value

No runtime value. If you read the field, it will be empty unless you wrote a value manually.

## Update payload (kirby_update_page_content)

Do not set this field key in update payloads.

## Merge strategy

Not applicable.

## Edge cases

Kirby 6 normalizes former built-in info sections into this non-value field. MCP schemas keep it with `hasValue: false` and do not emit an update-schema link for it. This retained guide explains exclusion, not a writable payload; never rely on manually writing a display key.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/info`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/info
