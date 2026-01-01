# Scenario: IndieAuth/RelMeAuth (`rel="me"` links)

## Goal

Add `rel="me"` links so IndieAuth/RelMeAuth services can verify identity across profiles.

## Inputs to ask for

- Which profiles should be linked (Mastodon, GitHub, Bluesky, etc.)
- Whether links should be `<a rel="me">` in the body or `<link rel="me">` in the head (both work)
- Where the links should be managed (hardcoded snippet vs site fields)

## Internal tools/resources to use

- Inventory snippets/templates: `kirby_snippets_index`, `kirby_templates_index`
- Validate output: `kirby_render_page` (confirm the tags appear in HTML head/body)

## Implementation steps

1. Decide where the links live:
   - in a header snippet (`site/snippets/header.php`)
   - in a dedicated snippet (e.g. `site/snippets/rel-me.php`)
   - or as Panel-managed fields on the site object
2. Render links with `rel="me"` attributes.

## Examples

### Head links

```html
<link rel="me" href="https://mastodon.social/@example" /> <link rel="me" href="https://github.com/example" />
```

### Body links

```html
<a rel="me" href="https://mastodon.social/@example">Find me on Mastodon</a>
```

## Verification

- View page source and confirm the `rel="me"` links are present.

## Glossary quick refs

- kirby://glossary/snippet
- kirby://glossary/field
- kirby://glossary/template

## Links

- Cookbook: IndieAuth: https://getkirby.com/docs/cookbook/integrations/indieauth
