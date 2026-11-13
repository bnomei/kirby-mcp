# Uberspace

Uberspace is shared hosting run from the shell. This file follows Uberspace 8 and covers what differs from `generic.md`; the rules in SKILL.md still apply. Last checked against the docs on 2026-11-13.

## Fits

- Shared hosting run from the shell: Uberspace runs the server, the account is set up over SSH. https://u8manual.uberspace.de/basics_ssh/
- The manual does not say where the servers are.
- The first month is free, without payment details. After that, prepaid and pay what you want: 6, 9 or 12 € per month, cancellable every month. https://u8manual.uberspace.de/billing_general/
- Deploys with rsync or SFTP; Composer runs on the server. PHP 8.3 to 8.5. https://u8manual.uberspace.de/lang_php/
- Kirby runs on Apache with its own `.htaccess`. Uberspace's Kirby guide covers Uberspace 7 only. https://lab.uberspace.de/guide_kirby/

## Trade-offs

- 10 GB storage, up to 100 GB at 3 € per 10 GB. Above 110 % writes are blocked, so Panel uploads fail. https://u8manual.uberspace.de/basics_resources/
- 1500 MiB RAM and 300 % CPU per account, on a shared host. https://u8manual.uberspace.de/basics_resources/
- No deploy pipeline: builds run locally or over SSH.

## Tools

- A web interface for the account and domains; no local CLI. Everything else runs over SSH.
- On the server, the `uberspace` command sets the PHP version and adds domains: `uberspace tool php version set 8.4`, `uberspace web domain list`.

## Settings

| Setting  | Value                                                                       |
| -------- | --------------------------------------------------------------------------- |
| Web root | `/var/www/virtual/<user>/html`, shared by all domains unless split          |
| PHP      | `uberspace tool php version set 8.4`; own `.ini` files in `~/.config/php/`  |
| SSH      | `<user>@<host>.uberspace.de`; ask the user for the account and server names |
| Mail     | SMTP at `<host>.uberspace.de`, port 465, login with the full address        |
| Cron     | `crontab -e`                                                                |
| Rights   | Folders `0755`, files `0644`: `chmod -R u=rwX,go=rX /var/www/virtual/$USER` |

## Deploy

Follow `generic.md` with these paths.

- **Plainkit, Starterkit:** deploy into `/var/www/virtual/<user>/html/`.
- **Composerkit:** keep the project outside the web root and point `html` at `public`. Replace `html` with the symlink in one go, as below; while `html` is missing, every domain linked to it is offline.

  ```sh
  cd /var/www/virtual/$USER
  rm -f html/nocontent.html && rmdir html
  mkdir -p my_project/public && ln -s my_project/public html
  ```

  Deploy into `/var/www/virtual/<user>/my_project/`. The web root cannot point into `/home`.

- After the upload, fix the rights with the `chmod` above, then empty the page cache as in `generic.md`.

## Content

Sync with rsync as in `generic.md`, to `<user>@<host>.uberspace.de:/var/www/virtual/<user>/html/content/`, or the resolved content root under `my_project/` for the Composerkit.

## Documentation

- Uberspace 8 manual: https://u8manual.uberspace.de/
- Document root: https://u8manual.uberspace.de/web_documentroot/
- Domains: https://u8manual.uberspace.de/web_domains/
- Mail: https://u8manual.uberspace.de/mail_access/
- Cron: https://u8manual.uberspace.de/services_cron/
- Uberspace 7 Kirby guide: https://lab.uberspace.de/guide_kirby/
