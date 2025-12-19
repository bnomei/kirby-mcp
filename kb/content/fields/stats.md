# Stats field (type: stats)

## Field summary

UI-only statistics display in the Panel; no content is stored.

## Storage format

No storage. This field does not write to content files.

## Runtime value

No runtime value. If you read the field, it will be empty unless you wrote a value manually.

## Update payload (kirby_update_page_content)

Do not set this field key in update payloads.

## Merge strategy

Not applicable.

## Edge cases

Stats values are computed in the Panel, not persisted in content files.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/stats`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/stats
