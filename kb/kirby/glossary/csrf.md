# CSRF (aliases: `csrf()`, “CSRF token”, “form token”)

## Meaning

CSRF (Cross-Site Request Forgery) protection prevents malicious sites from submitting state-changing requests on behalf of a logged-in user. In Kirby, CSRF protection is typically handled via a session-backed token: you embed it into forms and then verify it on POST.

## In prompts (what it usually implies)

- “Add CSRF to a form” means: include `<input type="hidden" name="csrf" value="<?= csrf() ?>">`.
- “Invalid CSRF token” means: the submitted token doesn’t match the session token (session/cookies missing, token name mismatch, or double-submit).
- “Protect a custom route endpoint” means: verify the token before allowing mutations.

## Variants / aliases

- `csrf()` returns a token string
- `csrf($token)` returns `true/false` to validate a submitted token
- Tokens are stored in the session (so session configuration matters)

## Example

```php
<input type="hidden" name="csrf" value="<?= csrf() ?>">
```

```php
<?php
$token = get('csrf');
if (csrf($token) !== true) {
    throw new Exception('Invalid CSRF token');
}
```

## MCP: Inspect/verify

- Render the form page with `kirby_render_page` and confirm the hidden `csrf` input exists.
- Remember: CSRF depends on browser cookies/session state; MCP CLI runs can’t fully reproduce a real browser session.
- If the issue is “token mismatch”, inspect session config with `kirby://config/session` and review how the request is handled (kirby://glossary/request).

## Related terms

- kirby://glossary/request
- kirby://glossary/session
- kirby://glossary/permissions

## Links

- https://getkirby.com/docs/reference/templates/helpers/csrf
- https://getkirby.com/docs/guide/sessions
