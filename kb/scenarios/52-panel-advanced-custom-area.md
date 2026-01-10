# Scenario: Advanced Panel area (custom views + API endpoints)

## Goal

Build a more advanced custom Panel area that:

- has multiple views/routes inside the area
- loads data from view actions (and optional dialogs/dropdowns/searches)
- uses a richer Vue UI

## Inputs to ask for

- Data model (what the area manages)
- Required views (list, detail, create, edit)
- Authorization/permissions (which roles can access)
- Whether data should be stored in Kirby content, in files, or external systems

## Internal tools/resources to use

- Confirm roots: `kirby://roots`
- Inventory plugin setup: `kirby_plugins_index`
- Inspect routes/api config: `kirby://config/routes`, `kirby://config/api`
- If you implement backend endpoints via routes, locate them with `kirby_routes_index(patternContains='…')` (requires `kirby_runtime_install`)

## Implementation steps

1. Extend the area definition to multiple view patterns.
2. Provide backend data via view actions (and optional dialogs/dropdowns/searches).
3. Consume the props/data from Vue components.
4. Keep the boundary clear: Panel UI uses HTTP; backend enforces permissions.

## Examples

- Add a list view (`pattern: 'products'`) and a detail view (`pattern: 'products/(:any)'`).
- Provide a route/API endpoint that returns JSON for the list/detail payloads.

## Verification

- Confirm every view route resolves in the Panel.
- Confirm backend endpoints reject unauthorized access.
- Confirm backend routes are registered and locate their definition with `kirby_routes_index(patternContains='…')`.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/kirbyuse
- kirby://glossary/route
- kirby://glossary/api
- kirby://glossary/plugin

## Links

- Cookbook: Advanced Panel area: https://getkirby.com/docs/cookbook/panel/advanced-panel-area
- Reference: Panel areas: https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- Guide: Panel plugin setup: https://getkirby.com/docs/guide/plugins/plugin-setup-panel
