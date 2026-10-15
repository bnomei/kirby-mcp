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

## Kirby 6 guards and role permissions

Use `$model->guards()->isAvailable('update')` for model action availability and `ensureExecutable()` for execution checks. Kirby 5 legacy `$model->permissions()->can()` and model Rules classes are deprecated. Role permissions and `$role->permissions()` are a different API; keep role configuration and blueprint options. Hiding a control never authorizes a backend action.

Availability checks abilities and permissions; executability also validates action arguments. `isAvailable()`/`isExecutable()` return booleans, while `ensureAvailable()`/`ensureExecutable()` throw explaining exceptions. Kirby actions run their own guards; explicit preflight is useful before a multi-item operation but is not a transaction or a substitute for checks during execution.

Abilities protect invariants even for the privileged `kirby` user. Anonymous users have the `nobody` role. Model blueprint permission rules take precedence over role rules; keep denied-role tests when changing either. See https://getkirby.com/releases/6/guards.

## Kirby 5 legacy maintenance context

The following history helps audit an existing Kirby 5 site before migration. On Kirby 6 keep role rules, use guards for model checks, and use `pagepicker`/`filepicker`/`userpicker` for stored references; do not delete inaccessible references during migration.

- Use a patched Kirby 5 release (5.6.1 at this maintenance pass). Earlier 5.x versions had permission-enforcement gaps; hiding a Panel control is not an authorization check.
- `access.system` controls the system view; the old `access.settings` key has no effect.
- Since 5.4, `site.access` and user/self `access` and `list` permissions control additional read access. `user` concerns the acting user's own account; `users` concerns other accounts.
- User-blueprint `options` describe the **target account's role**; role keys inside an option identify the **acting user's role**. Role-specific options override corresponding permissions; otherwise Kirby falls back to the acting user's permissions. Changing role checks the old role's `changeRole` and the new role's `create` option.
- Since 5.4, denying `pages.changeStatus` also prevents creating an already-published page. To allow publication on creation but forbid later status changes, allow the permission and enforce the latter rule in `page.changeStatus:before`.
- Register custom permissions through the `permissions` plugin extension; unregistered custom permissions are deprecated since 5.5. Panel areas automatically provide `access.<areaId>`.
- Since 5.6, inaccessible references in pages/files/users fields display `–` without deleting the stored value. Do not remove references merely because the acting user cannot see them.
- `site.preview` governs site preview; in Kirby 5 the home page's preview permission/option also matters.

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
- https://getkirby.com/docs/reference/panel/blueprints/user
- https://getkirby.com/docs/reference/plugins/extensions/permissions
- https://github.com/getkirby/kirby/releases/tag/5.4.0
- https://github.com/getkirby/kirby/releases/tag/5.5.0
- https://github.com/getkirby/kirby/releases/tag/5.6.0
