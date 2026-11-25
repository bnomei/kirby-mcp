# Mittwald

Mittwald runs managed hosting, administered in its mStudio and the `mw` CLI; Kirby runs there as a custom PHP app. This file covers what differs from `generic.md`; the rules in SKILL.md still apply. Last checked against the docs on 2026-11-13.

## Fits

- Managed hosting: Mittwald runs the servers, administered in the mStudio or with the `mw` CLI. https://www.mittwald.de/mstudio
- Servers in Germany: an own data center in Espelkamp, more space in Frankfurt and Hannover. https://www.mittwald.de/webhosting
- Web hosting includes SSH, git, cron jobs, daily backups kept 30 days and Let's Encrypt certificates. https://www.mittwald.de/webhosting
- Deploys with rsync, git or a Deployer recipe (beta) with GitHub Actions and GitLab CI examples. PHP 8.2 to 8.5. https://developer.mittwald.de/docs/v2/guides/deployment/deployer/
- Kirby runs as a custom PHP app; the `mw` CLI sets its document root and PHP version. https://developer.mittwald.de/docs/v2/cli/reference/app/

## Trade-offs

- Offers are for business customers only; prices exclude VAT. https://www.mittwald.de/mstudio
- No Kirby installer and no Kirby guide. https://developer.mittwald.de/docs/v2/platform/workloads/php/
- Backups count against the booked storage. Contracts renew for their term unless cancelled 30 days before its end. https://www.mittwald.de/webhosting

## Tools

The mStudio in the browser covers every setting. The `mw` CLI creates the app, sets the document root and PHP version, opens SSH and syncs files. Install it and let the person sign in; never ask for the API token in the chat. https://developer.mittwald.de/docs/v2/cli/usage/install/

```sh
brew tap mittwald/cli && brew trust --formula mittwald/cli/mw && brew install mw
# or: npm install -g @mittwald/cli
```

## Settings

| Setting  | Value                                                                                                                |
| -------- | -------------------------------------------------------------------------------------------------------------------- |
| Web root | `/` for the Plainkit and Starterkit, `/public` for the Composerkit: `mw app update <app-id> --document-root /public` |
| PHP      | `mw app dependency update <app-id> --set php=~8.4`; `~/.config/php/php.ini` or `.user.ini` (cached 5 minutes)        |
| SSH      | `mw app ssh <app-id>`; `mw ssh-user create --public-key <file>` for CI                                               |
| Mail     | Not covered in the docs read                                                                                         |
| Cron     | `mw cronjob create`                                                                                                  |
| App      | `mw app create php --document-root /public --set php=~8.4`                                                           |
| OPcache  | Without the Deployer recipe: `php cachetool.phar opcache:reset --fcgi=127.0.0.1:9000`                                |

## Deploy

- **rsync or git:** follow `generic.md`, then run `composer install --no-dev` over `mw app ssh <app-id>`.
- **Deployer:** `composer require --dev mittwald/deployer-recipes`, call `mittwald_app('<app-id>')` in `deploy.php` with `public_path` set (`public` for the Composerkit), and run `dep deploy` with `MITTWALD_API_TOKEN` set. Declare the Kirby paths from SKILL.md as shared folders, as in `generic.md`.
- Empty the page cache over `mw app ssh <app-id>` as in `generic.md`.

## Content

`mw app download` and `mw app upload` wrap rsync and take `--remote-sub-directory`, `--exclude` and `--dry-run`. Mittwald calls `upload` a development tool, not a deployment.

```sh
mw app download <app-id> --remote-sub-directory=content --target ./content \
  --exclude _changes --exclude .lock --dry-run
```

## Documentation

- Deployment guides: https://developer.mittwald.de/docs/v2/guides/deployment/
- PHP apps: https://developer.mittwald.de/docs/v2/platform/workloads/php/
- `mw` CLI: https://developer.mittwald.de/docs/v2/cli/reference/app/
- Cron jobs: https://developer.mittwald.de/docs/v2/cli/reference/cronjob/
- Web hosting plans: https://www.mittwald.de/webhosting
