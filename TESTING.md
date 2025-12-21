# Testing

## Coverage (Herd + Xdebug)

- Requires PHP >= 8.4 (CI uses 8.5).
- Ensure the starterkit fixture exists: `composer cms:starterkit` (creates `tests/cms/`).
- Recommended run (prints summary + file breakdown):
  - `herd coverage ./vendor/bin/pest --coverage`
- Summary only:
  - `herd coverage ./vendor/bin/pest --coverage --only-summary-for-coverage-text`
- Write a text report:
  - `herd coverage ./vendor/bin/pest --coverage-text`
- Write Clover XML (updates `coverage.xml` for gap analysis):
  - `herd coverage ./vendor/bin/pest --coverage-clover=coverage.xml`

### Fallback when `herd coverage` cannot resolve PHP

Adjust the PHP version/paths to match your Herd installation.

```bash
XDEBUG_MODE=coverage "$HOME/Library/Application Support/Herd/bin/php85" \
  -c "$HOME/Library/Application Support/Herd/config/php/85/debug/debug.ini" \
  ./vendor/bin/pest --coverage
```

## Coverage snapshots (update after each run)

### 2025-12-21 (local, Herd php85 debug; post Render/Blueprint error coverage)

- Total: 72.7% (6073/8358; Clover updated via `--coverage-clover=coverage.xml`)
- Progress this run: added Render truncation/error coverage and Blueprint command error cases; retained routes pagination and runtime command detail tests.

### 2025-12-21 (local, Herd php85 debug; post BlueprintTools/ProjectContext/command detail tests)

- Total: 70.6% (5900/8358; Clover updated via `--coverage-clover=coverage.xml`)
- Progress this run: added BlueprintTools filesystem fallback tests, BlueprintYaml error handling, ProjectContext env/config handling, and runtime command snippet/template detail coverage.

### 2025-12-21 (local, Herd php85 debug; post CodeIndexTools filter/debug tests)

- Total: 68.3% (5706/8358; Clover updated via `--coverage-clover=coverage.xml`)
- Progress this run: added filesystem filter coverage for CodeIndexTools + runtime debug coverage.

### 2025-12-21 (local, Herd php85 debug; post CLI-prepend/mcp-dump tests)

- Total: 64.5% (5388/8358; Clover updated via `--coverage-clover=coverage.xml`)
- Progress this run: added `tests/Unit/KirbyCliPrependTest.php` and `tests/Unit/McpDumpFunctionTest.php`, plus additional runtime command coverage.
- Zero coverage (priority candidates): none of the previous "out-of-process" files remain fully uncovered; `src/Cli/kirby-cli-prepend.php` still has partial branch coverage due to constants defined by `tests/prepend.php`.

### 2025-12-21 (local, Herd php85 debug)

- Total: 61.3% (5124/8358; Clover updated via `--coverage-clover=coverage.xml`)
- Progress this run: added in-process runtime command coverage via `tests/Integration/RuntimeCommandClassesTest.php`.
- Zero coverage (priority candidates):
  - `src/mcp-dump.php`
  - `src/Cli/kirby-cli-prepend.php`

### 2025-12-20 (local, herd/php85)

- Total: 48.9% (Clover updated via `--coverage-clover=coverage.xml`)
- Progress this run: added tests for `src/Mcp/Support/ExtensionRegistryIndexer.php`, `src/Dumps/DumpProjectRootResolver.php`, `src/Dumps/McpDumpContext.php`, `src/Mcp/Tools/CacheTools.php`, `src/Mcp/Support/PageResolver.php`, `src/Mcp/Support/RuntimeCommand.php`, `src/Blueprint/BlueprintScanResult.php`, `src/Mcp/Resources/ToolExamplesResources.php`, `src/Project/ProjectInfoInspector.php`, `src/Mcp/Tools/ProjectTools.php`, `src/Mcp/Resources/AbstractMarkdownDocsResource.php`, `src/Mcp/Tools/DocsTools.php`, `src/Mcp/Resources/BlueprintResources.php`, and `src/Mcp/Resources/PageResources.php`.
- Zero coverage (priority candidates):
  - `src/Mcp/Commands/*.php`
  - `src/mcp-dump.php`
  - `src/Cli/kirby-cli-prepend.php`
- Higher coverage highlights:
  - `src/Dumps/DumpValueNormalizer.php` (93.5%)
  - `src/Mcp/McpLog.php` (100%)
  - `src/Mcp/Prompts/*.php` (100%)

## Coverage gap notes (from `coverage.xml`)

- `src/Cli/kirby-cli-prepend.php` still has partial branch coverage because `tests/prepend.php` defines the constants first; full branch coverage would need a process without the auto-prepend.
- CodeIndexTools filter coverage relies on forcing filesystem mode by temporarily removing `tests/cms/site/commands/mcp/templates.php` during specific tests.
- Latest additions: CodeIndexTools filesystem filter tests + runtime debug coverage, BlueprintYaml/ProjectContext error-path tests, runtime command detail coverage (snippets/templates/blueprints/collections + config:get JSON paths + routes pagination + render/blueprint error paths), ProjectTools kirby_cli_version error handling, plus Install/Update no-Kirby error paths.
