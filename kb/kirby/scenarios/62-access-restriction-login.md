# Scenario: Restrict access (frontend login + protected pages)

## Goal

Restrict parts of the site to logged-in users (or specific roles), including:

- a login page and controller
- a `client` role/user blueprint
- conditional navigation

## Inputs to ask for

- Which pages should be protected (whole site vs sections)
- Which roles are allowed
- Where login lives and where to redirect after login/logout

## Internal tools/resources to use

- Inspect user roles/blueprints: `kirby_blueprints_index`
- Inspect templates/controllers: `kirby_templates_index`, `kirby_controllers_index`
- Validate rendering and redirects: `kirby_render_page`

## Implementation steps

1. Create a user blueprint for the role (e.g. `users/client.yml`).
2. Create a login page:
   - `content/login/login.txt`
   - `site/templates/login.php`
   - `site/controllers/login.php`
3. Protect pages:
   - template-level gate (`if (!$kirby->user()) go('/')`)
   - or route-level restriction (more global)
4. Adjust navigation/snippets to show login/logout links appropriately.

## Examples

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if (!$kirby->user()): ?>
  <?php go('login') ?>
<?php endif ?>
```

## Verification

- As a guest, confirm protected pages redirect to login (or show 403).
- As an authorized user, confirm protected pages render normally.

## Glossary quick refs

- kirby://glossary/role
- kirby://glossary/blueprint
- kirby://glossary/controller
- kirby://glossary/template

## Links

- Cookbook: Access restriction: https://getkirby.com/docs/cookbook/security/access-restriction
- Guide: Users: https://getkirby.com/docs/guide/users
- Guide: Authentication: https://getkirby.com/docs/guide/security#authentication
