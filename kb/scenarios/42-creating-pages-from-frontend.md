# Scenario: Create pages from frontend form input (safe workflow)

## Goal

Allow visitors to submit a form that creates content pages, e.g.:

- event registrations
- submissions/entries
- guestbook posts (moderated)

## Inputs to ask for

- Parent page id under which new pages are created
- Page template and required fields for the created pages
- Whether submissions should be drafts (recommended) or published
- Anti-spam needs (honeypot, CSRF, rate limiting)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect blueprint constraints: `kirby://blueprint/{encodedId}`
- Validate rendering and redirects: `kirby_render_page`

## Implementation steps

1. Create a form snippet and include it in the target template.
2. In the controller:
   - validate request (`POST`), honeypot/CSRF
   - validate data via `invalid()`
   - authenticate/impersonate for write operations (`$kirby->impersonate('kirby')`)
   - create page via `$parent->createChild([...])` inside `try/catch`
3. Redirect to a success page or show an inline success message.
4. Prefer creating drafts first and publishing later (moderation).

## Examples (cookbook pattern; abridged)

```php
$kirby->impersonate('kirby');

$registration = $page->createChild([
  'slug'     => md5(Str::slug($data['name'] . microtime())),
  'template' => 'registration',
  'content'  => $data,
]);
```

## Verification

- Submit the form and confirm a new (draft) page is created under the intended parent.
- Confirm write operations fail without authentication/impersonation.

## Glossary quick refs

- kirby://glossary/pages
- kirby://glossary/roots
- kirby://glossary/template
- kirby://glossary/blueprint

## Links

- Cookbook: Creating pages from frontend: https://getkirby.com/docs/cookbook/forms/creating-pages-from-frontend
- Reference: Hooks (optional moderation workflows): https://getkirby.com/docs/reference/plugins/hooks
- Reference: `$page->createChild()`: https://getkirby.com/docs/reference/objects/page/create-child
