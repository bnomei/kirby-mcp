---
name: kirby-upgrade-and-maintenance
description: Upgrade Kirby and maintain dependencies safely using composer audit, plugin compatibility checks, and official docs. Use when updating Kirby versions or making maintenance changes that affect runtime.
---

# Kirby Upgrade and Maintenance

## Quick start

- Follow the workflow below for safe, incremental upgrades.

## Workflow

1. Call `kirby_init` or gather baseline data with `kirby_info` and `kirby_composer_audit`.
2. Inventory plugins for compatibility risks: `kirby_plugins_index`.
3. Use `kirby_online` to find official upgrade guides and breaking changes for the target version (prefer `kirby_search` first).
4. Build a project-specific checklist of required code/config changes.
5. Ask for confirmation before dependency updates that change the lockfile.
6. Verify:
   - run project scripts discovered in the composer audit
   - call `kirby_cli_version` to confirm the installed version
   - ensure runtime commands are in sync: `kirby_runtime_status` and `kirby_runtime_install` if needed
   - render representative pages with `kirby_render_page(noCache=true)`
7. Summarize changes, remaining risks, and a short manual QA checklist.
