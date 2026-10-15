---
name: kirby-content-migration
description: Plans and applies safe Kirby content migrations using runtime content tools, update schemas, and explicit confirmation. Use when users need to rename/move/transform fields, clean up content, or bulk-update pages/files across languages.
---

# Kirby Content Migration

Use Kirby 6 conventions for new work. For CMS upgrades, read `kirby://kb/scenarios/83-migrate-kirby-5-to-6` before planning content writes.

## Schema safety

- Inspect normalized model fields, not only raw `kirby_blueprint_read` configuration. `hasValue: false` excludes display fields; `null` means unresolved, not writable.
- Never write `pagelist`, `filelist`, `info`, `stats` or `buttons` values. Picker selections use `pagepicker`, `filepicker`, `userpicker`; read their canonical update-schema guides.
- Renaming a legacy picker type does not rename its stored key. Resolve existing targets; a new random UUID is not a relationship target.
- Audit Symfony YAML changes in nested content: quote dates/reserved scalars and use real booleans. Invalid options now survive saves; transform removed keys explicitly.
- Only follow actual update-schema links or entries in `kirby://fields/update-schema`; do not invent guides for arbitrary/display types.
- PHP updates can accept supported objects/collections; MCP arguments still require JSON storage payloads. Check serialization, empty-number behavior and validation in `kirby://kb/scenarios/84-kirby-6-core-contracts` before bulk writes.

## Quick start

- Follow the workflow below for safe, confirm-first migrations.

## KB entry points

- `kirby://kb/scenarios/16-batch-update-content`
- `kirby://kb/scenarios/74-update-blocks-programmatically`
- `kirby://kb/scenarios/34-content-file-cleanup-script`
- `kirby://kb/scenarios/75-update-file-metadata`

## Required inputs

- Source and target fields plus transform rules.
- Scope (templates/ids/sections) and batch size.
- Language and draft handling rules.
- Exclusions or derived fields that must not change.

## Plan template

- List source field, target field, transform rule, and a before/after example.
- Specify scope (templates/ids), languages, and draft handling.
- Note exclusions (derived fields, computed values, or generated files).

## Plan-validate-execute

- Write a `changes.json` plan with explicit page ids/uuids and new values.
- Validate the plan against blueprint and field schemas, plus a sample diff.
- Apply in batches (`confirm=false` preview, then `confirm=true`).

## Verification checklist

- Preview diffs for each field type before writing.
- Confirm language scope and batch boundaries.
- Re-render representative pages after apply.

## Common pitfalls

- Writing updates before reading field update schemas.
- Migrating large batches without confirm previews.

## Workflow

1. Ask for exact transformations, scope (pages/templates/sections), languages, draft handling, and any derived fields that must not be written.
2. Call `kirby:kirby_init`, then ensure runtime availability with `kirby:kirby_runtime_status` and `kirby:kirby_runtime_install` if needed.
3. Identify target pages:
   - Prefer explicit page ids/uuids from the user.
   - Otherwise derive a list using `kirby://roots` and the content directory structure.
4. Search the KB with `kirby:kirby_search` for related playbooks (examples: "batch update content", "update blocks programmatically", "content file cleanup script", "update file metadata").
5. Read field storage rules before writing:
   - `kirby://fields/update-schema`
   - existing `kirby://field/{type}/update-schema` links for involved value fields; resolve custom storage when no guide exists.
6. Read a small sample with `kirby:kirby_read_page_content` (or `kirby://page/content/{encodedIdOrUuid}`) and produce a diff-style preview.
7. Use `kirby://tool-examples` for safe, copy-ready `kirby:kirby_update_page_content` payloads.

## Apply

8. Call `kirby:kirby_update_page_content` with `confirm=false` to preview changes (set `payloadValidatedWithFieldSchemas=true`).
9. Ask for explicit confirmation, then re-run with `confirm=true` in small batches.
10. Stop on first error; summarize what applied vs skipped.

## Verify

11. Render representative pages with `kirby:kirby_render_page(noCache=true)` or re-read content to confirm the final state.
12. Report changes, remaining risks, and any follow-up manual checks.
