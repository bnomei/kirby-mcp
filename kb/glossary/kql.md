# KQL (aliases: “Kirby Query Language”, “headless queries”, “KQL plugin”)

## Meaning

KQL is an optional Kirby plugin that provides a query interface for fetching content without writing backend code for each endpoint. It supports only a **non-destructive** subset of Kirby’s API and can be used with the same auth methods as the REST API.

KQL can also be used without authentication, but that is only safe if _all_ content is intended to be public.

## In prompts (what it usually implies)

- “Is KQL installed?” means: check dependencies/plugins first; KQL is not guaranteed to be present.
- “Use KQL for headless frontend” means: you need read access, flexible query shape, and you accept the security implications.
- “We need write access” means: use the REST API or project-local automation instead (KQL is read-only).

## Variants / aliases

- KQL plugin docs and playground
- REST API is the write-capable alternative (see kirby://glossary/api)

## Example

```text
KQL: fetch selected page fields and nested relations
```

## MCP: Inspect/verify

- Determine whether KQL is installed:
  - `kirby_composer_audit` / `kirby://composer`
  - `kirby_plugins_index`
- If installed, consult the official KQL docs and test in the playground.
- If not installed, prefer [content representations](kirby://glossary/content-representation) or [routes](kirby://glossary/route) for read endpoints.

## Related terms

- kirby://glossary/api
- kirby://glossary/content-representation
- kirby://glossary/route

## Links

- https://getkirby.com/docs/guide/beyond-kirby#kql
- https://github.com/getkirby/kql/#kirby-ql
- https://kql.getkirby.com/
