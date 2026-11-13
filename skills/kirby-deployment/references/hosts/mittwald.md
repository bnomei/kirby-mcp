# Mittwald

Mittwald runs managed hosting, administered in its mStudio and the `mw` CLI. Kirby runs there as a custom PHP app. The rules in SKILL.md and the workflow in `generic.md` still apply.

## Fits

- Servers in Germany: own data center in Espelkamp, more space in Frankfurt and Hannover. https://www.mittwald.de/webhosting
- Web hosting includes SSH, Apache, git, cron jobs, editable `php.ini`, daily backups kept 30 days and Let's Encrypt certificates. https://www.mittwald.de/webhosting
- PHP 8.2 to 8.5 included. https://www.mittwald.de/webhosting
- A CLI (`mw`) that creates apps, sets the document root and PHP version, and syncs files with rsync. https://developer.mittwald.de/docs/v2/cli/reference/app/
- A Deployer recipe (beta) with GitHub Actions and GitLab CI examples. https://developer.mittwald.de/docs/v2/guides/deployment/deployer/

## Trade-offs

- Offers are for business customers only; prices exclude VAT. https://www.mittwald.de/mstudio
- No Kirby installer or Kirby guide; Kirby is set up as a custom PHP app. https://developer.mittwald.de/docs/v2/platform/workloads/php/
- Backups count against the booked storage. https://www.mittwald.de/webhosting
- Contracts renew for their term unless cancelled 30 days before its end. https://www.mittwald.de/webhosting

## Settings

| Setting   | Value                                                                                    |
| --------- | ---------------------------------------------------------------------------------------- |
| App       | `mw app create php --document-root /public --set php=~8.4`                               |
| Web root  | `/` for the Plainkit and Starterkit, `/public` for the Composerkit                       |
| Change it | `mw app update <app-id> --document-root /public`                                         |
| PHP       | `mw app dependency update <app-id> --set php=~8.4`                                       |
| PHP ini   | `~/.config/php/php.ini`, or `.user.ini` per folder (cached 5 minutes)                    |
| SSH       | `mw app ssh <app-id>`; `mw ssh-user create --public-key <file>` for CI                   |
| OPcache   | Deployments without the recipe: `php cachetool.phar opcache:reset --fcgi=127.0.0.1:9000` |

## Deploy

Install the CLI (`brew tap mittwald/cli && brew trust --formula mittwald/cli/mw && brew install mw`, or `npm install -g @mittwald/cli`) and let the person sign in. Never ask for the API token in the chat. https://developer.mittwald.de/docs/v2/cli/usage/install/

- **rsync or git:** follow `generic.md`, then run `composer install --no-dev` over `mw app ssh <app-id>`.
- **Deployer:** `composer require --dev mittwald/deployer-recipes`, call `mittwald_app('<app-id>')` in `deploy.php` with `public_path` set (`public` for the Composerkit), and run `dep deploy` with `MITTWALD_API_TOKEN` set. Declare the Kirby paths from SKILL.md as shared folders, as in `generic.md`.
- **Content:** `mw app download` and `mw app upload` wrap rsync and take `--remote-sub-directory`, `--exclude` and `--dry-run`. Mittwald calls `upload` a development tool, not a deployment.

  ```sh
  mw app download <app-id> --remote-sub-directory=content --target ./content \
    --exclude _changes --exclude .lock --dry-run
  ```

- Empty the page cache over `mw app ssh <app-id>` as in `generic.md`.

## Documentation

- Deployment guides: https://developer.mittwald.de/docs/v2/guides/deployment/
- PHP apps: https://developer.mittwald.de/docs/v2/platform/workloads/php/
- `mw app` reference: https://developer.mittwald.de/docs/v2/cli/reference/app/
- Web hosting plans: https://www.mittwald.de/webhosting
