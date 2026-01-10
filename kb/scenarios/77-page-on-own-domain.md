# Scenario: Serve a page on its own domain/subdomain

## Goal

Serve a subpage (e.g. `blog`) directly on a separate domain/subdomain while keeping a shared Kirby installation.

## Inputs to ask for

- Which domain/subdomain should map to which page id
- Whether only the root (`/`) should be mapped or all subpaths too
- Whether links should point to the main domain or the subdomain
- Whether canonical URLs should stay on the main domain

## Internal tools/resources to use

- Confirm environment + roots: `kirby://info`, `kirby://roots`
- Validate rendered output (manual in browser)

## Implementation steps

1. Add conditional rendering logic in the root `index.php`.
2. Set `'url'` in `site/config/config.php` if you want links to point to the main domain.
3. If you need subpaths, expand the condition to allow `REQUEST_URI` beyond `/`.
4. Consider page models for correct canonical URLs/navigation output.

## Examples (quicktip)

`index.php` (excerpt)

```php
require __DIR__ . '/kirby/bootstrap.php';

if ($_SERVER['SERVER_NAME'] === 'blog.domain.com' && $_SERVER['REQUEST_URI'] === '/') {
  echo (new Kirby)->render('blog');
} else {
  echo (new Kirby)->render();
}
```

## Verification

- Request the subdomain root and confirm it renders the target page.
- Confirm other site URLs still render normally on the main domain.

## Glossary quick refs

- kirby://glossary/page
- kirby://glossary/request
- kirby://glossary/roots

## Links

- Quicktip: Page on own domain: https://getkirby.com/docs/quicktips/page-on-own-domain
- Guide: Page models: https://getkirby.com/docs/guide/templates/page-models
