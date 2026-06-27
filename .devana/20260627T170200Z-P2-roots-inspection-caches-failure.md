DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | high | security=no
DEVANA-KEY: src/Mcp/Support/KirbyRuntimeContext.php:109 | roots-inspection-caches-failure

# Failed/empty Kirby roots inspection is cached as authoritative success

## Finding

`KirbyRuntimeContext::rootsInspection()` runs `inspectWithCli()` and caches the
result unconditionally (`KirbyRuntimeContext.php:109-114`), with no check that
the inspection succeeded. When `kirby roots` fails transiently,
`KirbyRootsInspector::inspectWithCli()` returns `new KirbyRoots([])` for any
non-zero exit or empty stdout (`KirbyRootsInspector.php:30-33`). That empty
result is cached for the full TTL.

On the next read within TTL, because a failed inspection yields no `index` root,
`indexPhpPath` is `null`, so the code takes the early `return $entry->inspection;`
branch (`KirbyRuntimeContext.php:76-78`) and never re-runs the CLI or checks any
mtime. The mtime-based self-healing path (lines 80-93) is structurally
unreachable for failures, because failures always produce `indexPhpPath = null`.

The companion CLI cache gates on success — `CliResources.php` only calls
`StaticCache::set(...)` when `$payload['ok'] === true` — but
`rootsInspection()` has no equivalent guard.

## Violated Invariant Or Contract

Only successful inspections should be cached. A transient failure must not be
served as authoritative project roots for the remainder of the TTL.

## Oracle

`CliResources.php` (`ok === true` gating before caching) is the neighboring
implementation establishing "cache only on success." `KirbyRootsInspector`
returns empty roots on failure, making failure observationally distinct only by
the empty result — which the cache then treats as a valid answer.

## Counterexample

Default `cacheTtlSeconds = 60` (`KirbyMcpConfig.php`). For a project with custom
roots (non-standard `index`/`content`/`templates` paths):

1. First roots-dependent call: `kirby roots` fails transiently (timeout, lock,
   non-zero exit, empty stdout) → `inspection.roots` empty.
2. `roots->get('index')` empty → `indexPhpPath = null`, `indexPhpMtime = null`.
3. Line 109 caches the empty entry with `inspectedAt = now`.
4. CLI recovers 1s later.
5. Next call within 60s: `age < ttl` true; `indexPhpPath` null → early return at
   lines 76-78 → empty roots returned without re-running the CLI.
6. For the rest of the TTL, every roots consumer (`RootsCodeIndexer`,
   `commandsRoot()`, completion providers, etc.) silently falls back to default
   `site/...` paths instead of the project's real custom roots → wrong file
   resolution.

## Why It Might Matter

A single transient CLI failure poisons the in-process roots cache for up to the
TTL (default 60s, configurable to 3600s), causing wrong file/path resolution for
all roots-dependent operations in the long-lived MCP session (stdio or HTTP),
even after the CLI recovers. The process is not reset per request, so this is
real cross-call staleness.

## Proof

State/transition trace over the static `self::$rootsInspectionCache` keyed at
line 69 (`projectRoot|host`): unguarded write at line 109 + null-`indexPhpPath`
early-return at lines 76-78. The mtime staleness check (lines 80-93) cannot run
for a failed inspection, so failures never self-heal until TTL expiry or manual
`cache.clearCache` (`CacheTools.php`).

## Counterevidence Checked

- Default TTL is 60 (> 0), so the read path is active by default; the bug is not
  gated behind opt-in config.
- `clearRootsCache()` exists (`KirbyRuntimeContext.php:119`) but is only invoked
  by the manual `cache` tool; nothing clears it automatically on failure.
- Not a cross-project/profile leak: the key includes `projectRoot` and `host`,
  and global-reference tools do not call `roots()`. Hence P2 correctness, not
  P0/P1 leak.
- Strongest false-positive reason: empty roots degrade to default `site/...`
  fallbacks rather than throwing, so for standard-layout projects the cached
  failure is observationally harmless. The wrong-result impact bites only
  projects with custom roots — but for those it is a genuine semantic bug.

## Suggested Next Step

Cache only when the inspection succeeded (e.g. gate the write on
`$inspection->cliResult->exitCode === 0` / non-empty roots), mirroring the
`ok === true` gate in `CliResources`. On failure, skip caching so the next call
retries.

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified `KirbyRootsInspector.php:30-33` returns
  empty roots on failure and `KirbyRuntimeContext.php:97-114` caches
  unconditionally; early-return at 76-78 confirmed unreachable-to-heal for
  failures.

- 2026-06-27: fixed. `KirbyRuntimeContext::rootsInspection()` now gates the cache write on inspection success (`$inspection->cliResult->exitCode === 0 && trim(stdout) !== ''`), mirroring the `ok === true` gate in `CliResources`. A transient `kirby roots` failure (timeout / non-zero exit / empty stdout) returns empty roots but is no longer written to `self::$rootsInspectionCache`, so the next call re-runs the CLI and self-heals instead of serving the poisoned empty result for the whole TTL. Added unit test `KirbyRuntimeContextRootsCacheTest` using stub `ENV_KIRBY_BIN` binaries (failing exit-1 stub → empty + not cached; then a succeeding stub emitting a custom `index` root → re-runs and returns `/custom/index/root`). Verified the test fails on the pre-fix code and passes after. phpstan clean.

DEVANA-KEY: src/Mcp/Support/KirbyRuntimeContext.php:109 | roots-inspection-caches-failure
DEVANA-SUMMARY: fixed | P2 | high | A transient `kirby roots` failure returned empty roots that were cached unconditionally for the TTL, so roots-dependent operations used wrong/default paths for custom-root projects until expiry. Now caches only successful inspections.
