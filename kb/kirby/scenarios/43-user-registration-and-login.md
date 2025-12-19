# Scenario: User registration + login (CSRF + validation)

## Goal

Implement frontend user registration and login using Kirby’s user system.

## Inputs to ask for

- User role to assign on registration (e.g. `client`)
- Required registration fields and whether email verification is required
- Login UX (redirect target, “remember me”, logout behavior)
- Whether registration should be open or invite-only

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect user blueprints: `kirby_blueprints_index` + `kirby://blueprint/{encodedId}`
- Inspect config options: `kirby://config/{option}` (esp. auth-related)
- Validate flows: `kirby_render_page`

## Implementation steps

1. Create a user blueprint for the role you register (e.g. `site/blueprints/users/client.yml`).
2. Add a registration form and include a CSRF token:
   - hidden input `csrf` with `csrf()`
3. In the registration controller:
   - verify `csrf(get('csrf')) === true`
   - validate input via `invalid()`
   - impersonate and create user via `$kirby->users()->create([...])`
4. Add a login form/controller:
   - verify CSRF
   - use `$kirby->auth()->login($email, $password)`
5. Add logout route/button if needed.

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
