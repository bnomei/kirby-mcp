# Scenario: Tailwind CSS build workflow for Kirby

## Goal
Integrate Tailwind CSS with a Kirby project using a simple CLI build that outputs to `assets/`.

## Inputs to ask for
- Whether Node/npm is available in the environment
- Input CSS entry file location (e.g. `src/tailwind.css`)
- Output CSS path (e.g. `assets/css/styles.css`)
- Whether minification is required in production builds

## Internal tools/resources to use
- Confirm project roots: `kirby://roots`
- Inspect existing asset includes in templates/snippets: `kirby_snippets_index`

## Implementation steps
1. Add `package.json` scripts for watch/build.
2. Create `src/tailwind.css` entry file.
3. Install Tailwind CLI dependency.
4. Include the generated CSS in your templates via `css('assets/css/styles.css')`.

## Examples (cookbook snippets)
`package.json` (excerpt)
```json
{
  "scripts": {
    "watch": "npx @tailwindcss/cli -i ./src/tailwind.css -o ./assets/css/styles.css -w",
    "build": "npx @tailwindcss/cli -i ./src/tailwind.css -o ./assets/css/styles.css -m"
  }
}
```

## Verification
- Run `npm run watch` locally and confirm `assets/css/styles.css` updates.
- Load a page and confirm `css('assets/css/styles.css')` includes the generated Tailwind output.

## Glossary quick refs

- kirby://glossary/asset
- kirby://glossary/snippet
- kirby://glossary/template
- kirby://glossary/roots

## Links
- Cookbook: Kirby meets Tailwind CSS: https://getkirby.com/docs/cookbook/frontend/kirby-meets-tailwindcss
- Guide: Assets helper `css()`: https://getkirby.com/docs/reference/templates/helpers/css
