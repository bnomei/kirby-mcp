# Custom command tools

[README](../README.md) · [Configuration](configuration.md) · [Remote permissions](remote.md#configure-role-permissions)

Expose a project or plugin's Kirby CLI command as a named MCP tool. The command stays usable from your terminal and from Janitor; Kirby MCP adds a description, validated inputs, discovery and its existing execution policy. No MCP interface or Janitor dependency is required in your command.

Nothing is exposed automatically. The project owner registers tools in `.kirby-mcp/mcp.json` and separately allows their commands. Tools appear in `tools/list`, `kirby://tools` and `kirby_tool_suggest` in project mode, never in global reference mode. Restart your MCP process and refresh the client's tool list after changing definitions.

## Write a normal Kirby command

Create `site/commands/acme-report.php` in your Kirby project:

```php
<?php

use Kirby\CLI\CLI;

return [
    'description' => 'Read a page title for an editorial report',
    'args' => [
        'page' => [
            'longPrefix' => 'page',
            'description' => 'Page ID',
            'required' => true,
        ],
    ],
    'command' => static function (CLI $cli): void {
        $page = $cli->kirby()->page($cli->arg('page'));
        if ($page === null) {
            fwrite(STDERR, 'Page not found' . PHP_EOL);
            exit(1);
        }

        $cli->out('__KIRBY_MCP_JSON__');
        $cli->out(json_encode([
            'id' => $page->id(),
            'title' => $page->title()->value(),
        ], JSON_THROW_ON_ERROR));
        $cli->out('__KIRBY_MCP_JSON_END__');
    },
];
```

Test it from the project root:

```sh
vendor/bin/kirby acme-report --page=home
```

A plugin can instead keep the file under `site/plugins/acme-reports/commands/report.php` and register it through Kirby's standard `commands` extension:

```php
Kirby::plugin('acme/reports', [
    'commands' => [
        'acme:report' => require __DIR__ . '/commands/report.php',
    ],
]);
```

Use `acme:report` as the configured command below in that case. A plugin can document a suggested registration, but installing it does not grant MCP access automatically. Do not put custom files in the installer-owned `site/commands/mcp/` directory.

## Register the tool

Merge these keys into your project's `.kirby-mcp/mcp.json`:

```json
{
  "cli": {
    "allow": ["acme-report"]
  },
  "customTools": {
    "custom_acme_report": {
      "command": "acme-report",
      "title": "Editorial page report",
      "description": "Read a page's ID and title for an editorial report. Use a page ID such as home or notes/my-note.",
      "readOnly": true,
      "inputSchema": {
        "type": "object",
        "properties": {
          "page": {
            "type": "string",
            "minLength": 1,
            "description": "Kirby page ID"
          }
        },
        "required": ["page"]
      }
    }
  }
}
```

The agent calls `custom_acme_report` with `{"page":"home"}`. Kirby MCP invokes the fixed `acme-report` command with one `--page=home` argument. It does not interpolate a shell command.

### Definition contract

| Field            | Contract                                                                                                                                                                                                  |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Tool name        | The object key: `custom_` followed by letters, digits, `_`, `-` or `.`, at most 64 characters total. Include your plugin/project name to prevent collisions. Built-in `kirby_*` names cannot be replaced. |
| `command`        | Required fixed CLI command name. No shell syntax, flags or `mcp:*` runtime aliases.                                                                                                                       |
| `description`    | Required non-empty text. Explain when to use it, effects, required checks and expected output.                                                                                                            |
| `title`          | Optional display label; defaults to the tool name.                                                                                                                                                        |
| `readOnly`       | Boolean, defaults to `false`. Set `true` only if the command has no writes or external side effects. This is a trusted declaration, not inferred or enforced by a sandbox.                                |
| `inputSchema`    | JSON Schema with root `type: object` and a `properties` object. Defaults to no inputs. List mandatory inputs in `required`; unknown inputs are rejected.                                                  |
| `arguments`      | Optional array of fixed argv strings, passed before dynamic options. Use for fixed positional arguments or flags such as `"--key=acme.rebuild"`; do not declare that same long option as an input.        |
| `timeoutSeconds` | Integer from 5 to 300; defaults to 60.                                                                                                                                                                    |

Each input name must start with a letter and contain only letters, digits or hyphens. It maps to a CLI **long option of the same name**. Each property must declare `type` as `string`, `integer`, `number` or `boolean`; constraints such as `enum`, `minimum` and `pattern` are supported. Reserve `allowWrite` for MCP; it is never forwarded to the command.

Schemas use a restricted subset: root keys are `type`, `properties`, `required`, `additionalProperties`, `title` and `description`. Property keys are `type`, `title`, `description`, `default`, `enum`, `const`, `minimum`, `maximum`, `exclusiveMinimum`, `exclusiveMaximum`, `multipleOf`, `minLength`, `maxLength` and `pattern`. References (`$ref`), dialect overrides (`$schema`), composition and extension keywords are rejected. Write opt-in, required-input presence and scalar types are also checked against the original call arguments before execution, independently of SDK validation.

When dynamic inputs are declared, fixed options must use canonical long names (`--key=value` or `--key` followed by a value), never short aliases such as `-k`. The `--` end-of-options marker is also rejected in this case. For negative fixed values, use the attached form, such as `--offset=-1`. This prevents an alias from hiding a collision with a dynamic input.

- Strings and numbers become one `--name=value` argv element. Spaces, quotes, leading dashes and shell-like text inside the value stay data.
- Boolean `true` adds `--name`; `false` omits it. Use Kirby's `noValue` argument setting for these flags. False does not override a true default inside your command.
- For structured data, declare a string input and pass JSON text in it; decode and validate it in the command. Object/array/null input types are rejected rather than risking the SDK's lossy conversion of empty objects to arrays.
- Omitted optional inputs are not forwarded. JSON Schema `default` documents a default but does not inject it or satisfy a required input; set actual defaults in the Kirby command.
- Dynamic positional arguments and renamed options should be handled by a small project-owned wrapper command exposing suitable long options.

Invalid definitions stop project-server construction with an error rather than silently exposing a partial tool set. Schema constraints are checked before execution. Do not use schema patterns to introduce undeclared option names.

### Writes and permissions

For a writing command, leave `readOnly` false and add the command to `cli.allowWrite` rather than `cli.allow`. MCP automatically adds a required boolean `allowWrite` input whose only accepted value is `true`. The agent must obtain the user's approval before setting it. This uses the generic CLI's explicit write opt-in, **not a separate interactive consent dialog**.

`cli.deny` always wins. Registration does not allow execution on its own. Conversely, allowing a command does not register a custom tool. All `mcp:*` targets are excluded; use the dedicated built-in tools for runtime operations.

HTTP calls need `kirby-mcp:execute`, plus `kirby-mcp:write` when `allowWrite=true`. OAuth/remote-token users also need `access`, `execution` and `execution.cli` under `bnomei.kirby-mcp`. These capabilities govern discovery and direct calls. Local stdio and loopback shared-token access retain their existing trusted behavior.

**This is trusted PHP execution, not per-tool or per-page isolation.** The command does not automatically impersonate the remote user. Granting CLI execution also permits the generic CLI tool to run allowlisted commands with other arguments. Fixed inputs constrain this custom tool only; they do not narrow that broader grant. Keep secrets out of tool definitions, arguments and output. Review commands before enabling them and validate application-specific paths, IDs and URLs inside the command.

### Results and failures

Results use the existing CLI envelope: `ok`, `exitCode`, `stdout`, `stderr`, `timedOut`, `mcpJson` and policy details. The two marker lines in the example make the enclosed JSON object available as `mcpJson`; without them, plain CLI output is still available in `stdout`. Markers are optional and do not require importing an MCP class.

Exit non-zero for failure. Printing an error, returning an array or throwing an exception that the CLI catches does not necessarily produce a non-zero exit status. Policy denial, timeouts and non-zero exits become MCP error results. No retries or background jobs are added.

## Reuse with Janitor

Janitor and MCP can invoke the **same command**, keeping business logic in one place. Janitor continues to own Panel buttons, context queries, notifications and other UI behavior. MCP owns tool descriptions, input validation and agent-facing discovery. Neither system imports the other's permissions or result store.

Prefer a dedicated command over exposing `janitor:call`, `janitor:job`, `janitor:pipe` or `janitor:tinker` with agent-controlled targets. If adapting a known Janitor job, fix its `--key` in trusted configuration or wrap it in a named command; never accept an arbitrary callback key, PHP body, shell command or method name merely to avoid writing a wrapper. Janitor's `janitor()->data(...)` store is in-process; print output or marked JSON for MCP to receive it. Do not assume Janitor's Panel dispatch checks run when a command is invoked directly through the CLI.
