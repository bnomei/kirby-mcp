# Hosts

One row per host. Add a row only with facts checked in the host's own docs, and link them. A host gets a reference file of its own only when its workflow differs too much for one row.

| Host                   | Recognise by        | Deployment            | What survives         |
| ---------------------- | ------------------- | --------------------- | --------------------- |
| fortrabbit             | `.fortrabbit/`      | Git push or rsync     | Git: exclude patterns |
| Dokku                  | `dokku` git remote  | Git push, buildpack   | Mounted storage       |
| FTP via GitHub Actions | FTP deploy workflow | Changed files on push | What the action skips |
| VPS, shared hosting    | No host files       | rsync, SFTP           | What you exclude      |

Content sync: `frbit files` on fortrabbit, rsync or SFTP elsewhere.

- fortrabbit: `fortrabbit.md`, https://docs.fortrabbit.com/guides/kirby
- Dokku: https://getkirby.com/docs/cookbook/development-deployment/git-based-deployment-with-dokku
- FTP via GitHub Actions: https://getkirby.com/docs/cookbook/development-deployment/git-based-ftp-deployment
- VPS, shared hosting: `generic.md`
