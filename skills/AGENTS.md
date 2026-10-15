# Bundled skill guidance

Use Kirby 6 conventions for new work. Keep skill names and paths stable; follow the building-skills workflow when editing their instructions.

- Each independently loaded skill needs only a short Kirby 6 default and its task-specific rules, not MCP release mappings or repeated beta disclaimers. Keep baseline/research caveats in migration guides.
- When existing code differs, inspect the installed CMS version and migration references before replacing it; unfamiliar APIs may belong to plugins or newer releases.
- Prefer canonical value fields (`pagepicker`, `filepicker`, `userpicker`) and display fields (`pagelist`, `filelist`, `info`, `stats`, `buttons`). Never turn a picker into a list or write a display-field content value.
- Root field placements use YAML list references. Existing built-in sections remain supported through automatic conversion; custom sections require PHP display-field ports. Keep section resources discoverable for existing configuration and migration.
- Keep raw blueprint configuration distinct from normalized model fields; `hasValue: null` is unresolved. Read actual available update-schema links before writes.
- Use `kirby://kb/scenarios/83-migrate-kirby-5-to-6` and `kirby://kb/panel/compat-k5-k6-migration` for actionable migration guidance. Do not keep unlabelled Kirby 5 APIs or speculative beta recipes in ordinary workflows.
- Route PHP/storage work to `kirby://kb/scenarios/84-kirby-6-core-contracts`, editor workflows to `kirby://kb/panel/reference-editor` and component/request work to `kirby://kb/panel/reference-runtime`. Distinguish source-reviewed recipes from runtime-tested integrations.
- Validate changed Markdown with Prettier and check all concrete `kirby://kb` links against bundled paths.
