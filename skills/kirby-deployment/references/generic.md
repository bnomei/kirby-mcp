# VPS or shared hosting

For a server reached over SSH or FTP, without a hosting platform in between. Resolve the roots first; the paths below are the Plainkit defaults.

## Requirements

- PHP version and extensions as listed in Kirby's requirements, the same major PHP version locally and on the server.
- The web root points at the folder with `index.php`: the project root for the Plainkit and Starterkit, `public/` for the Composerkit.
- PHP can write to `content/`, `media/`, `site/accounts/`, `site/sessions/`, `site/cache/` and `site/config/` (for `.license`).

## Deploy the code with rsync

Exclude every path from the SKILL.md table, so the deployment neither uploads nor deletes it:

```sh
rsync -avz --dry-run \
  --exclude=.git --exclude=/content --exclude=/media \
  --exclude=/site/accounts --exclude=/site/sessions --exclude=/site/cache \
  --exclude=/site/config/.license \
  ./ user@example.com:/var/www/example.com/
```

- Drop `--dry-run` once the list looks right. Add `--delete` only with these excludes in place.
- With `vendor/` in `.gitignore`, run `composer install --no-dev` on the server, or upload `vendor/` built for the server's PHP version.

## Sync content

Ask the user for the SSH host and the project path on the server; `kirby:kirby_roots` resolves the local roots only, and the server's can differ. Commit or back up the local `content/` first: a pull overwrites it.

```sh
# Down: Panel edits from the server, dry run first
rsync -avz --dry-run --exclude=_changes --exclude=.lock user@example.com:/var/www/example.com/content/ ./content/

# Up: local content to the server, dry run first
rsync -avz --dry-run --exclude=_changes --exclude=.lock ./content/ user@example.com:/var/www/example.com/content/
```

Drop `--dry-run` once the list looks right.

## Release-based tools

Tools that deploy each release into a new folder and switch a symlink (Deployer, Envoyer and similar) need the persistent paths as shared folders outside the releases: `content`, `media`, `site/accounts`, `site/sessions`, `site/cache` and the license file. Otherwise every release starts empty.

## After a deployment

```sh
ssh user@example.com 'rm -rf /var/www/example.com/site/cache/*/pages'
```

Then open the site and log in to the Panel.

## Documentation

- Kirby requirements: https://getkirby.com/docs/guide/quickstart#requirements
- Kirby roots: https://getkirby.com/docs/reference/system/roots
- Multi-environment config: https://getkirby.com/docs/guide/configuration#multi-environment-setup
- Git deployment with Dokku: https://getkirby.com/docs/cookbook/development-deployment/git-based-deployment-with-dokku
- FTP deployment with GitHub Actions: https://getkirby.com/docs/cookbook/development-deployment/git-based-ftp-deployment
