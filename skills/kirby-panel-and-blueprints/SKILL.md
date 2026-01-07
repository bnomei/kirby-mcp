---
name: kirby-panel-and-blueprints
description: Design Kirby blueprints and Panel UI, including blueprint reuse/extends, programmable blueprints, and custom Panel fields/sections/areas. Use when changing the Panel experience or schema.
---

# Kirby Panel and Blueprints

## Workflow

1. Clarify the content model, required fields, and Panel UX expectations.
2. Call `kirby_init` and read `kirby://roots`.
3. Inspect existing blueprints and patterns:
   - `kirby_blueprints_index`
   - `kirby_blueprint_read`
4. Use Panel reference resources for field/section choices:
   - `kirby://fields`
   - `kirby://sections`
5. Check plugin surface when custom Panel UI is needed:
   - `kirby_plugins_index`
   - `kirby://extensions`
6. Search the KB with `kirby_search` (examples: "blueprints reuse extends", "programmable blueprints", "custom panel field", "custom panel section", "custom panel area", "panel branding", "custom html to panel", "blueprints in frontend", "structured field content", "authors via users field").
7. Implement minimal, convention-aligned YAML/PHP; prefer `extends` and shared sections over duplication.
8. Validate by re-reading the blueprint (`kirby_blueprint_read`) and verifying frontend output with `kirby_render_page` when relevant.
