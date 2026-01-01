# Blueprint: user (content update)

## Blueprint summary

User blueprints define editable profile fields for users. Updates map to `$user->update($data, $language, $validate)`.

## Storage format

User content is stored in the account folder (e.g. `site/accounts/<id>/`), alongside credentials.

```yaml
street: 1 Main St
city: Berlin
```

## Runtime value

`$user->content($language)->toArray()` returns a field-value map.

## Update payload (kirby_update_user_content)

```json
{ "city": "Berlin", "street": "1 Main St" }
```

## Merge strategy

Replace field values by key. For merges/append, read existing values, merge in memory, then update.

## Edge cases

- Users are resolved by id or email, not by a full UUID.
- Updating credentials (email, password, role) is possible but should be done carefully.
- Complex fields (blocks/layout/structure) require their own update schemas.

## MCP: Inspect/verify

- Read content: `kirby_read_user_content` or `kirby://user/content/{encodedIdOrEmail}`
- Read blueprint config: `kirby_blueprint_read` or `kirby://blueprint/users%2Fdefault`
- Field storage guidance: `kirby://fields/update-schema`, `kirby://field/{type}/update-schema`

## Glossary quick refs

- kirby://glossary/user
- kirby://glossary/users
- kirby://glossary/content
- kirby://glossary/field

## Links

- https://getkirby.com/docs/reference/panel/blueprints/user
