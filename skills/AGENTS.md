# Bundled skill guidance

These published skills target Kirby 6 / MCP 6.x. Existing MCP 1.x releases are the legacy Kirby 5 target. Keep skill names and paths stable; follow the building-skills workflow when editing their instructions.

- Verify `kirby://info` before applying version-sensitive guidance. The migration baseline is `6.0.0-beta.1`; online docs are unversioned, not frozen per CMS version.
- Prefer canonical value fields (`pagepicker`, `filepicker`, `userpicker`) and display fields (`pagelist`, `filelist`, `info`, `stats`, `buttons`). Never turn a picker into a list or write a display-field content value.
- Root field placements use YAML list references. Custom sections require PHP display-field ports; only built-in sections auto-convert.
- Keep raw blueprint configuration distinct from normalized model fields; `hasValue: null` is unresolved. Read actual available update-schema links before writes.
- Use `kirby://kb/scenarios/83-migrate-kirby-5-to-6` and `kirby://kb/panel/compat-k5-k6-migration` for actionable migration guidance. Do not keep unlabelled Kirby 5 APIs or speculative beta recipes in ordinary workflows.
- Validate changed Markdown with Prettier and check all concrete `kirby://kb` links against bundled paths.
