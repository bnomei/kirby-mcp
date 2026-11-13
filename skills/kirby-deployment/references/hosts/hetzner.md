# Hetzner

Hetzner sells Cloud servers (self-managed VPS) and Webhosting (shared hosting, managed in konsoleH). This file covers what differs from `generic.md`; the rules in SKILL.md still apply. Last checked against the docs on 2026-11-13.

## Fits

- Two products: Cloud servers with root access, run and updated by the customer, and Webhosting, run by Hetzner. https://docs.hetzner.com/cloud/apps/list/lamp-stack/, https://www.hetzner.com/webhosting
- Cloud servers in Germany (Falkenstein, Nuremberg), Finland, the USA and Singapore; Webhosting in Nuremberg. https://docs.hetzner.com/cloud/general/locations/, https://www.hetzner.com/webhosting
- Cloud: hourly price with a monthly cap, the lower one is charged. Webhosting: no minimum contract period. https://docs.hetzner.com/cloud/billing/faq/, https://www.hetzner.com/webhosting
- Cloud: any workflow from `generic.md`, with a LAMP image to start from. Webhosting: SFTP, SSH in the larger packages. PHP up to 8.5 on Webhosting. https://docs.hetzner.com/konsoleh/account-management/configuration/php-configuration
- getkirby.com itself is hosted on Hetzner servers in Nuremberg. https://getkirby.com/privacy

## Trade-offs

- Cloud: the server, PHP and the web server are the customer's to install, update and secure. A stopped server is billed until it is deleted. https://docs.hetzner.com/cloud/billing/faq/
- Webhosting: SSH only in the L and XL packages, on port 222, without root. https://docs.hetzner.com/de/managed/administration-on-konsoleh/access-details/ssh/
- Webhosting: the docs name no document root setting for the main domain, which the Composerkit needs (`public/`). Subdomains can point at any folder. https://docs.hetzner.com/managed/domain-and-dns/subdomain/

## Tools

- Cloud: the Cloud Console in the browser, or the `hcloud` CLI to create servers. https://github.com/hetznercloud/cli
- Webhosting: konsoleH in the browser; no CLI.

## Settings

| Setting  | Cloud                                      | Webhosting                                                   |
| -------- | ------------------------------------------ | ------------------------------------------------------------ |
| Web root | Your web server's config                   | `public_html/`; subdomains can point at a folder below it    |
| PHP      | Installed by you; the LAMP image ships one | konsoleH: Services, PHP Configuration; up to 8.5             |
| SSH      | `root@<server-ip>`, key chosen at creation | L and XL only: `ssh -p 222`, main FTP user, no root          |
| Mail     | Your own setup                             | Not covered in the docs read                                 |
| Cron     | `crontab -e`                               | konsoleH Cron Job Manager; jobs using too many resources end |
| SFTP     | Over SSH                                   | Port 22, all packages                                        |

## Deploy

- **Cloud:** follow `generic.md`. The LAMP image ships Apache, MySQL, PHP and Certbot on Ubuntu 24.04, installed but not enabled; check its PHP version against Kirby's requirements.
- **Webhosting with SSH (L, XL):** follow `generic.md`, with `-e 'ssh -p 222'` for rsync. The docs do not mention rsync or git, so check that they run.
- **Webhosting without SSH (S, M):** upload over SFTP. Composer runs locally, and the page cache is emptied by deleting `site/cache/<domain>/pages` over SFTP.
- **Composerkit on Webhosting:** point a subdomain at `public/`; the main domain has no documented setting.

## Content

Sync with rsync as in `generic.md`: on Cloud as is, on Webhosting L and XL with `-e 'ssh -p 222'`. Without SSH, download and upload `content/` over SFTP, excluding `_changes` and `.lock`.

## Documentation

- Cloud: https://docs.hetzner.com/cloud/
- Webhosting packages: https://www.hetzner.com/webhosting
- PHP configuration: https://docs.hetzner.com/konsoleh/account-management/configuration/php-configuration
- SSH and SFTP: https://docs.hetzner.com/managed/administration-on-konsoleh/access-details/ssh/, https://docs.hetzner.com/managed/administration-on-konsoleh/access-details/ftp-sftp/
- Cron: https://docs.hetzner.com/managed/administration-on-konsoleh/cronmanager/
