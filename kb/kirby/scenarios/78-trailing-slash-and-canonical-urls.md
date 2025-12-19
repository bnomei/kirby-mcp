# Scenario: Enforce canonical URLs (with/without trailing slash)

## Goal
Prevent duplicate content by redirecting to a single canonical URL format:
- with trailing slash (rare for Kirby; usually for migrations)
- without trailing slash (recommended default)

## Inputs to ask for
- Which canonical format is required (with vs without slash)
- Server setup (Apache `.htaccess` vs Nginx/Caddy)
- Whether the site uses the API/Panel (must be excluded from rewrite rules)

## Internal tools/resources to use
- Confirm hosting constraints (this is mostly server config, not Kirby PHP code).
- Verify internal URLs used in templates (`$page->url()` has no trailing slash).

## Implementation steps
1. Update `.htaccess` rewrite rules (Apache) to enforce desired format.
2. Exclude `/panel`, `/api`, and `/media` from rewrite rules to avoid breakage.
3. If migrating from a trailing-slash CMS, expect double requests unless you also rewrite internal link generation (not recommended).

## Examples (quicktip)

### Force trailing slash (migration-only; be careful)
```apacheconf
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_URI} !^/api
RewriteCond %{REQUEST_URI} !^/panel
RewriteCond %{REQUEST_URI} !^/media
RewriteRule ^(.*[^/])$ /$1/ [L,R=301]
```

### Force URLs without trailing slash (recommended)
```apacheconf
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_URI} !^/media
RewriteRule ^(.*)/$ /$1 [L,R=301]
```

## Verification
- Request both URL variants and confirm one redirects to the canonical one (301).
- Confirm `/panel`, `/api`, and `/media` still work as expected.

## Glossary quick refs

- kirby://glossary/request
- kirby://glossary/api
- kirby://glossary/media
- kirby://glossary/panel

## Links
- Quicktip: Trailing slash: https://getkirby.com/docs/quicktips/trailing-slash
- Reference: `$page->url()`: https://getkirby.com/docs/reference/objects/cms/page/url
