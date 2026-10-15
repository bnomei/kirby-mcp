# Session (aliases: `$session`, `Kirby\Session\Data`, `$kirby->session()`)

## Meaning

In Kirby, the session is a per-visitor store that persists across requests (via a cookie). It’s commonly used for authentication state, flash messages, and small “remember this” preferences.

Kirby exposes a session data object with helpers like `get()`, `set()`, `pull()`, etc.

Use `Kirby\Session\Data`, `Store` and `FileStore` for session integrations. The normal entry point is `$kirby->session()`; handler integrations use `$kirby->sessions()`, `find($token)` and `Sessions::factory()`. Store scalar/array data, not objects: sessions no longer serialize objects. Cookies are signed with HMAC-SHA256 and default to secure on HTTPS.

For Kirby 5 migrations, replace the old `SessionData`/`SessionStore`/`FileSessionStore` class names and `sessionHandler()` API with the names above. Old login cookies and in-flight challenges are invalidated, so users must log in again. See `kirby://kb/scenarios/84-kirby-6-core-contracts` for handler methods.

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
  - example: `return kirby()->session() instanceof Kirby\Session\Data;`

## Related terms

- kirby://glossary/request
- kirby://glossary/permissions
- kirby://glossary/option

## Links

- https://getkirby.com/docs/reference/objects/session/session-data
- https://getkirby.com/docs/reference/system/options/session
- https://getkirby.com/docs/guide/sessions
