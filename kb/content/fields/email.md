# Email field (type: email)

## Field summary

Email input stored as a plain string.

## Storage format

```yaml
email: hello@example.com
```

## Runtime value

`$page->email()->value()` returns the stored email string.

## Update payload (kirby_update_page_content)

```json
{ "email": "hello@example.com" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

Panel validation may reject invalid formats; store the canonical address string.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/email`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/email
