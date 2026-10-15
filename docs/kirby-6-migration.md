# Kirby MCP 6 migration ledger

Release ledger for Kirby MCP v6.0.0. Main integration, tagging and publication are authorized; no deployment.
Baseline verified on 2026-10-15: upstream `getkirby/kirby` and Starterkit **6.0.0-beta.1**.
Includes v1.15.1 maintenance work. Existing MCP 1.x releases remain the Kirby 5 line.
The stable MCP package version is not a production certification of the beta CMS.

## Technical contracts

- PHP 8.3 minimum; Composer's development platform is 8.3.0, with CI on 8.3 and 8.5.
- CMS is development-only. Composer `conflict` rejects versions below beta.1 and major 7+;
  global reference installs do not require CMS. Caller-side preflight rejects unsupported runtimes even
  when project-local wrappers load an older MCP package; command-side enforcement remains in place.
  generic CLI execution preflights the version except diagnostic `version`/`help`/`list`.
- Both kit scripts pin beta.1 and assert bundled CMS equals the root lock before replacing fixtures.
  Keep helper collision patches and the fixture-first autoloader; do not download moving kit main.
  Temporary-file creation uses portable `mktemp`, not the GNU-only `--suffix` option.
- `fieldSchemas.hasValue`: true for Kirby `ValueField` subclasses, false for display fields,
  null when type resolution is unavailable. It is not `isStorable(language)` or permission.
- `pagepicker`, `filepicker`, `userpicker` hold selections. `pagelist`, `filelist` are display-only.
  Update-schema links require an existing guide and `hasValue: true`; unresolved/static types
  conservatively omit them. Guides remain directly accessible through reference resources.
  No implicit relinking of old picker guides. Unknown custom fields have no invented Panel URI.
- `Kirby\Blueprint\Blueprint::load()` is raw inspection. Model content reads use normalized
  `blueprint()->fields()`. Root field references use list entries (`- related`), not `related: true`.
- Query contexts resolve UUID strings with `Uuid::from()`. Unknown field methods now error.
  Use bracket/member access for unusual field names; Kirby TXT decoding normalizes dashes to underscores.
- Native Panel login remains authoritative, including challenge/2FA handling and the `panel.path`
  return slot. Use `$kirby->panel()->url('login')`; never collect passwords in an MCP login form.
- Upstream Vue 3 still passes a component instance to `created(app)`, supporting `$panel` and `$watch`.
  No speculative plugin JavaScript rewrite is required.
- Beta.1 only registers Vue in import maps; there is no `importMaps` extension or `panel.extend`
  invocation. Plugins using kirbyuse must bundle it while keeping Vue external (kirbyup 4@next,
  Node 24+, `dev`, `index.dev.js`). `Panel::go()` remains static; do not blanket-rewrite it.

## Integrated knowledge/content work

- Integrated `kirby-6-docs` through its [deeper canonical pass](https://github.com/bnomei/kirby-mcp/commit/582e7ed3dd3fd0bc8ed2ebb3090209368f2fae2b):
  canonical picker storage guides, display-only lists, the Kirby 5-to-6 migration guide and all 15 skills.
- Task guidance teaches Kirby 6 directly; baseline and general version caveats stay in migration guides.
  Existing built-in sections remain supported by automatic conversion and need no immediate rewrite;
  custom sections require ports. Section resources remain discoverable.
- Coverage includes blueprint root references, removed presets/custom sections, shared names,
  field classes, queries, authentication challenges and Panel/Vue 3 tooling. Published paths remain.
- Combined regression checks require all three canonical picker links and nested picker links;
  display fields (including info with an existing guide) and unresolved types omit write-guide links.
  An independent audit resolved all 1,004 concrete KB/glossary/schema URI occurrences.
- Integration review corrected dropdown controller `factory(...)->load()`, Options API
  `$panel.search()` and the inaccurate claim that beta.1 removes the `$events` helper.
- Four former Vue 2 sketches are now Kirby 6 implementation recipes, not certified third-party builds.
  New editor/runtime/core-contract references cover developer-facing features, not every cosmetic or
  internal changelog entry. Direct-request buttons and the YAML migration CLI remain future-beta work.
  HTTP Accept negotiation/cache behavior, third-party dependencies and authenticator flows still need
  integration certification; documentation checks are not runtime certification.
- Online documentation/marketplace availability is upstream-dependent; catalog entries do not prove
  every upstream URL or Kirby 6 plugin listing exists.

## Validation and known limitations

- Pinned fixture reproduced the static Panel URL failure; initial full run: 496 passed, 3 failed.
  The OAuth API and Starterkit assumptions are fixed; PHPStan also found and verified the UUID fix.
- Targeted runtime command tests cover picker/list distinction, root refs, legacy built-in section
  conversion, nested block schemas, existing guide resolution, invalid options with validation on/off,
  multilingual isolation, UUID query contexts, operators and unknown field methods.
- Combined full suite on both PHP 8.3 and PHP 8.5: **508 tests / 4,991 assertions**.
  Both pinned kit setup scripts passed; the final PHP 8.5 run used a freshly regenerated Starterkit.
  PHPStan: no errors. Composer validate and audit pass; Pint dirty-file check passes.
- A disposable consumer with default stable minimum stability resolved and installed the local package
  as 6.0.0 with explicit CMS `6.0.0-beta.1` on a PHP 8.3 Composer platform (plugins/scripts disabled).
  Consumers must opt into the CMS beta themselves; MCP's dev requirement does not grant that opt-in.
- Oracle findings addressed: caller-side runtime gate, portable fixture temporary-file creation, and
  TXT key normalization in query guidance. Regression coverage checks unsupported wrapper non-execution
  and the documented normalized-key query. Native macOS fixture execution was not available in this orb.
- Browser: native password login → resume → explicit consent → approval → PKCE token exchange;
  resulting bearer token successfully reads an MCP resource. Account Panel activity appears, expands,
  and hides after logout.
  No custom credentials UI or vendor patch was used. Other authentication methods/2FA were source-reviewed,
  but not end-to-end exercised with an enrolled authenticator.
- **Upstream Starterkit beta.1 Linux issue:** Site Panel requests `js/Sortable.min.js` while the kit ships
  `js/sortable.min.js`, causing a 404 dynamic-import error on case-sensitive filesystems.
  The Composer CMS package has the correct uppercase filename. Account Panel renders normally.
  A user-authorized orb-only symlink restored Site Panel rendering with HTTP 200 and no browser errors;
  it is not committed or added to fixture setup. Unmodified kits still need the upstream correction.
- Release notes disclose these limitations. Beta.1 verification is not a promise of compatibility with
  future beta API changes, Kirby 7+, or production readiness of the CMS.
