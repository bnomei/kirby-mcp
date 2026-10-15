# kirbyuse

## Meaning

kirbyuse provides Panel composables and typings. Use the Kirby-6/Vue-3 prerelease line for Kirby 6; legacy 1.x targets Kirby 4/5. Bundle kirbyuse and leave Vue external: tagged Kirby 6 beta.1 supplies a `vue` import map, not a plugin import-map registration API.

## In prompts (what it usually implies)

- "Use kirbyuse" means importing compatible composables into the plugin build; see `kirby://kb/panel/tooling-kirbyuse` before changing loaders.

## Example

```js
import { usePanel, useContent } from 'kirbyuse';
```

## MCP: Inspect/verify

- `kirby://kb/panel/tooling-kirbyuse`

## Related terms

- kirby://glossary/panel
- kirby://glossary/plugin
- kirby://glossary/kirbyup

## Links

- https://github.com/johannschopplich/kirbyuse
