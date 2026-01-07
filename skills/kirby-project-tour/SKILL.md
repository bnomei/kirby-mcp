---
name: kirby-project-tour
description: Map a Kirby project using Kirby MCP tools/resources, including roots, templates, snippets, controllers, models, blueprints, plugins, runtime status, and key config. Use when a user wants a project overview, file locations, or a quick orientation before making changes.
---

# Kirby Project Tour

## Quick start

- Follow the workflow below for a structured tour.

## Workflow

1. Call `kirby_init` to capture versions and composer audit details.
2. Read `kirby://roots` and summarize where templates, snippets, controllers, models, blueprints, content, config, and plugins live.
3. Inventory project surface (prefer parallel calls):
   - `kirby_templates_index`
   - `kirby_snippets_index`
   - `kirby_controllers_index`
   - `kirby_models_index`
   - `kirby_blueprints_index`
   - `kirby_plugins_index`
4. If runtime-backed data is needed, check `kirby_runtime_status` and run `kirby_runtime_install` if required, then retry indexes.
5. Read key config values when relevant: `kirby://config/debug`, `kirby://config/cache`, `kirby://config/routes`, `kirby://config/languages`.
6. Use `kirby_search` to jump into task playbooks (examples: "scaffold page type", "custom routes", "search page", "custom blocks").
7. If you hit unfamiliar terms, consult `kirby://glossary` and `kirby://glossary/{term}`.
8. If you are unsure which tool/resource to use next, call `kirby_tool_suggest` or read `kirby://tools`.

## Output checklist

- Provide a "where to edit what" cheat sheet (template vs controller vs snippet vs blueprint vs content vs config).
- Highlight notable customizations (page models, plugins, blueprint overrides, unusual roots).
- Offer 3 next-step recommendations (DX, performance, security).
