---
name: kirby-deployment
description: Deploys a Kirby site and keeps Panel content, accounts and the license safe across deployments (git, rsync or a hosting platform, content sync, per-host config, Panel setup on a new server). Use when deploying Kirby, moving it to a new host, or syncing content between local and a server.
---

# Kirby Deployment

Use Kirby 6 conventions for new work. The roots below are the same in Kirby 5 and 6; custom roots in `index.php` move them, so resolve them before writing any exclude rule or sync command.

## Quick start

- Resolve the roots, decide where content is edited, recognise the host, then follow its reference file.

## KB entry points

- `kirby://glossary/roots`
- `kirby://glossary/content`
- `kirby://glossary/media`
- `kirby://glossary/cache`
- `kirby://kb/scenarios/36-multisite-variant`

## Required inputs

- The host, or the files that name it (see "Recognise the host").
- Where editors change content: locally and committed, or in the Panel on the server.
- Whether the server already has Panel users, a license or content that must survive.

## What never travels with the code

Resolve each path with `kirby:kirby_roots`; the Plainkit paths are shown. The Composerkit keeps all of them except `media` under `data/` and `public/media`.

| Root               | Plainkit path          | In git                                        | Code deployment                    | Content sync                      |
| ------------------ | ---------------------- | --------------------------------------------- | ---------------------------------- | --------------------------------- |
| `content`          | `content/`             | Yes, unless edited in the Panel on the server | Excluded when edited on the server | Pull before push                  |
| `_changes` folders | `content/**/_changes/` | No                                            | Excluded with `content`            | Exclude: unsaved Panel edits      |
| `media`            | `media/`               | No                                            | Excluded                           | Never: copies and thumbs only     |
| `accounts`         | `site/accounts/`       | No                                            | Excluded                           | Only to seed a new server         |
| `sessions`         | `site/sessions/`       | No                                            | Excluded                           | Never                             |
| `cache`            | `site/cache/`          | No                                            | Emptied after deployment           | Never                             |
| `license`          | `site/config/.license` | No                                            | Excluded                           | Only to copy an activated license |

A deployment that replaces the whole folder deletes every path above that it does not exclude. Users created in the Panel, an activated license and content edited on the server are then gone.

## Recognise the host

| File in the repo                                         | Host                                  | Reference                  |
| -------------------------------------------------------- | ------------------------------------- | -------------------------- |
| `.fortrabbit/deploy.yml`, `.fortrabbit/environments.yml` | fortrabbit                            | `references/fortrabbit.md` |
| Other host files                                         | See the table                         | `references/hosting.md`    |
| None                                                     | VPS or shared hosting with SSH or FTP | `references/generic.md`    |

Ask when nothing names the host. Load only the reference that matches.

## Per-host config

- Keep shared options in `site/config/config.php`; put server-only options in `site/config/config.<domain>.php`, which Kirby loads on that domain only.
- Turn `debug` off on public servers. Keep secrets out of git: read them from server environment variables.
- Kirby refuses to install the Panel on a public server. Set `'panel' => ['install' => true]` in the server's config file, create the first user, then remove the option.

## Content sync

- Pull before push: the server holds the newest Panel edits. Use a dry run first.
- Sync `content/` alone; exclude `_changes` and `.lock`. Never sync `media/`, `site/cache/` or `site/sessions/`.
- Uploads travel with `content/`: Kirby stores each original file next to its page's text file, e.g. `content/blog/post/photo.jpg` with its metadata in `photo.jpg.txt`.
- `media/` holds only public copies and thumbs, generated on the first request. Its folder names hash the absolute content root path (the default `content.salt`) and the file's modification time, so a synced local `media/` folder does not match the server's URLs and is never used.
- Never sync with delete from local to server without a backup; it removes pages created in the Panel.
- Push `site/accounts/` only to seed a new server; afterwards accounts belong to the server.

## Cache after a deployment

- Empty the page cache (`site/cache/<host>/pages` by default) after every deployment; templates changed, cached HTML did not.
- `kirby:kirby_cache_clear` only clears caches of the MCP process. It does not touch `site/cache` on any server.

## Common pitfalls

- Excluding `site/accounts` but not `site/config/.license`: the license activated in the Panel disappears with the next replacing deployment.
- Committing `content/` and editing in the Panel on the server: the next deployment overwrites the Panel edits, or the git copy drifts.
- Hardcoding Plainkit paths in a Composerkit or custom-roots project.
- Uploading a local `vendor/` built with another PHP version than the server's.
- Deploying the Kirby MCP adapter without its package: with `bnomei/kirby-mcp` in `require-dev`, a build that runs `composer install --no-dev` removes the classes that `site/plugins/kirby-mcp/index.php` loads, and every request fails with HTTP 500. Keep `site/plugins/kirby-mcp/` and `site/commands/mcp/` out of git, or install the package as a production dependency as in `docs/install/remote.md`.

## Workflow

1. Call `kirby:kirby_init`, then `kirby:kirby_roots` to resolve `content`, `media`, `accounts`, `sessions`, `cache` and `license`.
2. Read `kirby://config/debug` and `kirby://config/cache`; list the `site/config/config.*.php` files.
3. Recognise the host and load its reference.
4. Check `.gitignore` and the host's deployment excludes against the table above, for the resolved paths.
5. Deploy the code, then sync content in the direction the user asked, dry run first.
6. Empty the page cache on the server, then verify the site and a Panel login.
