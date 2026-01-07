---
name: kirby-ide-support
description: Improve IDE autocomplete and static analysis in Kirby projects with PHPDoc hints and Kirby IDE helper generation. Use when types are missing or IDE support is degraded.
---

# Kirby IDE Support

## Quick start

- Follow the workflow below for a minimal, types-only IDE pass.

## Workflow

1. Call `kirby_init`, then check status with `kirby_ide_helpers_status`.
2. Inspect templates/snippets/controllers/models for missing hints:
   - `kirby_templates_index`
   - `kirby_snippets_index`
   - `kirby_controllers_index`
   - `kirby_models_index`
3. Add minimal, types-only improvements:
   - `@var` hints in templates/snippets
   - typed controller closures
   - ensure page models extend the correct base class
4. If generating helpers, run `kirby_generate_ide_helpers(dryRun=true)` first; ask before writing, then run with `dryRun=false`.
5. Re-run `kirby_ide_helpers_status` and summarize changes.
6. Search the KB with `kirby_search` (example: "ide support").
