# Scenario: Permission tricks (role-based read access + blueprint overrides)

## Goal
Implement advanced role-based permissions, such as:
- different blueprints per role
- restricting read access to content for certain roles
- custom behavior via page/file/user models

## Inputs to ask for
- Roles involved and what each role can do (read/write/publish)
- Whether restrictions apply in the Panel, frontend, or both
- Where the rules should live (permissions config vs model overrides)

## Internal tools/resources to use
- Inspect blueprints and overrides: `kirby_blueprints_index`
- Inspect models: `kirby_models_index`
- Inventory plugins: `kirby_plugins_index`

## Implementation steps
1. Decide if the rule is UI-only (blueprints) or enforcement (permissions/model).
2. Implement the restriction:
   - role-specific blueprint resolution (plugin override)
   - model method overrides like `isReadable()` (where applicable)
3. Test with real users/roles.

## Examples
- Use role-specific blueprints to tailor the Panel UI.
- Use model overrides to enforce access restrictions beyond the UI layer.

## Verification
- Log in with each role and confirm access is correctly restricted.
- Confirm restrictions apply both in Panel and frontend where intended.

## Glossary quick refs

- kirby://glossary/role
- kirby://glossary/blueprint
- kirby://glossary/panel
- kirby://glossary/permissions

## Links
- Cookbook: Permission tricks: https://getkirby.com/docs/cookbook/security/permission-tricks
- Guide: Users/roles: https://getkirby.com/docs/guide/users
