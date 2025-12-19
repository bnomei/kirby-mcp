# Scenario: Add custom HTML to the Panel (info field)

## Goal

Show custom HTML in the Panel UI using the `info` field in a blueprint (e.g. onboarding content, embedded video).

## Inputs to ask for

- Where to show it (site blueprint, page blueprint, user blueprint)
- Whether the HTML should be static or dynamic (KQL placeholders like `{{ user.name }}`)
- Security/maintenance constraints (avoid risky embeds)

## Internal tools/resources to use

- Panel fields reference: `kirby://fields` + `kirby://field/info`
- Inspect blueprints: `kirby_blueprints_index` + `kirby://blueprint/{encodedId}`

## Implementation steps

1. Add an `info` field to the relevant blueprint.
2. Set `theme: none` and `label: ""` for a clean canvas.
3. Add HTML to the `text:` option (use YAML `|` multi-line strings).

## Examples (quicktip)

```yaml
info:
  label: ''
  type: info
  theme: none
  text: |
    <h2>Nice to see you, {{ user.name }}</h2>
```

## Verification

- Open the relevant blueprint in the Panel and confirm the info box renders.
- Confirm the HTML is safe/maintainable and doesn’t break Panel layout.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/blueprint
- kirby://glossary/field
- kirby://glossary/yaml

## Links

- Quicktip: Add custom HTML to Panel: https://getkirby.com/docs/quicktips/add-custom-html-to-panel
- Reference: Info field: https://getkirby.com/docs/reference/panel/fields/info
