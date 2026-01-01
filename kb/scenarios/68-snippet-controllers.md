# Scenario: Snippet controllers (and block controllers) via plugin

## Goal

Move logic out of snippets/blocks into dedicated controller files, similar to page controllers:

- snippet controller: `site/controllers/snippets/<name>.php`
- block controller: `site/controllers/snippets/blocks/<block>.php`

## Inputs to ask for

- Which snippets/blocks need controllers
- Controller data requirements (queries, computed fields)
- Whether snippet output must stay cacheable/deterministic

## Internal tools/resources to use

- Inventory snippets/controllers: `kirby_snippets_index`, `kirby_controllers_index`
- Inventory plugins: `kirby_plugins_index`

## Implementation steps

1. Add the snippet-controllers plugin (or implement the pattern yourself).
2. Create controller files for targeted snippets/blocks.
3. Keep snippets focused on markup; keep data fetching in controllers.

## Examples

- Snippet controller: `site/controllers/snippets/header.php`
- Block controller: `site/controllers/snippets/blocks/video.php`

## Verification

- Confirm snippet markup stays clean and data is injected from the controller.
- Confirm blocks using snippet controllers still render in both frontend and Panel previews.

## Glossary quick refs

- kirby://glossary/snippet
- kirby://glossary/controller
- kirby://glossary/block
- kirby://glossary/blocks

## Links

- Quicktip: Snippet controllers: https://getkirby.com/docs/quicktips/snippet-controllers
- Reference: Snippet API: https://getkirby.com/docs/reference/objects/template/snippet
