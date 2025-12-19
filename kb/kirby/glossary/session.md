# Session (aliases: `$session`, `Kirby\Session\SessionData`, `$kirby->session()`)

## Meaning

In Kirby, the session is a per-visitor store that persists across requests (via a cookie). It’s commonly used for authentication state, flash messages, and small “remember this” preferences.

Kirby exposes a session data object with helpers like `get()`, `set()`, `pull()`, etc.

## In prompts (what it usually implies)

- “Flash message” usually means: write to session and consume with `pull()` (so it disappears after one request).
- “Remember user preference” means: `set()` a value and read it on later requests.
- “Session timeout” means: adjust the `session` config options.

## Variants / aliases

- `$kirby->session()` (get session data object)
- `$session->get('key')`, `$session->set('key', 'value')`, `$session->pull('key')`
- Config: `session.*` options (`durationNormal`, `durationLong`, `timeout`, `cookieName`, …)

## Example

```php
<?php

$session = $kirby->session();
$notice = $session->pull('notice');
```

## MCP: Inspect/verify

- Read effective session settings via `kirby://config/session` (requires `kirby_runtime_install`).
- Use `kirby_eval` for quick sanity checks (note: real session behavior depends on browser cookies):
  - example: `return kirby()->session() instanceof Kirby\\Session\\SessionData;`

## Related terms

- kirby://glossary/request
- kirby://glossary/permissions
- kirby://glossary/option

## Links

- https://getkirby.com/docs/reference/objects/session/session-data
- https://getkirby.com/docs/reference/system/options/session
- https://getkirby.com/docs/guide/sessions

