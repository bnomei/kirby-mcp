# fortrabbit

fortrabbit is a managed PHP hosting platform with a Kirby software template. This file covers what differs from a self-managed server; the rules in SKILL.md still apply.

**Current values beat this file.** Read the live settings of the environment (`frbit environments get <env> --json`, or `get_environment` on the fortrabbit MCP server) and the Kirby guides before changing anything. Every docs page is available as Markdown: insert `/raw` after the host and append `.md`, e.g. `https://docs.fortrabbit.com/raw/guides/kirby/tune.md`. Last checked against the docs on 2026-11-13.

## Choose a workflow

| Workflow                  | Code                    | Content       | Fits              |
| ------------------------- | ----------------------- | ------------- | ----------------- |
| Git + rsync (recommended) | Git push, built there   | `frbit files` | Most sites, teams |
| rsync only                | `frbit files push`, all | `frbit files` | Small, one dev    |

Find out which one the project uses: `frbit environments get <env> --json` shows the deploying branch under `git.branch`; `null` means no git deployment.

Do not mix them for code. With a repository connected, the next git deployment overwrites code pushed with rsync.

## Settings for both workflows

| Setting   | Value                                                                    |
| --------- | ------------------------------------------------------------------------ |
| Objects   | An app with environments (`en-xxxxxx`): own code, files, SSH, domain     |
| Root path | Empty for the Plainkit and Starterkit, `public` for the Composerkit      |
| PHP       | Kirby 6 needs 8.3 or newer: check before the first deployment            |
| Env vars  | `APP_MODE`, `APP_DEBUG`: Kirby ignores them unless the config reads them |
| SSH       | `<env>@ssh.<region>.frbit.app`, no root; `frbit ssh -- '<command>'`      |
| Mail      | No sendmail: configure SMTP                                              |

## Git + rsync

Git carries the code, rsync carries what the Panel writes. The deployment settings below exist only for git deployments.

| Deployment setting | Kirby template                                       |
| ------------------ | ---------------------------------------------------- |
| Strategy           | `replace`: deletes everything not in git or excluded |
| Exclude patterns   | Kept by every deployment, list below                 |
| Build              | `composer install --no-dev`                          |
| Post-deploy        | `rm -rf site/cache/*/pages`                          |

Template exclude patterns: `/node_modules`, `/content`, `/media`, `/public/media`, `/web/media`, `/data/`, `/site/accounts`, `/site/sessions`.

1. Connect the GitHub repository and branch through the fortrabbit GitHub App.
2. Check the exclude patterns against the roots of the project, see the pitfalls below. `frbit environments update <env> --exclude-pattern …` replaces the whole list: repeat the flag for every existing pattern.
3. `git push` deploys. `frbit deployments logs <deployment>` shows a failed build; `frbit environments deploy <env>` deploys the latest commit again.
4. Push the content once, then pull before every later push (see "Content and Panel users").

### Git + rsync pitfalls

- **The license is not excluded.** `/site/config/.license` is missing from the template's patterns, so every deployment of a Plainkit or Starterkit project deletes a license activated in the Panel. Add the pattern, or commit the activated file. The Composerkit's `data/storage/.license` is covered by `/data/`.
- **Environments created before October 2026** lack `/site/accounts` and `/site/sessions`: every deployment deletes the Panel users created on the server. Add both.
- **Content in git does not deploy.** `/content` is excluded. Keep content in git as a copy if wanted, and sync it with `frbit files push content`. To deploy content by git instead, remove `/content` from the patterns; Panel edits on the server are then overwritten by every deployment.
- **Composerkit cache.** The post-deploy command empties `site/cache`; the Composerkit's cache sits in `data/runtime/cache`. Change the command to `rm -rf data/runtime/cache/*/pages`.
- **Composerkit languages.** `data/storage/languages` falls under `/data/`, so committed language files never deploy. Push them with `frbit files push data/storage/languages`.

## rsync only

The whole site goes up with rsync. Strategy, exclude patterns, build and post-deploy commands do not apply: there is no build pipeline, and every step of it is manual.

1. Install the dependencies for production before the push: `composer install --no-dev`, with a local PHP version close to the environment's. Or push without `vendor/` and run `frbit ssh -- 'composer install --no-dev'` afterwards.
2. Push the code from the project root, leaving the server's paths alone, dry run first:

   ```sh
   frbit files push --dry-run \
     --exclude /content/ --exclude /media/ --exclude /site/accounts/ \
     --exclude /site/sessions/ --exclude /site/cache/ --exclude /site/config/.license
   ```

   Drop `--dry-run` once the list looks right. `.git`, `.env` and `.fortrabbit` never go up.

3. Empty the page cache: `frbit ssh -- 'rm -rf site/cache/*/pages'`.

Content goes separately with `frbit files push content`, as in Git + rsync.

## Content and Panel users

Both workflows. `frbit files` paths are relative to the project root on both sides.

| Task             | Command                                |
| ---------------- | -------------------------------------- |
| Back up first    | `frbit backups create <env>`           |
| Pull Panel edits | `frbit files pull content`             |
| Push content     | `frbit files push content`             |
| Seed Panel users | `frbit files push site/accounts`, once |

- `--dry-run` first, and always `--exclude _changes --exclude .lock`.
- **The first Panel user.** Seed it from a local install with the command above, or set `'panel' => ['install' => true]` in `site/config/config.<domain>.php` for the environment's domain, create the user, then remove the option.
- Without the CLI, use rsync with the SSH host from `frbit link --json`. `frbit ssh` needs frbit 1.17.0 or later; `frbit <command> --help` lists the current flags.

## Documentation

- Kirby guides: https://docs.fortrabbit.com/guides/kirby
- Git + rsync, with the current template values: https://docs.fortrabbit.com/guides/kirby/git-and-rsync
- rsync only: https://docs.fortrabbit.com/guides/kirby/rsync
- Panel, caching and mail: https://docs.fortrabbit.com/guides/kirby/tune
- Strategy and exclude patterns: https://docs.fortrabbit.com/platform/deployment/strategy, https://docs.fortrabbit.com/platform/deployment/exclude-patterns
- CLI and MCP server: https://docs.fortrabbit.com/platform/automation
