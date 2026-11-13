# fortrabbit

fortrabbit is a managed PHP hosting platform with a Kirby software template. This file covers what differs from a self-managed server; the rules in SKILL.md still apply.

**Current values beat this file.** Read the live settings of the environment (`frbit environments get <env> --json`, or `get_environment` on the fortrabbit MCP server) and the Kirby guides before changing anything. Every docs page is available as Markdown: insert `/raw` after the host and append `.md`, e.g. `https://docs.fortrabbit.com/raw/guides/kirby/tune.md`. Last checked against the docs on 2026-11-13.

## What's different on fortrabbit

| Concern     | fortrabbit                                                               |
| ----------- | ------------------------------------------------------------------------ |
| Objects     | An app with environments (`en-xxxxxx`): own code, files, SSH, domain     |
| Deployment  | Git push through the GitHub App, or rsync                                |
| Settings    | Kirby template; change in the dashboard or `frbit environments update`   |
| Strategy    | `replace`: everything not excluded is overwritten                        |
| Build       | `composer install --no-dev`                                              |
| Post-deploy | `rm -rf site/cache/*/pages`                                              |
| Env vars    | `APP_MODE`, `APP_DEBUG`: Kirby ignores them unless the config reads them |
| SSH         | `<env>@ssh.<region>.frbit.app`, no root                                  |
| Mail        | No sendmail: configure SMTP                                              |

Template exclude patterns: `/node_modules`, `/content`, `/media`, `/public/media`, `/web/media`, `/data/`, `/site/accounts`, `/site/sessions`.

## Tasks with the frbit CLI

| Task                 | Command                                               |
| -------------------- | ----------------------------------------------------- |
| Find the environment | `frbit link --json`                                   |
| Read settings        | `frbit environments get <env> --json`                 |
| Add exclude patterns | `frbit environments update <env> --exclude-pattern …` |
| Back up              | `frbit backups create <env>`                          |
| Pull content         | `frbit files pull content`                            |
| Push content         | `frbit files push content`                            |
| Seed Panel users     | `frbit files push site/accounts`                      |
| Empty the page cache | `frbit ssh -- 'rm -rf site/cache/*/pages'`            |
| Redeploy             | `frbit environments deploy <env>`                     |
| Deployment log       | `frbit deployments logs <deployment>`                 |

- `--exclude-pattern` replaces the list: repeat it for every existing pattern.
- Content: `--dry-run` first, and always `--exclude _changes --exclude .lock`.
- `frbit ssh` needs frbit 1.17.0 or later.

`frbit files` paths are relative to the project root on both sides. Flags change with releases: `frbit <command> --help` lists the current ones. Without the CLI, use rsync with the SSH host from `frbit link --json`.

## Common pitfalls

- **The license is not excluded.** `site/config/.license` (Composerkit: `data/storage/.license`, covered by `/data/`) is not in the template's exclude patterns, so every deployment of a Plainkit or Starterkit project deletes a license activated in the Panel. Add `/site/config/.license` to the exclude patterns, keeping the existing ones, or copy the activated file into the repository.
- **Environments created before October 2026** lack `/site/accounts` and `/site/sessions` in the exclude patterns: every deployment deletes the Panel users created on the server. Add them, keeping the existing patterns.
- **Composerkit cache.** Its cache lives in `data/runtime/cache`, which the `/data/` pattern keeps, while the post-deploy command empties only `site/cache`. Change the command to `rm -rf data/runtime/cache/*/pages`, and set the root path to `public`.
- **Composerkit languages.** `data/storage/languages` falls under the excluded `/data/`, so language files committed to git never reach the environment. Push them with `frbit files push data/storage/languages`.
- **Content in git.** `/content` is excluded, so content committed to git never deploys. Sync it with `frbit files push content`.
- **The first Panel user.** Set `'panel' => ['install' => true]` in `site/config/config.<domain>.php` for the environment's domain, create the user, then remove the option.
- **Kirby 6 needs PHP 8.3 or newer.** Raise the PHP version of the environment before deploying it.

## Documentation

- Kirby guides: https://docs.fortrabbit.com/guides/kirby
- Git and rsync deployment, with the current template values: https://docs.fortrabbit.com/guides/kirby/git-and-rsync
- Panel, caching and mail: https://docs.fortrabbit.com/guides/kirby/tune
- Exclude patterns: https://docs.fortrabbit.com/platform/deployment/exclude-patterns
- CLI and MCP server: https://docs.fortrabbit.com/platform/automation
