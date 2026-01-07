---
name: kirby-security-and-auth
description: Secure Kirby sites with access restriction, user roles, permissions, and protected downloads. Use when implementing login/role-based access, permissions, or file protection.
---

# Kirby Security and Auth

## Workflow

1. Clarify which pages/data are protected, required roles, and login/logout behavior.
2. Call `kirby_init` and read `kirby://roots`.
3. Inspect templates/controllers/blueprints to align with existing patterns:
   - `kirby_templates_index`
   - `kirby_controllers_index`
   - `kirby_blueprints_index`
4. For protected downloads or auth routes, inspect routes with `kirby_routes_index` and `kirby://config/routes` (install runtime if needed).
5. Search the KB with `kirby_search` (examples: "access restriction login", "user registration and login", "files firewall", "permission tricks", "page on own domain").
6. Implement least-privilege checks in templates/controllers or routes; enforce CSRF and validation on auth forms.
7. Verify by rendering protected pages (`kirby_render_page`) and manually testing login and download URLs.
