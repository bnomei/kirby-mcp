# Status (aliases: draft, unlisted, listed, `_drafts`)

## Meaning

Kirby pages have three core states:

- **Draft**: stored in a `_drafts` folder; not publicly accessible unless authenticated (or via secret draft link)
- **Unlisted**: public, but not part of listed navigation/sorting
- **Listed**: public and ordered (number prefix)

These states affect how pages are stored on disk and how they appear in collections (`children()`, navigation, etc.).

## In prompts (what it usually implies)

- “Include drafts” means: use `$page->drafts()` or `$page->childrenAndDrafts()` (drafts aren’t in `$page->children()`).
- “Make it listed/unlisted” means: change status (Panel) and possibly adjust folder numbering for sorting.
- “Draft not found via `page()`” means: `page()` fetches published pages only; use `$kirby->page('…')` for drafts.

## Variants / aliases

- Folder: `_drafts`
- Common API calls:
  - `$page->children()`, `$page->drafts()`, `$page->childrenAndDrafts()`
  - `$page->listed()`, `$page->unlisted()` (collections)

## Example

```php
<?php foreach ($page->childrenAndDrafts() as $child): ?>
  <a href="<?= $child->url() ?>"><?= $child->title()->escape() ?></a>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Use `kirby_render_page` to verify what the frontend outputs for a specific page id/uuid.
- Use `kirby_eval` to check state in runtime without guessing:
  - example: `return [$page->isDraft(), $page->isListed(), $page->isUnlisted()];`
- Use `kirby_read_page_content` to confirm you’re reading the current version and not the wrong language/state.

## Related terms

- kirby://glossary/page
- kirby://glossary/content
- kirby://glossary/route

## Links

- https://getkirby.com/docs/guide/content/publishing-workflow
