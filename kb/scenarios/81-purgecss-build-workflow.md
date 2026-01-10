# Scenario: PurgeCSS build workflow (remove unused CSS)

## Goal

Reduce CSS payload by purging unused selectors from a large framework CSS file by scanning your Kirby PHP templates/snippets.

## Inputs to ask for

- Input CSS path (framework file)
- Which files should be scanned for class usage (`site/**/*.php`, plugin UIs)
- Output directory/file strategy
- Safelist needs (classes generated dynamically)
- Whether to use CLI only (recommended) or a GUI tool (e.g. Prepros)

## Internal tools/resources to use

- Confirm project roots: `kirby://roots`
- Identify template/snippet locations: `kirby_templates_index`, `kirby_snippets_index`

## Implementation steps

1. Install PurgeCSS (CLI).
2. Add a `package.json` build script that scans `site/**/*.php`.
3. Optionally add `purgecss.config.js` for advanced configuration/safelisting.
4. Replace your CSS include with the purged output.
5. Create a `purge/` output directory if your script writes there.

## Examples (cookbook snippet)

`package.json` (excerpt)

```json
{
  "scripts": {
    "build": "npx purgecss -css assets/css/css-framework.css --content site/**/*.php --output purge/"
  }
}
```

## Verification

- Run the build and confirm the output CSS file is smaller.
- Confirm safelisted/dynamic classes aren’t removed unintentionally.

## Glossary quick refs

- kirby://glossary/snippet
- kirby://glossary/template
- kirby://glossary/roots

## Links

- Cookbook: Kirby meets PurgeCSS: https://getkirby.com/docs/cookbook/frontend/kirby-meets-purgecss
- PurgeCSS docs: https://purgecss.com/
