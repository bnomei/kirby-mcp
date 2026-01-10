# Runtime Command Template Guidelines

## Mission

Provide Kirby CLI command definition templates that are copied into host projects by `kirby-mcp install`.

## System

- Each file in `commands/mcp/**/*.php` returns a `::definition()` array from `src/Mcp/Commands/*`.
- Directory structure maps to Kirby CLI command names (e.g. `commands/mcp/page/update.php` → `mcp:page:update`).
- Content wrappers include `mcp:site:*`, `mcp:file:*`, and `mcp:user:*` alongside page commands.

## Workflows

- Add a new command template:
  1. Create/modify `src/Mcp/Commands/<Name>.php` (`public static function definition(): array`).
  2. Add the matching template file here that returns `<Name>::definition()`.
  3. Add/adjust integration tests that assert the command exists after runtime install.
  4. If the command uses a sub-scope (e.g. `mcp:query:dot`), mirror it in the folder path (`commands/mcp/query/dot.php`).

## Guardrails

- Keep templates minimal and side-effect free: strict types + a single return statement.
- Don’t require autoloaders or perform IO; the host project controls Kirby bootstrapping.
