---
name: kirby-forms-and-frontend-actions
description: Implement frontend forms and actions in Kirby (contact forms, file uploads, email with attachments, creating pages from frontend). Use when handling user input or building submission flows.
---

# Kirby Forms and Frontend Actions

## Workflow

1. Clarify the form type, validation rules, spam protection, storage target, and email requirements.
2. Call `kirby_init` and read `kirby://roots`.
3. Inspect existing templates/controllers/snippets for patterns:
   - `kirby_templates_index`
   - `kirby_controllers_index`
   - `kirby_snippets_index`
4. Read relevant config options via `kirby://config/{option}` (e.g. `email`, `routes`) when needed.
5. Search the KB with `kirby_search` (examples: "basic contact form", "frontend file uploads", "email with attachments", "creating pages from frontend").
6. Implement controller-driven validation and CSRF checks; keep templates thin and escape output.
7. For uploads, enforce MIME/size limits and store files in safe locations.
8. Verify by submitting forms in a browser and rendering success/error states with `kirby_render_page`.
