# Uberspace

Uberspace is shared hosting run from the shell: one account ("Asteroid") per project, set up over SSH. This file follows Uberspace 8; accounts on Uberspace 7 use other commands, see the U7 manual. The rules in SKILL.md and the workflow in `generic.md` still apply.

## Fits

- Shell access over SSH and SFTP, Apache with `.htaccess`, Composer installed. https://u8manual.uberspace.de/basics_ssh/, https://u8manual.uberspace.de/lang_php/
- First month free, without payment details. https://u8manual.uberspace.de/billing_general/
- Prepaid, pay what you want: Budget 6 €, Standard 9 €, Solidary 12 € per month; cancel every month. https://u8manual.uberspace.de/billing_general/
- PHP 8.3, 8.4 and 8.5. https://u8manual.uberspace.de/lang_php/

## Trade-offs

- 10 GB storage, up to 100 GB at 3 € per 10 GB. Above 110 % writes are blocked, so Panel uploads fail. https://u8manual.uberspace.de/basics_resources/
- 1500 MiB RAM and 300 % CPU per account, shared host. https://u8manual.uberspace.de/basics_resources/
- No deploy pipeline: code goes up with rsync or SFTP, or a git setup of your own.
- No Kirby guide for Uberspace 8; the lab guide covers Uberspace 7. https://lab.uberspace.de/guide_kirby/

## Settings

| Setting  | Value                                                                       |
| -------- | --------------------------------------------------------------------------- |
| SSH      | `<user>@<host>.uberspace.de`                                                |
| Web root | `/var/www/virtual/<user>/html`, shared by all domains unless split          |
| PHP      | `uberspace tool php version set 8.4`; own `.ini` files in `~/.config/php/`  |
| Rights   | Folders `0755`, files `0644`: `chmod -R u=rwX,go=rX /var/www/virtual/$USER` |
| Mail     | SMTP at `<host>.uberspace.de`, port 465, login with the full address        |

## Deploy

Follow `generic.md` with these paths.

- **Plainkit, Starterkit:** deploy into `/var/www/virtual/<user>/html/`.
- **Composerkit:** keep the project outside the web root and point `html` at `public`. Never delete `html` while domains still link to it: they go offline.

  ```sh
  cd /var/www/virtual/$USER
  rm -f html/nocontent.html && rmdir html
  mkdir -p my_project/public && ln -s my_project/public html
  ```

  Deploy into `/var/www/virtual/<user>/my_project/`. The web root cannot point into `/home`.

- After the upload, fix the rights with the `chmod` above, then empty the page cache as in `generic.md`.

## Documentation

- Uberspace 8 manual: https://u8manual.uberspace.de/
- Document root: https://u8manual.uberspace.de/web_documentroot/
- Domains: https://u8manual.uberspace.de/web_domains/
- Mail: https://u8manual.uberspace.de/mail_access/
- Uberspace 7 Kirby guide: https://lab.uberspace.de/guide_kirby/
