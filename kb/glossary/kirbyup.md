# kirbyup (aliases: "kirbyup bundler")

## Meaning

kirbyup builds Kirby Panel plugin assets with Vite. For Kirby 6, use kirbyup 4, Node.js 24+ and Vue 3; `dev` runs the hot-reload server. Rebuild Kirby 5 bundles rather than reusing their Vue 2 output.

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
