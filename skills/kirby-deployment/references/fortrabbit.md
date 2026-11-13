# fortrabbit

fortrabbit is a managed PHP hosting platform with a Kirby software template. This file covers what differs from a self-managed server; the rules in SKILL.md still apply.

**Current values beat this file.** Read the live settings of the environment (`frbit environments get <env> --json`, or `get_environment` on the fortrabbit MCP server) and the Kirby guides before changing anything. Every docs page is available as Markdown: insert `/raw` after the host and append `.md`, e.g. `https://docs.fortrabbit.com/raw/guides/kirby/tune.md`. Last checked against the docs on 2026-11-13.

## What's different on fortrabbit

| Concern             | Self-managed server  | fortrabbit                                                                                                                                                                      |
| ------------------- | -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Objects             | One server           | An app has one or more environments (`en-xxxxxx`), each with its own code, files, SSH access and default domain                                                                 |
| Code deployment     | Your script          | Git push to the connected branch through the fortrabbit GitHub App, or rsync without git                                                                                        |
| Deployment settings | Your script          | The Kirby software template sets the `replace` strategy, exclude patterns, build and post-deploy commands. Change them in the dashboard or with `frbit environments update`     |
| What survives       | What you exclude     | Paths in the exclude patterns: `/content`, `/media`, `/public/media`, `/web/media`, `/data/`, `/site/accounts`, `/site/sessions`. Everything else is replaced by the repository |
| Composer            | You run it           | A build command runs `composer install --no-dev`                                                                                                                                |
| Cache               | You clear it         | A post-deploy command runs `rm -rf site/cache/*/pages`                                                                                                                          |
| Env vars            | `.env` on the server | Dashboard variables per environment; the template sets `APP_MODE=production` and `APP_DEBUG=false`, which Kirby ignores unless the config reads them with `getenv()`            |
| Access              | Root or sudo         | SSH and rsync as `<env>@ssh.<region>.frbit.app`, no root                                                                                                                        |
| Mail                | Local MTA            | No sendmail: configure Kirby's email transport for SMTP                                                                                                                         |

## Tasks with the frbit CLI

| Task                                   | Command                                                                                                                     |
| -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Find the environment of this checkout  | `frbit link` (`--json` adds the SSH host)                                                                                   |
| Read the exclude patterns and commands | `frbit environments get <env> --json`                                                                                       |
| Add an exclude pattern                 | `frbit environments update <env> --exclude-pattern <p> …`: repeat the flag for every existing pattern, the list is replaced |
| Back up before a content push          | `frbit backups create <env>`                                                                                                |
| Pull Panel edits                       | `frbit files pull content --dry-run --exclude _changes --exclude .lock`, then without `--dry-run`                           |
| Push local content                     | `frbit files push content --dry-run --exclude _changes --exclude .lock`, then without `--dry-run`                           |
| Seed Panel users on a new environment  | `frbit files push site/accounts`                                                                                            |
| Empty the page cache by hand           | `frbit ssh -- 'rm -rf site/cache/*/pages'`                                                                                  |
| Deploy the latest commit again         | `frbit environments deploy <env>`                                                                                           |
| Read a failed deployment               | `frbit deployments logs <deployment>`                                                                                       |

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
