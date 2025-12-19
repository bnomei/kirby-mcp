# User (aliases: `$user`, `Kirby\Cms\User`, `$kirby->user()`)

## Meaning

In Kirby, a “user” is an account that can log into the Panel and/or be used as a frontend user (depending on permissions). A user has an id/email, a role (which defines permissions), optional custom fields from the role blueprint, and can have files (like an avatar).

In templates/routes/controllers, you typically obtain the current user via `$kirby->user()` and then branch on role/permissions.

## In prompts (what it usually implies)

- “Current user” / “logged in user” means: `$kirby->user()` (may be `null`).
- “Frontend users” usually means: users with `access.panel: false` but still allowed to do specific actions via the PHP API.
- “Create/update a user” implies authentication/permissions (or controlled impersonation) and usually shouldn’t be done blindly.

## Variants / aliases

- `$kirby->user()` (current user)
- `$kirby->user('you@yourdomain.com')` (fetch by id/email)
- `$kirby->users()` (collection of all users)
- `$user->role()` returns a kirby://glossary/role
- `$user->isAdmin()` (common permission shortcut)
- User blueprints (role “schema”): `site/blueprints/users/<role>.yml`

## Example

```php
<?php if ($user = $kirby->user()): ?>
  <p>Hello <?= $user->name()->escape() ?></p>
<?php endif ?>
```

## MCP: Inspect/verify

- Use `kirby_eval` to safely check what “current user” means in _this_ runtime context:
  - example: `return kirby()->user() ? kirby()->user()->email() : null;`
- Inspect role blueprints with `kirby_blueprints_index` and `kirby://blueprint/users%2F<role>`.
- If a prompt is about “why can’t this user do X”, inspect the role + permissions:
  - `kirby_eval`: `return kirby()->user()?->role()->toArray();`

## Related terms

- kirby://glossary/users
- kirby://glossary/role
- kirby://glossary/permissions
- kirby://glossary/session

## Links

- https://getkirby.com/docs/reference/objects/cms/user
- https://getkirby.com/docs/guide/users
- https://getkirby.com/docs/guide/users/roles
