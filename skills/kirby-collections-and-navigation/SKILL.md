---
name: kirby-collections-and-navigation
description: Build Kirby listings, pagination, search, filtering/sorting/grouping, and navigation menus. Use when implementing collection logic in templates/controllers/snippets.
---

# Kirby Collections and Navigation

## Workflow

1. Clarify collection scope (site vs section), filters, sort order, and UI (pagination, tag filters, menu style).
2. Call `kirby_init` and read `kirby://roots`.
3. Inspect existing templates/controllers/snippets to reuse patterns:
   - `kirby_templates_index`
   - `kirby_controllers_index`
   - `kirby_snippets_index`
4. Prefer controllers for collection logic; keep templates thin.
5. Search the KB with `kirby_search` for task playbooks (examples: "pagination", "search page", "filtering with tags", "related pages field", "navigation menus", "menu builder", "previous next navigation", "collections filtering", "collections sorting", "collections grouping", "random content", "create a blog section", "one pager site sections").
6. Implement or adjust collection queries; add snippets for repeated UI.
7. Verify rendering and pagination URLs with `kirby_render_page(noCache=true)`.
