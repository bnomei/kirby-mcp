# Users (aliases: `$users`, `Kirby\Cms\Users`, `$kirby->users()`)

## Meaning

“Users” refers to the collection of all users in a Kirby site (`Kirby\Cms\Users`). You usually get this collection via `$kirby->users()` and then filter/sort/select user objects.

## In prompts (what it usually implies)

- “List all users” means iterating `$kirby->users()`.
- “Only admins/editors” means filtering by role or a predicate (e.g. `isAdmin`).
- “Find a user by email” means `$kirby->user('email')` or `$users->find('id')` depending on what identifier you have.

## Variants / aliases

- `$kirby->users()` (all users)
- `$users->filterBy('isAdmin')` (admins)
- `$users->pluck('email')` (common extraction)
- `$users->sortBy('role')`

## Example

```php
<?php foreach ($kirby->users()->sortBy('email') as $user): ?>
  <li><?= $user->email()->escape() ?></li>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Use `kirby_eval` to quickly inspect what the site considers “users”:
  - example: `return kirby()->users()->pluck('email');`
- If a user list depends on permissions (Panel/API), inspect [roles](kirby://glossary/role) and kirby://glossary/permissions.

## Related terms

- kirby://glossary/user
- kirby://glossary/role
- kirby://glossary/permissions

## Links

- https://getkirby.com/docs/reference/objects/cms/users
- https://getkirby.com/docs/guide/users

