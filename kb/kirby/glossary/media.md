# Media (aliases: `media/`, `$file->mediaUrl()`, “thumbs folder”)

## Meaning

Kirby’s `media` folder contains generated, cacheable runtime files like image thumbs and other file versions. In a default setup, thumbs are stored under `/media` and grouped into hashed subfolders.

If your project uses thumbnails heavily, the media folder is often where “where did all this disk usage come from?” investigations end up.

## In prompts (what it usually implies)

- “Clear thumbnails” often means clearing parts of the `media` folder (or running a CLI clear command).
- “Pre-generate thumbs and deploy them” means ensuring consistent hashing across environments (often via `content.salt`).
- “Why are thumb URLs different between envs?” is often the same hashing/salt issue.

## Variants / aliases

- Configurable root: `media` (see kirby://glossary/roots)
- Related methods:
  - `$file->mediaUrl()`, `$file->mediaRoot()`
  - `$page->mediaUrl()`, `$page->mediaRoot()`
- Hashing is influenced by `$kirby->contentToken()` and (when set) `content.salt`

## Example

```php
<?php if ($image = $page->image()): ?>
  <a href="<?= $image->mediaUrl() ?>"><?= $image->filename() ?></a>
<?php endif ?>
```

## MCP: Inspect/verify

- Use `kirby_roots` to locate the resolved `media` root (it may be customized).
- Inspect thumb-related config via `kirby://config/thumbs` and `kirby://config/content.salt`.
- To discover project-specific CLI maintenance commands, read `kirby://commands` and use `kirby_run_cli_command` if needed.

## Related terms

- kirby://glossary/thumb
- kirby://glossary/cache
- kirby://glossary/option
- kirby://glossary/roots

## Links

- https://getkirby.com/docs/guide/files/resize-images-on-the-fly
- https://getkirby.com/docs/reference/system/options/thumbs
- https://getkirby.com/docs/reference/system/options/content#salt-for-drafts-and-media-files

