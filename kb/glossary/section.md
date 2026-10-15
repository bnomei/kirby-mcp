# Section (Kirby 5 legacy blueprint concept)

## Kirby 6 replacement

Use fields for all Panel UI. A legacy `pages` section becomes `pagelist`, `files` becomes `filelist`, and `info`/`stats` keep their names as non-value fields. A `fields` section wrapper is unwrapped. Built-in sections convert automatically; custom plugin sections do not and must become PHP `DisplayField` classes registered under `fields`.

Do not confuse a **picker field** with a section: legacy `pages`/`files`/`users` picker fields become `pagepicker`/`filepicker`/`userpicker` and store selected references. Lists do not store values.

## Canonical example

```yaml
fields:
  drafts:
    type: pagelist
    status: drafts
  published:
    type: pagelist
    status: listed
```

When migrating, replace `headline` with `label`, remove `rawvalues`, and resolve case-insensitive name collisions between former sections and fields. Hiding an upload/create control is not an authorization check. Stats reports can open a dialog or drawer rather than a URL.

## Inspect and migrate

- `kirby_blueprint_read` reads raw blueprint configuration; model `blueprint()->fields()` gives normalized fields including display fields.
- `kirby://fields` lists canonical types; `kirby://sections` is legacy migration reference.
- `kirby://kb/panel/reference-sections` explains custom-section ports.
- `kirby://kb/scenarios/83-migrate-kirby-5-to-6` covers the full site migration.

## Links

- https://getkirby.com/releases/6/blueprints
- https://getkirby.com/releases/6/migration/plugin-developers/sections
