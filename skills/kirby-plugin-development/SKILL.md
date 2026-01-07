---
name: kirby-plugin-development
description: Build or extend Kirby plugins using hooks, extensions, blocks, KirbyTags, and shared templates/controllers. Use when creating reusable features or integrating Panel customizations.
---

# Kirby Plugin Development

## Workflow

1. Define the plugin id (vendor/name), feature scope, and whether it must be reusable across projects.
2. Call `kirby_init` and read `kirby://roots` to locate plugin roots.
3. Inspect existing plugins to avoid duplication: `kirby_plugins_index`.
4. Use extension and hook references:
   - `kirby://extensions` and `kirby://extension/{name}`
   - `kirby://hooks` and `kirby://hook/{name}`
5. Search the KB with `kirby_search` (examples: "kirbytext hooks", "extend kirbytags", "columns in kirbytext", "custom blocks", "nested blocks", "snippet controllers", "share templates via plugin", "shared controllers", "replace core classes", "pdf preview images", "plugin workflow", "monolithic plugin setup", "panel plugin without bundler").
6. Implement the plugin with a minimal `index.php` registration, then add blueprints/snippets/assets as needed.
7. Verify by rendering affected pages with `kirby_render_page` and confirming the plugin loads without errors.
