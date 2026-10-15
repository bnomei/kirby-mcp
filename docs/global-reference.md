# Global reference mode

[README](../README.md) · [Tool and resource reference](reference.md) · [Configuration](configuration.md)

Use a global server for Kirby research without opening a project. It does not need an installed Kirby CMS. Use a separate project-local server when you need the site's actual blueprints, runtime or content.

## Install and connect

Requires PHP 8.3+ and Composer:

```bash
composer global require bnomei/kirby-mcp
composer global config bin-dir --absolute
```

Use the directory printed by the second command to configure an **absolute executable path** in your client. GUI clients may not inherit your terminal's PATH. For example, substitute your actual path for `/absolute/composer/bin/kirby-mcp`:

```json
{
  "mcpServers": {
    "kirby-reference": {
      "command": "/absolute/composer/bin/kirby-mcp",
      "args": ["--global"]
    }
  }
}
```

If the Composer bin directory is on your shell PATH, you can also start it with:

```bash
kirby-mcp --global
```

No runtime `install` step or `--project` argument is needed. Global mode neither autodetects nor accepts a project. Restart the client after changing its server definition. Update the global package with `composer global update bnomei/kirby-mcp`.

## Available reference surface

Tools: `kirby_init`, `kirby_search`, `kirby_online`, `kirby_online_plugins`, and `kirby_tool_suggest`.

Resources include bundled KB articles and glossary, field/section reference, hooks, extensions, and content/blueprint update-schema guides. See the [full reference](reference.md#resources-and-uri-templates) for URI shapes; project-only URIs are not available here. Online docs and plugin searches require network access and upstream availability.

Example requests:

```text
Use Kirby MCP to explain pagepicker storage and its update schema.
Search the bundled Kirby knowledge base for collection filtering.
Search official Kirby docs online for Panel permissions.
```

## When to add a project server

Global mode does not expose project roots/info, blueprint inventory, renders, content reads or mutations, eval/query, runtime installation or IDE helper generation. It never records project Panel activity. Add another server using the project's `vendor/bin/kirby-mcp` and explicit `--project=/absolute/project/path` if needed; keep its client name distinct from `kirby-reference`.

Global discovery receives public cache hints for its deployment-static surface; this is not a live view of a project. For the current CMS baseline and reference caveats, see [compatibility](reference.md#compatibility-and-reference-limits).

## Troubleshoot startup and discovery separately

`spawn … ENOENT` means the client could not start the configured executable. Check the absolute path returned by Composer, executable permissions, and whether the client can find PHP. Verify that exact executable from a terminal; do not assume the GUI shares your PATH.

If the process starts but the client reports an unexpected type in `resources/list`, troubleshoot it separately from executable lookup. Record the client version, negotiated protocol, error and stderr diagnostics when reporting it; see [issue #14](https://github.com/bnomei/kirby-mcp/issues/14).
