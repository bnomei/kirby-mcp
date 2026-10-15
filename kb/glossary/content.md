# Content (aliases: `content/`, “content file”, `.txt`, `.md`)

## Meaning

Kirby is a flat-file CMS: most content lives in the **content root** as folders (pages) and text files (fields). A page is typically represented by a folder and a content file inside that folder.

Content files are `.txt` by default, but the extension can be configured (e.g. `.md`). Drafts live in a `_drafts` folder under their parent page.

## In prompts (what it usually implies)

- “Where is the content stored?” means: locate the `content` root (don’t assume it’s `./content`).
- “Why can’t I find a field value?” means: check the actual page content file (and language/changes status).
- “This page is a draft” means: it may be under `_drafts` and not part of normal children collections.

## Variants / aliases

- Content roots can be customized (“roots”): see kirby://glossary/roots
- Content file extension is configurable via `content.extension` (e.g. `txt` vs `md`)
- Page states: draft/unlisted/listed (see kirby://glossary/status)

## Saved content, changes, and previews

Kirby distinguishes saved content (`latest`) from the Panel's unpublished edits (`changes`). These content versions are separate from a page's draft/unlisted/listed status and from its content language. A published page can still have unpublished edits.

Use `$model->version('latest')->read($language)` when a migration needs only the saved values for an exact language without fallback; `null` means no saved content exists for that language. Do not treat a Panel preview as proof that edits have been published. Editing locks are also separate: leaving a Panel view releases its lock; unlocking does not publish or discard changes.

Kirby 6 uses Symfony YAML by default; audit stored YAML dates, booleans and reserved-leading-character strings before bulk migration. Display fields such as `pagelist`/`filelist` are not content values; picker fields store references. See `kirby://glossary/yaml`.

## Example

```text
Title: An interesting article

----

Text:
Really interesting content here...
```

## MCP: Inspect/verify

- Resolve the real content root first: `kirby_roots` (or `kirby://roots`).
- Read a page’s current content (drafts/changes-aware):
  - `kirby_read_page_content` or `kirby://page/content/{encodedIdOrUuid}`
- Check content handling config (runtime install required):
  - `kirby://config/content.extension`
  - `kirby://config/content.uuid`
- Validate what a template actually renders from content with `kirby_render_page` (use `noCache=true` if output seems stale).

## Related terms

- kirby://glossary/page
- kirby://glossary/field
- kirby://glossary/status
- kirby://glossary/uuid
- kirby://glossary/roots

## Links

- https://getkirby.com/docs/guide/content
- https://getkirby.com/docs/guide/content/creating-pages
- https://getkirby.com/docs/guide/content/fields
- https://getkirby.com/docs/reference/system/options/content
- https://getkirby.com/docs/cookbook/development-deployment/content-file-cleanup
- https://github.com/getkirby/kirby/releases/tag/5.5.0
