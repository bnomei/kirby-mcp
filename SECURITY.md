# Security Policy

## Supported Versions

| Version | Supported |
| ------- | --------- |
| 1.x     | yes       |
| < 1.0   | no        |

## Authentication and permission boundaries

The built-in OAuth provider delegates login to Kirby's Panel, including its configured login methods
and two-factor authentication. Pending login handoffs are browser-session-bound, expire after ten
minutes, and require explicit CSRF-protected consent before a code is issued. Approval and denial
consume the handoff once; a new handoff in the same browser replaces the previous one. Consent
pages disallow framing, including when rendered by a custom snippet. Public OAuth endpoints require
HTTPS; plaintext HTTP is allowed only with an actual loopback peer and loopback request host.

OAuth and remote-token authentication resolve an existing Kirby user. Remote-token records require
`userId` (or `KIRBY_MCP_HTTP_REMOTE_TOKEN_USER_ID` for the environment token). Both modes apply the
hierarchical `bnomei.kirby-mcp` capability map: every parent and leaf must allow the operation, custom
roles default to denied, and Kirby's native `admin` role remains unrestricted. The four dedicated
page/file/site/user updates additionally apply Kirby's native mutation permissions and hooks.

This is not per-target isolation: an allowed capability can access every target supported by it.
Enabled and permitted eval or generic CLI execution is trusted and can bypass other permissions.
Revocation is checked per HTTP request, not continuously during an open stream. Local stdio and
loopback shared-token access remain trusted. See [Remote MCP permissions](docs/permissions.md) before
exposing the server.

## Reporting a Vulnerability

Please report security issues via GitHub Security Advisories for this repository.
If advisories are not available, open a GitHub issue and request a private reporting channel.
Do not include sensitive details in public issues.

We will acknowledge reports and follow up with next steps as soon as possible.
