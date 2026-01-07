---
name: kirby-routing-and-representations
description: Implement custom Kirby routes and content representations (.json/.xml/.rss), including redirects, sitemap endpoints, and URL pattern filtering. Use when building endpoints, redirects, or representation templates that change how URLs resolve.
---

# Kirby Routing and Representations

## Workflow

1. Clarify the URL pattern, HTTP methods, response type, and language behavior.
2. Call `kirby_init` and read `kirby://roots` to locate config and template roots.
3. Read `kirby://config/routes` to understand current route configuration.
4. If runtime is available, call `kirby_routes_index` to see registered patterns; otherwise run `kirby_runtime_status` and `kirby_runtime_install` first.
5. Inspect existing templates/controllers to avoid collisions:
   - `kirby_templates_index`
   - `kirby_controllers_index`
6. For content representations, add `site/templates/<template>.<type>.php` and optional `site/controllers/<template>.<type>.php`.
7. For routes, add or adjust `routes` in `site/config/config.php` or a plugin. Avoid greedy patterns that shadow `.json`/`.rss` representations.
8. Validate output:
   - use `kirby_render_page(contentType: json|xml|rss)` for representations
   - manually hit route URLs for router behavior (render does not execute the router)
9. Search the KB with `kirby_search` (examples: "custom routes", "json content representation", "filtering via routes", "sitemap.xml", "trailing slash", "page on own domain", "dynamic opengraph images").
