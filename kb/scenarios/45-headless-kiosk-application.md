# Scenario: Headless kiosk application (Kirby as backend)

## Goal

Use Kirby as a headless backend for a kiosk-style frontend app.

This is a larger system integration scenario where MCP can help most with:

- Kirby-side API configuration and data modeling
- content structure/blueprints for editors
- JSON endpoints/content representations

## Inputs to ask for

- Kiosk frontend stack (Electron, static site generator, mobile wrapper, etc.)
- Data requirements (which pages/files, update frequency, offline needs)
- Auth model and deployment constraints

## Internal tools/resources to use

- Confirm environment: `kirby://info`
- Inspect content structure/blueprints:
  - `kirby_blueprints_index`, `kirby_models_index`
  - `kirby_templates_index`, `kirby_controllers_index`

## Implementation steps

1. Model kiosk content in Kirby (page types + blueprints).
2. Expose required data through the API (often via KQL or custom endpoints).
3. Keep the frontend app decoupled: treat Kirby as the source of truth.

## Examples

- KQL-driven payloads via `POST /api/query` (e.g. `page('exhibits').children`).
- Dedicated JSON representations for kiosk screens (e.g. `exhibits.json`).

## Verification

- Validate that the API returns the kiosk-required payloads deterministically.
- Validate caching/offline behavior in the kiosk frontend stack.

## Glossary quick refs

- kirby://glossary/api
- kirby://glossary/blueprint
- kirby://glossary/kql

## Links

- Cookbook: Headless kiosk application: https://getkirby.com/docs/cookbook/headless/headless-kiosk-application
- Cookbook: Headless getting started: https://getkirby.com/docs/cookbook/headless/headless-getting-started
