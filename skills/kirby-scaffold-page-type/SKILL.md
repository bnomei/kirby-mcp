---
name: kirby-scaffold-page-type
description: Scaffold a new Kirby page type (blueprint + template, optional controller/model) using project roots, index tools, and Panel field/section references. Use when creating a new page type or extending an existing blueprint/template.
---

# Kirby Scaffold Page Type

## Quick start

- Follow the workflow below to scaffold a new page type safely.

## Workflow

1. Ask for page type name, required fields, Panel UX expectations, and whether to extend an existing type.
2. Call `kirby_init` and read `kirby://roots` to locate templates, blueprints, controllers, models, and snippets.
3. Check for name collisions and existing patterns:
   - `kirby_templates_index`
   - `kirby_blueprints_index`
   - `kirby_controllers_index`
   - `kirby_models_index`
4. If extending an existing type, read it with `kirby_blueprint_read` before generating new files.
5. Use Panel reference resources for field and section choices:
   - `kirby://fields`
   - `kirby://sections`
6. Search the KB with `kirby_search` (examples: "scaffold page type", "blueprints reuse extends", "programmable blueprints", "custom post types", "create a blog section", "one pager site sections", "authors via users field").
7. Create minimal, convention-aligned files; prefer snippets for reusable view logic.
8. Verify with `kirby_render_page(noCache=true)` when runtime is available; otherwise run `kirby_runtime_status` and `kirby_runtime_install` first.
9. Optionally run `kirby_ide_helpers_status` and `kirby_generate_ide_helpers` (dry-run first) to keep IDE types in sync.
