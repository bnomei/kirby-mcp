---
name: kirby-content-migration
description: Plan and apply safe Kirby content migrations using runtime content tools, update schemas, and explicit confirmation. Use when a user needs to rename/move/transform fields, clean up content, or bulk-update pages/files across languages.
---

# Kirby Content Migration

## Quick start

- Follow the workflow below for safe, confirm-first migrations.

## Workflow

1. Ask for exact transformations, scope (pages/templates/sections), languages, draft handling, and any derived fields that must not be written.
2. Call `kirby_init`, then ensure runtime availability with `kirby_runtime_status` and `kirby_runtime_install` if needed.
3. Identify target pages:
   - Prefer explicit page ids/uuids from the user.
   - Otherwise derive a list using `kirby://roots` and the content directory structure.
4. Search the KB with `kirby_search` for related playbooks (examples: "batch update content", "update blocks programmatically", "content file cleanup script", "update file metadata").
5. Read field storage rules before writing:
   - `kirby://fields/update-schema`
   - `kirby://field/{type}/update-schema` for each involved field type.
6. Read a small sample with `kirby_read_page_content` (or `kirby://page/content/{encodedIdOrUuid}`) and produce a diff-style preview.
7. Use `kirby://tool-examples` for safe, copy-ready `kirby_update_page_content` payloads.

## Apply

8. Call `kirby_update_page_content` with `confirm=false` to preview changes (set `payloadValidatedWithFieldSchemas=true`).
9. Ask for explicit confirmation, then re-run with `confirm=true` in small batches.
10. Stop on first error; summarize what applied vs skipped.

## Verify

11. Render representative pages with `kirby_render_page(noCache=true)` or re-read content to confirm the final state.
12. Report changes, remaining risks, and any follow-up manual checks.
