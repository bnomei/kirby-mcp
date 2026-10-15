# Scenario: User registration + login (CSRF + validation)

## Goal

Implement frontend user registration and login using Kirby’s user system.

## Inputs to ask for

- User role to assign on registration (e.g. `client`)
- Required registration fields and whether email verification is required
- Login method (password vs login code via email)
- Login UX (redirect target, “remember me”, logout behavior)
- Whether registration should be open or invite-only

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect user blueprints: `kirby_blueprints_index` + `kirby://blueprint/{encodedId}`
- Inspect config options: `kirby://config/{option}` (esp. auth-related)
- Validate flows: `kirby_render_page`

## Implementation steps

1. Create a user blueprint for the role you register (e.g. `site/blueprints/users/client.yml`).
   - Configure `auth.methods` and email transport if using login codes
2. Add a registration form and include a CSRF token:
   - hidden input `csrf` with `csrf()`
3. In the registration controller:
   - verify `csrf(get('csrf')) === true`
   - validate input via `invalid()`
   - impersonate and create user via `$kirby->users()->create([...])`
4. Add a login form/controller:
   - verify CSRF
   - use `$kirby->auth()->authenticate('password', $email, $password)` for password login
   - handle `Kirby\Auth\Status` with `Kirby\Auth\State::Pending` and complete `verifyChallenge()` before granting access
   - for login codes, start the configured `code` auth method; do not bypass its checks by creating a challenge manually
5. Add logout route/button if needed.

Kirby 5 legacy `login2fa()` is deprecated and string status checks must become `state()`/`is(State::Pending)`. Password-only `login()` cannot complete a second factor. Users can opt into 2FA even when the site does not require it. Test pending, invalid, expired and successful challenges; never treat a pending status as a logged-in user. See https://getkirby.com/releases/6/migration/site-developers#users-permissions-and-login.

## Password policy, passkeys and recovery

Configure `auth.passwords` for a shared policy, then call `$kirby->auth()->passwords()->validate($password)` in custom registration/password forms. Catch `InvalidArgumentException` and show the policy's `hint()`. Supported rules include `minlength`, `maxlength`, lowercase/uppercase/digits/symbols, or custom `rules` with translated `hint`/`error`. The absolute bounds are 8–1000 characters. Do not log passwords or put them in diagnostics.

```php
return [
    'auth' => [
        'methods' => ['password', 'webauthn'],
        'passwords' => ['minlength' => 12],
    ],
];
```

Method order chooses the initial login form. Keep a bootstrap method available: users must be logged in to enroll their first passkey. Passkeys require HTTPS except localhost and belong to the site's host; staging passkeys do not automatically work in production. Default challenges include `webauthn`, `totp`, `email`.

With password `2fa` unset/false, enrolled users still complete their second factor. `true` requires a second step from everyone; keep email recovery available for users without another factor. `code`/`password-reset` login methods cannot be combined with mandatory password 2FA. Users enroll their own factors in the Security drawer; admins can remove another user's factor after their own password confirmation, but cannot enroll passkeys for them. Test recovery and Basic Auth integrations before rollout.

Source: https://getkirby.com/releases/6/authentication.

## Examples (from the cookbook recipe; abridged)

```php
if (csrf(get('csrf')) === true) {
  $kirby->impersonate('kirby');
  $user = $kirby->users()->create([
    'email'    => get('email'),
    'password' => get('password'),
    'role'     => 'client',
    'content'  => [
      'name' => get('name'),
    ],
  ]);
}
```

## Verification

- Attempt registration with invalid data and confirm errors.
- Register a user and confirm:
  - the account exists in the Panel (Users)
  - login works and sessions persist as expected

## Glossary quick refs

- kirby://glossary/user
- kirby://glossary/csrf
- kirby://glossary/blueprint
- kirby://glossary/role

## Links

- Cookbook: User registration: https://getkirby.com/docs/cookbook/forms/user-registration
- Guide: Users: https://getkirby.com/docs/guide/users
- Reference: `csrf()` helper: https://getkirby.com/docs/reference/templates/helpers/csrf
