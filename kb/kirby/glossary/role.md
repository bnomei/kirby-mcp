# Role (aliases: user role, `Kirby\Cms\Role`, `site/blueprints/users/*.yml`)

## Meaning

In Kirby, a “role” defines what a user is allowed to do. Roles are defined by **user blueprints** in `site/blueprints/users/` (e.g. `editor.yml`). The `admin` role always exists and has full permissions.

Role blueprints can also add custom fields to user profiles (tabs/fields shown in the Panel).

## In prompts (what it usually implies)

- “Create an editor role” means: add a new user blueprint in `site/blueprints/users/` and (optionally) define permissions.
- “Role-based access” means: set `permissions` in the user blueprint and/or override in page/file/site blueprints.
- “Admin can do anything” is usually correct (admin ignores custom permission settings).

## Variants / aliases

- User blueprint file: `site/blueprints/users/<role>.yml`
- Role object: `$user->role()` (returns `Kirby\Cms\Role`)
- `$kirby->role('editor')` (fetch a specific role by id)
- Admin role is mandatory and cannot be removed

## Example

Minimal role blueprint with restricted Panel access:

```yaml
title: Member

permissions:
  access:
    panel: false
```

## MCP: Inspect/verify

- Use `kirby_roots` to locate the effective `blueprints` root.
- Use `kirby_blueprints_index` to find existing user blueprints (ids like `users/<role>`).
- Read a role blueprint via `kirby://blueprint/users%2F<role>` to confirm permissions and custom fields.
- Use `kirby_eval` to check what role a user actually has:
  - example: `return kirby()->user()?->role()->id();`

## Related terms

- kirby://glossary/user
- kirby://glossary/users
- kirby://glossary/permissions
- kirby://glossary/blueprint

## Links

- https://getkirby.com/docs/guide/users/roles
- https://getkirby.com/docs/reference/objects/cms/role

