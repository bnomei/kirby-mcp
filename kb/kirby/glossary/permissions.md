# Permissions (aliases: `access.panel`, role permissions, blueprint permissions)

## Meaning

Kirby permissions control which actions a user can perform in the Panel, in Kirby’s PHP API, and (if enabled) in the REST API. Permissions are typically defined per **role** in user blueprints (`site/blueprints/users/<role>.yml`) and can be overridden more granularly in page/file/user/site blueprints.

Permissions are evaluated at runtime and can also be enforced/extended via hooks for complex rules.

## In prompts (what it usually implies)

- “User can’t access the Panel” usually means `access.panel: false` for that role.
- “User can edit their own profile but not other users” is controlled by the distinction between `user` (self) and `users` (other users) permissions.
- “Let users do X from the frontend” means: configure permissions for PHP API actions (even if Panel access is disabled).

## Variants / aliases

- Role-based permissions in `site/blueprints/users/<role>.yml` under `permissions:`
- Overrides in blueprints for specific models (page/file/user/site)
- Wildcard `*` in permission rules
- `$role->permissions()` (permissions object attached to a role)
- `$kirby->impersonate('kirby')` (powerful escape hatch; use with care and preferably with a callback)

## Example

Disallow access to other users but keep Panel access:

```yaml
title: Editor

permissions:
  access:
    panel: true
  users: false
```

## MCP: Inspect/verify

- Inspect user role blueprints with `kirby_blueprints_index` + `kirby://blueprint/users%2F<role>`.
- If permissions behave differently than expected, confirm runtime identity with `kirby_eval`:
  - example: `return kirby()->user()?->role()->permissions()->toArray();`
- If a prompt touches config-based permissions/routes/hooks, inspect the effective options via `kirby://config/{option}`.
- If access is enforced in custom routes, locate the route definition with `kirby_routes_index(patternContains='…')` (requires `kirby_runtime_install`).

## Related terms

- kirby://glossary/role
- kirby://glossary/user
- kirby://glossary/route
- kirby://glossary/hook
- kirby://glossary/option

## Links

- https://getkirby.com/docs/guide/users/permissions
- https://getkirby.com/docs/guide/users/roles
- https://getkirby.com/docs/reference/objects/cms/role/permissions
- https://getkirby.com/docs/reference/objects/cms/app/impersonate
