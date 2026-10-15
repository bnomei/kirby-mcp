# User picker field (type: userpicker)

## Field summary

Kirby 6 value field for selected users. The Kirby 5 legacy name `users` is a deprecated alias: rename the type to `userpicker`, not the content key. This is not a display list.

## Storage and runtime value

Default storage is a YAML list of user UUID references. Preserve configured alternative storage (`store: id` or `store: email`) when migrating existing content. Disabling UUIDs forces ID storage in beta.1.

```yaml
authors:
  - user://aBc123XyZ
  - user://dEf456UvW
```

Resolve with `$page->authors()->toUsers()` or `->toUser()`. Use real users' references, not generated UUIDs without targets. Do not expose private account data when rendering public author information.

## Update payload (kirby_update_page_content)

```json
{ "authors": ["user://aBc123XyZ", "user://dEf456UvW"] }
```

Read, merge unique references if requested, preserve order/storage format, preview and apply. Respect `multiple`, `min`, `max` and role/candidate constraints. A single picker uses at most one reference. Re-read and resolve targets after saving; account deletion or changed emails can break references.

## MCP: Inspect/verify

- `kirby://field/userpicker`
- `kirby_blueprint_read` for raw configuration; runtime model schemas for normalized types and `hasValue`.
- `kirby_read_page_content` before and after the update.

## Links

- https://getkirby.com/releases/6/blueprints
- https://getkirby.com/docs/guide/uuids
