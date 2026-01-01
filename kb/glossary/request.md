# Request (aliases: `$request`, `Kirby\Http\Request`, `$kirby->request()`)

## Meaning

In Kirby, the “request” object represents the current HTTP request: URL/path, query string, request method, headers, uploaded files, etc. It’s the primary way to read user input in routes/controllers/templates (together with form handling and validation).

## In prompts (what it usually implies)

- “Get query param `?q=…`” means: use `$request->query()`.
- “Handle POST form submission” means: use `$request->method()` + `$request->data()` and validate/escape.
- “Route params” often means: `$request->params()` (plus route pattern captures).

## Variants / aliases

- `$kirby->request()` (get request object)
- `$request->method()` (GET/POST/…)
- `$request->query()` (query string)
- `$request->data()` (request body data)
- `$request->files()` / `$request->file()` (uploads)

## Example

```php
<?php

$filter = $kirby->request()->query()->filter();
```

## MCP: Inspect/verify

- For values that depend on the _current_ request (path/query/method/headers), prefer dumping during an actual render/request:
  - add `mcp_dump([kirby()->request()->method(), kirby()->request()->path()->toString(leadingSlash: true)])->label('request');`
  - reproduce (`kirby_render_page` → `kirby_dump_log_tail(traceId=...)`) or hit the URL in a browser and filter via `kirby_dump_log_tail(path='/…')`
- Use `kirby_eval` for quick one-off API checks, but it won’t show what happened _during_ a specific render/request.
- If behavior depends on custom routes, inspect [routes](kirby://glossary/route) and locate the matching pattern with `kirby_routes_index(patternContains='…')`.

## Related terms

- kirby://glossary/route
- kirby://glossary/session
- kirby://glossary/csrf
- kirby://glossary/content-representation

## Links

- https://getkirby.com/docs/reference/objects/http/request
- https://getkirby.com/docs/guide/routing
