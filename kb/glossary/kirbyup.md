# kirbyup (aliases: "kirbyup bundler")

## Meaning

kirbyup is a Vite-based bundler for Kirby Panel plugin assets. Kirby 6 beta.1 uses kirbyup 4 (published under `next` at this baseline), Node.js 24+ and Vue 3, including HMR via `dev`. Kirby 5 legacy kirbyup 3/`serve` builds must be rebuilt with version 4 for Kirby 6.

## In prompts (what it usually implies)

- "Use kirbyup" means add build scripts and output JS/CSS for a Panel plugin.

## Example

```json
{
  "scripts": {
    "dev": "kirbyup dev src/index.js",
    "build": "kirbyup src/index.js"
  }
}
```

## MCP: Inspect/verify

- `kirby://kb/panel/tooling-kirbyup`
- `kirby://kb/panel/tooling-bundling`

## Related terms

- kirby://glossary/panel
- kirby://glossary/plugin

## Links

- https://github.com/johannschopplich/kirbyup
- https://kirbyup.getkirby.com
