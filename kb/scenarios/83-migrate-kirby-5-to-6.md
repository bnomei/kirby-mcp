# Scenario: Migrate Kirby 5 to Kirby 6

## Baseline and scope

This KB targets Kirby 6; the verified migration baseline is **6.0.0-beta.1** (checked October 15, 2026). Beta APIs and tooling can change. Test on a disposable copy, not production. Existing Kirby MCP 1.x releases target legacy Kirby 5 projects; MCP 6.x targets Kirby 6. Official online documentation is unversioned: a fetched page is not a frozen version-specific reference. Compare it with the installed version and the tagged source.

Preserve content and published URLs. Renaming a blueprint field's **type** is not renaming its content key, and converting a display section is not a content migration.

## Prepare a recoverable test copy

1. Back up content, accounts, configuration, plugins and the Composer lockfile. Keep secrets out of logs. Verify restoration before bulk writes.
2. First update the legacy site to a patched Kirby 5 release and resolve deprecations. Inventory every plugin, especially Panel plugins and custom auth.
3. Use PHP 8.3 or newer. Install the exact Kirby beta version on the copy; do not relax stability constraints globally just to resolve it.
4. Inspect `kirby://info`, `kirby://roots`, plugin inventory and runtime status. Do not infer the CMS version from the MCP version.
5. Enable debug only on the test copy; exercise templates, routes, Panel views, content saves, uploads and login with representative roles and languages.

## Convert blueprint types by their meaning

| Kirby 5 legacy definition | Kirby 6 replacement            | Content value?                     |
| ------------------------- | ------------------------------ | ---------------------------------- |
| `pages` **section**       | `pagelist` field               | No                                 |
| `files` **section**       | `filelist` field               | No                                 |
| `info` / `stats` section  | `info` / `stats` field         | No                                 |
| `fields` section wrapper  | Unwrap its fields              | Depends on each field              |
| `pages` **picker field**  | `pagepicker`                   | Yes, selected page references      |
| `files` **picker field**  | `filepicker`                   | Yes, selected file references      |
| `users` **picker field**  | `userpicker`                   | Yes, selected user references      |
| Custom plugin section     | Port to a `DisplayField` class | No, unless deliberately redesigned |

Built-in sections auto-convert when Kirby 6 loads a blueprint. **Custom sections do not.** Deprecated picker aliases still resolve, but use canonical picker names in new code. Never mechanically replace every `pages` or `files` type with a list.

When rewriting, replace section layout entries with fields, unwrap `fields` sections and replace section `headline` with `label`. Remove the obsolete `rawvalues` workaround. All names now share a case-insensitive namespace: a section called `Gallery` and a stored field called `gallery` collide. Rename the display entry first to avoid an unnecessary stored-content migration.

Presets (`preset: page`, `preset: pages`, `preset: files`) are removed and silently ignored. Write the fields explicitly. Root-level field definitions can be referenced from tabs and columns:

```yaml
title: Blog
fields:
  intro:
    type: textarea
  articles:
    type: pagelist
    label: Articles
    template: article
  cover:
    type: filepicker
    max: 1
tabs:
  content:
    columns:
      - width: 2/3
        fields:
          - intro
          - articles
      - width: 1/3
        fields:
          - cover
```

`articles` displays child pages; it is not a value to send to `kirby_update_page_content`. `cover` stores references. See `kirby://field/filepicker/update-schema` and `kirby://kb/panel/reference-fields`. The new `buttons` display field provides Panel actions; it does not store content either.

## Option fields and YAML

For `checkboxes`, `color`, `multiselect`, `radio`, `select`, `tags` and `toggles`, replace legacy `options: query`/`options: api` plus separate configuration with configuration **inside** `options`:

```yaml
category:
  type: select
  options:
    type: query
    query: site.find('categories').children
    text: '{{ page.title }}'
    value: '{{ page.slug }}'
country:
  type: select
  options:
    type: api
    url: https://example.com/countries.json
    text: '{{ item.name }}'
    value: '{{ item.code }}'
```

The query-string shorthand `options: site.children` still works. Picker candidate `query` settings are a different API; do not move those mechanically. Option fields now preserve invalid values instead of silently dropping them on save. Validate explicitly and decide how to map removed options. Color options use `value: text`, e.g. `"#ff0000": Red`.

Symfony YAML replaces Spyc as default. Use `true`/`false`, not `yes`/`no`/`on`/`off` (now strings); quote dates to prevent timestamp conversion. Quote scalars beginning with `@`, `%`, backticks, `[` or `{`; use single quotes for regex backslashes. Fix duplicate keys and tab indentation. Check serialized structure/object/entries/picker content as well as blueprint files. The documented temporary `yaml.handler: spyc` escape hatch is a migration aid, not the canonical configuration. Beta.1 docs promise a future CLI migration command; do not assume it exists now.

## Queries, templates and PHP

- Remove `query.runner`: Kirby 6 always uses the new runner. Use `page.content.get("my-field")` for dashed field names; `-` is subtraction. Unknown field methods now throw instead of returning the field unchanged. Test query results as well as syntax.
- Use `Kirby\Blueprint\Blueprint` and related blueprint classes. `Blueprint::factory($page, 'pages/article')` takes the model first. `tabs()` returns a `Tabs` collection; `tab()` returns a `Tab` object. Replace removed `section()`/`sections()` with `field()`/`fields()`.
- `Blueprint::load()` data is raw; model `$page->blueprint()->fields()` data is normalized and includes display fields. MCP `fieldSchemas` retain them with `hasValue: false`; `true` means a value field and `null` means unresolved/static, not permission to write. Generated update-schema links require `hasValue === true` and an actual bundled guide; unknown/static fields omit links too. Direct reference resources remain readable, but their availability does not establish writability.
- Forms use named arguments, `fill()`/`submit()`, `toFormValues()` and `toStoredValues()`, not removed props-array constructors or `data()`/`content()` methods.
- Replace deprecated model permissions checks with `$page->guards()->isAvailable('update')`; execution checks use `ensureExecutable()`. **Role permission configuration and `$role->permissions()` are not this deprecated model API** and remain relevant.
- Use named arguments for Kirby exceptions instead of a single options array. Custom validators cannot override core validator names.
- Reserve `push()`, `endpush()` and `stack()` for template stacks; reserve `$slot` and `$slots` in template data. Replace the removed video KirbyTag `style` option with a CSS class.

## Panel plugins, authentication and editors

Follow `kirby://kb/panel/compat-k5-k6-migration` for PHP field classes, Vue 3, ESM, trusted HTML and Panel controllers. Rebuilding JavaScript alone does not port array-based PHP fields or custom sections.

- Remove `panel.vue.compiler`. Favicon arrays use per-icon `rel` and `href`, not the old keyed `url` notation. Check custom CSS for renamed field classes and removed variables.
- All users must log in again: old login cookies are rejected. Tell editors before the switch; require a supported modern browser (Chrome/Edge 124, Firefox 125, Safari 17.5 or newer at beta.1).
- Frontend login flows should use `$kirby->auth()->authenticate('password', $email, $password)` and handle `Kirby\Auth\Status` / `Kirby\Auth\State::Pending`, then `verifyChallenge()`. A pending second factor is **not** a completed login. `login2fa()` is deprecated; password-only `login()` cannot complete 2FA.
- Users can opt into 2FA even when it is not globally required. Basic Auth cannot complete a second factor; use an appropriately scoped dedicated API user rather than weakening an editor's account.
- Passkeys require HTTPS (except localhost), are host-bound and need `webauthn` in the relevant configured methods/challenges. Staging credentials do not transfer automatically to a production hostname.
- Port custom challenges to `Kirby\Auth\Challenge`; the old Panel `login` extension is removed. Replace `user.changeTotp` hooks with `user.changeSecret` hooks.
- `$kirby->session()` remains the normal API. Handler integrations use `$kirby->sessions()`, `find($token)` and `Sessions::factory()`, not `sessionHandler()`, `get()` or direct construction.
- Show editors the account Security drawer, live editor preview, unsaved-field indicators and invalid-option/date messages. Verify a save/reload preserves intended values, including previously invalid ones.

## Acceptance checklist

- No duplicate blueprint names, ignored presets or custom-section error boxes.
- Lists render without content writes; picker selections survive save/reload and resolve to the same models.
- Serialized dates, booleans, nested fields and references round-trip on a sample in each language.
- Plugins load without console errors; field input, dialogs, drawers, API routes and authorization work for allowed and denied roles.
- Frontend login handles success, pending challenge, failure, logout and expired sessions; editors can recover access.
- Representative templates, query results and content representations match expectations; no production deployment is implied by passing beta tests.

## Authoritative sources

- https://getkirby.com/releases/6
- https://getkirby.com/releases/6/blueprints
- https://getkirby.com/releases/6/migration/site-developers
- https://getkirby.com/releases/6/migration/plugin-developers
- https://getkirby.com/releases/6/migration/editors
- https://github.com/getkirby/kirby/tree/6.0.0-beta.1
