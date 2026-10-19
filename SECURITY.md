# Security Policy

## Supported Versions

| Version | Supported     |
| ------- | ------------- |
| 6.x     | yes (Kirby 6) |
| 1.x     | yes           |
| < 1.0   | no            |

## Authentication and permission boundaries

The built-in OAuth provider delegates login to Kirby's Panel, including its configured login methods
and two-factor authentication. Pending login handoffs are browser-session-bound, expire after ten
minutes, and require explicit CSRF-protected consent before a code is issued. Approval and denial
consume the handoff once; a new handoff in the same browser replaces the previous one. Consent
pages disallow framing, including when rendered by a custom snippet. Public OAuth endpoints require
HTTPS; plaintext HTTP is allowed only with an actual loopback peer and loopback request host.

Built-in OAuth issuance intersects grants and stored client scopes with the current provider policy,
including requests that omit scope. Refresh rotation is serialized per grant family. Reusing an old
refresh token revokes that family's active refresh credentials, not unrelated grants. Tokens issued
before family tracking require fresh authorization. Existing signed access tokens remain valid until
their one-hour expiry, subject to current Kirby permissions; family revocation prevents further renewal.

OAuth and remote-token authentication resolve an existing Kirby user. Remote-token records require
`userId` (or `KIRBY_MCP_HTTP_REMOTE_TOKEN_USER_ID` for the environment token). Both modes apply the
hierarchical `bnomei.kirby-mcp` capability map: every parent and leaf must allow the operation, custom
roles default to denied, and Kirby's native `admin` role remains unrestricted. The four dedicated
page/file/site/user updates additionally apply Kirby's native mutation permissions and hooks.

Native child/file browsing, original-byte reads and image previews additionally check native model read/access/list
permissions. Uploads and page moves run native guards and hooks as the authenticated user. Upload tickets
require bearer authentication and write scope on redemption, bind the issuing user and operation, expire after
ten minutes, and can be claimed only once. Remote uploads never accept server paths. Keep ticket storage
under `.kirby-mcp/uploads` private and enforce HTTP body limits at the web server. Context-free uploads are
disabled unless explicitly enabled; blueprint-context uploads enforce destination, template and capacity.
Original-byte reads and previews reject symlink aliases beneath the configured content root, even aliases
to other content files. Local upload `source` is intentionally a trusted filesystem path, limited by the
stdio process's OS permissions, not Kirby read permissions. Do not expose stdio to untrusted clients.
The filesystem and PHP extensions remain trusted: this is not protection against a local process racing
filesystem changes or a privileged plugin overriding model behavior.

The existing inspection/content-read surface is not per-target isolation: an allowed capability can access every target supported by it.
Enabled and permitted eval or generic CLI execution is trusted and can bypass other permissions.
CLI command names (including help resources) must use canonical colon-separated names. Path separators,
dot segments, encoded alternatives and empty segments are rejected before policy checks or execution.
Installed command PHP remains trusted: `--help` still loads valid command definitions.
Revocation is checked per HTTP request, not continuously during an open stream. Local stdio and
loopback shared-token access remain trusted. See [Remote MCP permissions](docs/remote.md#configure-role-permissions) before
exposing the server.

Custom command tools inherit the CLI execution boundary: explicit project registration, command
allowlisting, deny precedence, `execution.cli` capability checks, and execute/write HTTP scopes.
Their input schemas and fixed command targets are not a sandbox or per-tool permission system.
They do not automatically impersonate the remote user. Granting CLI execution also grants access
to the generic CLI tool, so fixed custom-tool arguments do not restrict that broader capability.
See [Custom tools](docs/custom-tools.md#writes-and-permissions).

CORS preflight (`OPTIONS`) does not require a bearer token, but still enforces allowed origins and rejects
query-string credentials. Actual MCP requests require authentication; scopes and Kirby identity come
from the SDK's validated access token, not client-supplied request metadata.

## Reporting a Vulnerability

Please report security issues via GitHub Security Advisories for this repository.
If advisories are not available, open a GitHub issue and request a private reporting channel.
Do not include sensitive details in public issues.

We will acknowledge reports and follow up with next steps as soon as possible.
