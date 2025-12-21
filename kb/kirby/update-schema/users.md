# Users field (type: users)

## Field summary

User picker stored as user references (uuid by default, email when configured).

## Storage format

```yaml
users:
  - user://aBc123XyZ
  - user://dEf456UvW
```

## Runtime value

Use `$page->users()->toUsers()` for multiple or `->toUser()` for single.

## Update payload (kirby_update_page_content)

```json
{ "users": ["user://aBc123XyZ", "user://dEf456UvW"] }
```

## Merge strategy

Read existing references, merge unique values, then write back using the same store format (uuid or email).

## Edge cases

With `store: email`, values are user emails. Keep `multiple` in sync with payload shape.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/users`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/users
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/fields/users
