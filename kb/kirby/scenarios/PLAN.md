# Kirby KB scenarios (task playbooks) — plan

We are creating a **new** set of knowledgebase documents as Markdown files under `/kb`.

These docs are **task-oriented playbooks** for coding agents working on Kirby projects via the Kirby MCP server in this repo.

We start with `kb/kirby/scenarios/` and add scenario docs for common, code-centric Kirby tasks found in:
- Kirby Cookbook: https://getkirby.com/docs/cookbook
- Kirby Quicktips: https://getkirby.com/docs/quicktips
- Kirby Guide Quickstart: https://getkirby.com/docs/guide/quickstart

## Scope / non-goals

Include scenarios that touch code and project structure directly:
- Templates, snippets, controllers, page models
- Blueprints (Panel UI), blueprint composition (`extends`)
- Content representations (`.json`, `.rss`, …)
- Routing
- Plugins (extensions, hooks)

Discard scenarios that are mostly non-code or “environment choice”:
- Hosting and deployment choices
- FTP uploads
- “How to learn PHP” / generic programming advice
- Non-technical content strategy

## Scenario format (what every file should contain)

Each scenario doc should be short, operational, and tool-first:
- **Goal** (user intent)
- **Inputs to ask for** (what the agent must clarify)
- **Tooling plan** (which internal MCP tools/resources to use and why)
- **Implementation steps** (what code/files to change)
- **Examples** (small, correct snippets; avoid huge code blocks)
- **Verification** (how to confirm it works)
- **Glossary quick refs** (optional; `kirby://glossary/{term}` links for key terms used in the scenario)
- **Links**
  - Official Kirby docs (important reference links)
  - Prefer internal MCP resources/tools where they fit

### Scenario template

```md
# Scenario: <short name>

## Goal

## Inputs to ask for

## Internal tools/resources to use

## Implementation steps

## Examples

## Verification

## Glossary quick refs

## Links
```

## Baseline agent workflow (Kirby MCP)

1. Establish context:
   - Use `kirby_init` (or read `kirby://info` + `kirby://roots`).
2. Never assume paths:
   - Always start with `kirby_roots` / `kirby://roots` before using `site/`, `content/`, etc.
3. Discover existing project files before creating new ones:
   - `kirby_templates_index`, `kirby_snippets_index`, `kirby_controllers_index`
   - `kirby_blueprints_index`, `kirby_models_index`, `kirby_plugins_index`
4. Prefer resource reads when possible (structured + stable):
   - `kirby://blueprint/{encodedId}` (or tool `kirby_blueprint_read`)
   - `kirby://config/{option}`
   - `kirby://page/content/{encodedIdOrUuid}` (or tool `kirby_read_page_content`)
5. Validate by rendering and inspecting runtime output:
   - `kirby_render_page` (HTML/JSON render with error capture)
6. Only drop down to raw CLI when needed:
   - `kirby://commands` + `kirby://cli/command/{command}`
   - `kirby_run_cli_command` (guarded allowlist; set `allowWrite=true` only when required)

## Internal reference inventory (link targets)

### Core project resources
- `kirby://info` (runtime + environment summary)
- `kirby://composer` (composer audit + “how to run” commands)
- `kirby://roots` (resolved Kirby roots)
- `kirby://tools` (tool suggestions)

### CLI discovery
- `kirby://commands`
- `kirby://cli/command/{command}`

### Config/content/blueprint reads
- `kirby://config/{option}`
- `kirby://blueprint/{encodedId}`
- `kirby://page/content/{encodedIdOrUuid}`

### Panel reference
- `kirby://fields` + `kirby://field/{type}`
- `kirby://sections` + `kirby://section/{type}`

### Plugin reference
- `kirby://hooks` + `kirby://hook/{name}`
- `kirby://extensions` + `kirby://extension/{name}`

### Glossary
- `kirby://glossary` (term list)
- `kirby://glossary/{term}` (single glossary entry)

## Initial scenarios (seed set)

These are the first “common tasks” extracted from Cookbook/Quicktips/Guides that are directly code-centric:
- `01-scaffold-page-type.md` — Scaffold a new page type (template + blueprint + optional controller/model)
- `02-json-content-representation-ajax-load-more.md` — Add a JSON content representation (optionally for “load more” Ajax)
- `03-shared-controllers.md` — Share controller data across templates (site controller + shared controllers)
- `04-share-templates-controllers-via-plugin.md` — Share templates/controllers across page types via plugin registration
- `05-kirbytext-kirbytags-hooks.md` — Add KirbyText/KirbyTags hooks in a plugin

## Common scenarios (extended set)

More common, code-centric tasks from Cookbook/Quicktips/Guides:
- `06-blueprints-reuse-extends.md` — Reuse & extend blueprints with mixins (`extends`)
- `07-pagination.md` — Add pagination to listings
- `08-search-page.md` — Create a search page (controller + template)
- `09-filtering-with-tags.md` — Filter listings by tag + tag cloud
- `10-related-pages-field.md` — Add related content via a `pages` field
- `11-navigation-menus.md` — Build navigation menus (main/sub/breadcrumb)
- `12-menu-builder.md` — Panel-managed menus (structure + pages/link fields)
- `13-custom-routes.md` — Custom routes (redirects, virtual pages, JSON endpoints)
- `14-escaping-and-safe-markdown.md` — Escape output correctly + safe Markdown
- `15-custom-blocks-nested-blocks.md` — Custom blocks (incl. nested blocks) via plugin
- `16-batch-update-content.md` — Batch-update content safely (migrations)
- `17-extend-kirbytags.md` — Reuse/extend existing KirbyTags
- `18-ide-support.md` — Improve IDE support (type hints + helpers)
- `19-programmable-blueprints.md` — Programmable (PHP-based) blueprints
- `20-previous-next-navigation.md` — Previous/next navigation links
- `21-filtering-via-routes.md` — Filter listings via routes (pretty URLs)
- `22-custom-post-types.md` — Custom post types via template variants

## Cookbook coverage (remaining scenarios)

Scenarios added to exhaust the code-centric Kirby Cookbook categories:

### Collections
- `23-collections-filtering.md` — Filter collections safely (`filterBy`, `filter`)
- `24-collections-sorting.md` — Sort collections (`sortBy`, computed sort keys)
- `25-collections-grouping.md` — Group collections (`groupBy`, `group`)
- `26-collections-random-content.md` — Random content (`shuffle` + pick)

### Content representations
- `27-dynamic-opengraph-images.md` — Dynamic OG images via `.png` representation
- `28-figma-auto-populate.md` — JSON for “Populate Figma designs”

### Content structure
- `29-authors-via-users-field.md` — Authors via users + `users` field
- `30-create-a-blog-section.md` — Blog section (index + articles)
- `31-one-pager-site-sections.md` — One-pager site (sections via snippets)
- `32-subpage-builder-hooks.md` — Auto-create subpages via hooks
- `33-use-placeholders-str-template.md` — Placeholders via `Str::template()`
- `34-content-file-cleanup-script.md` — Cleanup unused fields (migration script)
- `35-replace-core-classes.md` — Replace core classes (App/Site) via plugin
- `36-multisite-variant.md` — Multisite variant (shared core + per-site roots)

### Extensions
- `37-columns-in-kirbytext.md` — KirbyText “columns” syntax via hook
- `38-pdf-preview-images.md` — PDF preview images (plugin + ImageMagick)

### Forms
- `39-basic-contact-form.md` — Contact form (validation + email)
- `40-frontend-file-uploads.md` — Frontend file uploads (safe storage)
- `41-email-with-attachments.md` — Email with attachments
- `42-creating-pages-from-frontend.md` — Create pages from frontend input
- `43-user-registration-and-login.md` — User registration + login (CSRF)

### Headless
- `44-headless-api-with-kql.md` — Headless API with KQL (`/api/query`)
- `45-headless-kiosk-application.md` — Headless kiosk app (high-level)

### i18n
- `46-i18n-field-options-and-labels.md` — Translate option labels and render correctly
- `47-i18n-find-translation-keys.md` — Find translation keys used in code
- `48-i18n-import-export-translations.md` — Import/export translation workflows

### Navigation endpoints
- `49-sitemap-xml-route.md` — `sitemap.xml` via snippet + route
- `50-table-of-contents-from-headlines.md` — Table of contents from headings

### Panel customization
- `51-panel-first-custom-area.md` — First custom Panel area
- `52-panel-advanced-custom-area.md` — Advanced Panel area (multi-view/API)
- `53-panel-first-custom-field.md` — Custom Panel field
- `54-panel-first-custom-section.md` — Custom Panel section

### Performance & caching
- `55-cdn-asset-and-media-urls.md` — CDN routing via components
- `56-lazy-loading-images.md` — Lazy-load images
- `57-responsive-images-srcset.md` — Responsive images via `srcset()`
- `58-fine-tune-page-cache.md` — Fine-tune cache exclusions via blueprint/field

### Plugin development
- `59-monolithic-plugin-setup.md` — Plugin repo with bundled dev site
- `60-plugin-workflow-local-testing.md` — Local testing workflow for plugins
- `61-panel-plugin-without-bundler.md` — Panel plugin without a bundler

### Security
- `62-access-restriction-login.md` — Access restriction (login + protected pages)
- `63-files-firewall-protected-downloads.md` — Protected downloads (files firewall)
- `64-permission-tricks-role-based.md` — Permission tricks (roles/blueprints/models)

### Experiments / advanced
- `65-ab-testing-visitor-groups.md` — A/B testing visitor groups
- `66-blueprints-in-frontend.md` — Read blueprints programmatically in frontend

### Quicktips coverage
- `67-indieauth-rel-me.md` — IndieAuth/RelMeAuth links
- `68-snippet-controllers.md` — Snippet/block controllers via plugin
- `69-structured-field-content.md` — Structured field content (`toStructure`)
- `70-add-custom-html-to-panel.md` — Custom HTML in Panel (info field)
- `71-panel-branding.md` — Panel branding
- `72-filter-by-language.md` — Filter by language (no fallback leakage)
- `73-language-variables-and-placeholders.md` — Language variables + placeholders
- `74-update-blocks-programmatically.md` — Update blocks field programmatically
- `75-update-file-metadata.md` — Update file metadata (`$file->update()`)
- `76-translate-exception-messages.md` — Translate exception messages
- `77-page-on-own-domain.md` — Serve a page on its own domain/subdomain
- `78-trailing-slash-and-canonical-urls.md` — Canonical URL rewrites
- `82-homepage-title-tag.md` — Homepage `<title>` tag pattern

### Frontend tooling
- `79-conditional-loading-frontend-libraries.md` — Conditionally load JS/CSS per template
- `80-tailwindcss-build-workflow.md` — Tailwind CSS build setup
- `81-purgecss-build-workflow.md` — PurgeCSS build setup

## Cookbook pages intentionally excluded (non-code / low MCP value)

These Cookbook pages are primarily server/environment workflow and are intentionally not turned into agent playbooks:
- Deploy/server setup: caddy, ddev, dokku, docker, nginx, git-based FTP deployment, mailhog
- Integrations: dropbox (symlink/server workflow)
- General PHP learning: php templates/loops/OOP, xdebug
- Meta: getkirby.com (site meta article)
