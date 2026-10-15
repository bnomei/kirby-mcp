---
name: kirby-upgrade-and-maintenance
description: Upgrades Kirby and maintains dependencies safely using composer audit, plugin compatibility checks, and official docs. Use when updating Kirby versions or making maintenance changes that affect runtime.
---

# Kirby Upgrade and Maintenance

Use Kirby 6 conventions for new work. Check the installed CMS version and the migration guide's verified baseline before upgrading; test on a backed-up copy.

## Kirby 5 to 6 acceptance

- Read `kirby://kb/scenarios/83-migrate-kirby-5-to-6` before planning. Audit Symfony YAML content as well as blueprints, removed presets, case-insensitive field collisions, option-source changes and invalid values.
- Distinguish legacy sections from pickers: lists store no value. Port custom sections and array-based fields to PHP classes and rebuild all Panel assets for Vue 3.
- Remove `query.runner`, test dashed field access and unknown methods; migrate blueprint object APIs, guards, template reserved names and auth/session integrations.
- Preserve role permission configuration; it is not the deprecated model permissions API. Test allowed/denied roles, pending 2FA, fresh cookies and editor workflows.
- Back up content/accounts/config as well as the lockfile. A dependency rollback alone cannot undo content writes or account changes.

## Quick start

- Follow the workflow below for safe, incremental upgrades.

## KB entry points

- `kirby://kb/scenarios/84-kirby-6-core-contracts`
- `kirby://kb/panel/reference-editor`
- `kirby://kb/panel/compat-k5-k6-migration`
- `kirby://kb/scenarios/83-migrate-kirby-5-to-6`
- `kirby://kb/panel/tooling-kirbyup`
- `kirby://kb/glossary/kirbyup`
- `kirby://kb/glossary/plugin`

## Required inputs

- Current and target Kirby versions.
- Environment constraints and downtime tolerance.
- Plugin compatibility risks and rollback expectations.

## Default upgrade checklist

- Read the target version guide and note breaking changes.
- Update `composer.json` constraints, then review the lockfile diff.
- Run project scripts and render representative pages.
- Verify runtime commands and CLI version match.

## Rollback note

- Keep a copy of the previous `composer.lock` and `vendor/` state.

## Rollback checklist

- Restore the previous `composer.lock`.
- Reinstall dependencies and re-run smoke tests.
- Re-render representative pages to confirm behavior.

## Staged upgrade pattern

- Upgrade a single environment/site first, then roll out to others.
- Validate plugins and custom code before multi-site rollout.

## Common pitfalls

- Skipping plugin compatibility checks.
- Upgrading multiple major versions in one jump.

## Workflow

1. Call `kirby:kirby_init` or gather baseline data with `kirby:kirby_info` and `kirby:kirby_composer_audit`.
2. Inventory plugins for compatibility risks: `kirby:kirby_plugins_index`.
3. Use `kirby:kirby_online` to find official upgrade guides and breaking changes for the target version (prefer `kirby:kirby_search` first).
4. Build a project-specific checklist of required code/config changes.
5. Ask for confirmation before dependency updates that change the lockfile.
6. Verify:
   - run project scripts discovered in the composer audit
   - call `kirby:kirby_cli_version` to confirm the installed version
   - ensure runtime commands are in sync: `kirby:kirby_runtime_status` and `kirby:kirby_runtime_install` if needed
   - render representative pages with `kirby:kirby_render_page(noCache=true)`
7. Summarize changes, remaining risks, and a short manual QA checklist.
