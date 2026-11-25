---
name: kirby-deployment
description: Deploys a Kirby site and keeps Panel content, accounts and the license safe across deployments (git, rsync or a hosting platform, content sync, per-domain config, Panel setup on a new server). Use when deploying Kirby, choosing a host for it, moving it to a new host, or syncing content between local and a server.
---

# Kirby Deployment

Use Kirby 6 conventions for new work. The roots below are the same in Kirby 5 and 6; custom roots in `index.php` move them, so resolve them before writing any exclude rule or sync command.

## Quick start

- Resolve the roots, decide where content is edited, then follow the host's reference file.

## KB entry points

- `kirby://glossary/roots`
- `kirby://glossary/content`
- `kirby://glossary/media`
- `kirby://glossary/cache`

## Required inputs

- The host: ask when the user has not named it, or help choose one (see "No host yet").
- Where editors change content: locally and committed, or in the Panel on the server.
- Whether the server already has Panel users, a license or content that must survive.

## What never travels with the code

Resolve each path with `kirby:kirby_roots`; custom roots in `index.php` move them.

None of them belongs in git, except `content` when nobody edits in the Panel on the server. `_changes` folders hold unsaved Panel edits.

| Root       | Plainkit, Starterkit   | Composerkit                         | Deployment             | Sync            |
| ---------- | ---------------------- | ----------------------------------- | ---------------------- | --------------- |
| `content`  | `content/`             | `data/storage/content/`             | Exclude if edited live | Pull, then push |
| `_changes` | `content/**/_changes/` | `data/storage/content/**/_changes/` | Follows `content`      | Exclude         |
| `media`    | `media/`               | `public/media/`                     | Exclude                | Never           |
| `accounts` | `site/accounts/`       | `data/storage/accounts/`            | Exclude                | Seed once       |
| `sessions` | `site/sessions/`       | `data/runtime/sessions/`            | Exclude                | Never           |
| `cache`    | `site/cache/`          | `data/runtime/cache/`               | Empty after            | Never           |
| `license`  | `site/config/.license` | `data/storage/.license`             | Exclude                | Copy once       |

A deployment that replaces the whole folder deletes every path above that it does not exclude. Users created in the Panel, an activated license and content edited on the server are then gone.

## Host reference

Load `references/hosts/<host>.md` when it exists, otherwise `references/generic.md`. Load only that one.

### No host yet

When the user asks where to host, read only the `## Fits` and `## Trade-offs` sections of each file in `references/hosts/`. List the hosts side by side with those facts and their links, ask what matters to the user (budget, data location, server maintenance, deployment workflow), and let the user decide. Do not rank the hosts or pick one.

- Kirby 6 needs PHP 8.3 or newer; check it for every host.
- Any other host with PHP and SSH or SFTP works too, with `references/generic.md`.

## Per-domain config

- Keep shared options in `site/config/config.php`; put server-only options in `site/config/config.<domain>.php`, which Kirby loads on that domain only.
- Turn `debug` off on public servers. Keep secrets out of git: read them from server environment variables.
- Kirby refuses to install the Panel on a public server. Set `'panel' => ['install' => true]` in the server's config file, create the first user, then remove the option.

## Content sync

- Pull before push: the server holds the newest Panel edits. Use a dry run first.
- Sync `content/` alone; exclude `_changes` and `.lock`. Never sync `media/`, `site/cache/` or `site/sessions/`.
- Uploads travel with `content/`: Kirby stores each original file next to its page's text file, e.g. `content/blog/post/photo.jpg` with its metadata in `photo.jpg.txt`.
- `media/` holds only thumbs and public copies, regenerated on each server: its folder names hash the server's content path, so a synced copy is never used.
- Never sync with delete from local to server without a backup; it removes pages created in the Panel.
- Push `site/accounts/` only to seed a new server; afterwards accounts belong to the server.

## Cache after a deployment

- Empty the page cache (`site/cache/<domain>/pages` by default) after every deployment, unless the host's post-deploy command does it; templates changed, cached HTML did not.
- `kirby:kirby_cache_clear` only clears caches of the MCP process. It does not touch `site/cache` on any server.

## Common pitfalls

- Excluding `site/accounts` but not `site/config/.license`: the license activated in the Panel disappears with the next replacing deployment.
- Committing `content/` and editing in the Panel on the server: the next deployment overwrites the Panel edits, or the git copy drifts.
- Hardcoding Plainkit paths in a Composerkit or custom-roots project.
- Uploading a local `vendor/` built with another PHP version than the server's.
- Deploying the Kirby MCP adapter without its package: with `bnomei/kirby-mcp` in `require-dev`, a build that runs `composer install --no-dev` removes the classes that `site/plugins/kirby-mcp/index.php` loads, and every request fails with HTTP 500. Keep `site/plugins/kirby-mcp/` and `site/commands/mcp/` out of git, or install the package as a production dependency as in https://github.com/bnomei/kirby-mcp/blob/main/docs/install/remote.md.

## Workflow

1. Call `kirby:kirby_init`, then `kirby:kirby_roots` to resolve `content`, `media`, `accounts`, `sessions`, `cache` and `license`.
2. Read `kirby://config/debug` and `kirby://config/cache` (both need `kirby:kirby_runtime_install` first); list the `site/config/config.*.php` files.
3. Load the host's reference.
4. Check `.gitignore` and the host's deployment excludes against the table above, for the resolved paths.
5. Show the dry run and wait for the user's yes before the first real deployment, any sync with delete, and any push to the server.
6. Deploy the code. Sync content only when asked, or push it once on a first deployment; pull before every later push.
7. Empty the page cache on the server, then verify the site and a Panel login.
