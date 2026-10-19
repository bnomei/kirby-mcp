# Native browsing, uploads and moves

[README](../README.md) · [Configuration](configuration.md) · [Tool and resource reference](reference.md) · [Remote access](remote.md)

`content` always means Kirby fields (including file alt text/captions); `read` means original file bytes.
`kirby_view_image` returns actual MCP image content, not a website URL or base64 inside JSON.
All new operations resolve Kirby models and check native permissions as the authenticated user.
They expose site/page attachments, not accounts, configuration files or arbitrary filesystem paths.
Byte reads and previews reject symlink aliases below the configured content root, including aliases to
another page's files. They require both file and parent `read`/`access` permissions.

## Browse pages and attached files

Use `parent: {"type":"site"}` or `parent: {"type":"page","id":"page://…"}`.
Summaries prefer full UUIDs in `id` and resource links, include the current `pathId`, and return
`uuid: null` with path IDs when UUIDs are disabled. Website `url` values are not authorized download links.
Child listings accept `status` (`all`, `draft`, `listed`, `unlisted`, `published`); file listings accept
`template` and `type`. Both accept `limit` (1–100, default 50) and an opaque `cursor` returned as
`nextCursor`. Keep filters unchanged between calls. Resources return `nextUri` under
`kirby://browse/children/{encodedParent}/{limit}/{encodedCursor}/{encodedFilters}` or
`kirby://browse/files/{encodedParent}/{limit}/{encodedCursor}/{encodedFilters}`; follow these links
instead of building them. Parent and filters are URL-encoded JSON; `start` selects the first cursor.
Listings are live, not snapshots: concurrent moves/renames can change later pages.

## Read files and view images

Original UTF-8 text is returned unchanged, including CSV line endings; binary files use MCP blobs.
Oversized reads fail explicitly without partial data (default 4 MiB). Image previews support JPEG,
PNG, WebP and GIF originals up to 40 megapixels, produce a JPEG bounded by `maxWidth`/`maxHeight`
(default 1280 each, maximum 2048), and reject previews over 2 MiB. They include alt text in the requested
metadata `language`, original dimensions and preview dimensions; they do not modify the original.
If Kirby cannot create a thumbnail version, the tool fails instead of returning the original as a preview.

## Upload files

For uploads, `uploadContext` names a file picker or file list on `parent`. Kirby's resolved blueprint
controls the effective destination, template, enabled state and remaining capacity. Initial `content`
metadata is supplied during creation, including `language` translations, so creation hooks can see it.
Metadata keys `template` and `uuid` are reserved (case-insensitive) and rejected before preview or ticket
issuance: the blueprint controls the template and Kirby generates the UUID.
Uploading does **not** insert the file into a cover/gallery field; use the existing content-update tool
with its returned UUID for that. Existing filenames, including transformed output filenames, are rejected.

```json
{
  "parent": { "type": "page", "id": "page://…" },
  "filename": "results.csv",
  "uploadContext": "attachments",
  "content": { "caption": "Annual results" },
  "source": "/absolute/local/path/results.csv",
  "confirm": false
}
```

Local stdio copies `source` and preserves the original. On HTTP, omit `source`: confirmed calls return
an `uploadUrl` and a ten-minute, single-use `ticket`. POST raw bytes (not multipart or JSON) to that URL,
using your normal `Authorization: Bearer …` plus `X-Kirby-Upload-Ticket: …`. The transfer response returns
the created file. Tickets bind the user, context, destination, template, filename, metadata and byte limit;
redemption rechecks permissions and context and consumes the ticket even if validation fails. HTTP never
accepts server source paths. `KirbyMcpRoutes::routes()` includes the upload route automatically; manually
copied routes must also forward `POST|OPTIONS` at the MCP path plus `/upload` to `KirbyMcpRoute::handle()`.

### Limits and deployment

Kirby PHP config options (not `.kirby-mcp/mcp.json`):

| Option                                  | Default  | Meaning                                                                                 |
| --------------------------------------- | -------- | --------------------------------------------------------------------------------------- |
| `bnomei.kirby-mcp.files.readMaxBytes`   | 4194304  | Maximum original read response                                                          |
| `bnomei.kirby-mcp.files.uploadMaxBytes` | 20971520 | Maximum upload bytes; native blueprint limits also apply                                |
| `bnomei.kirby-mcp.files.directUpload`   | `false`  | Explicitly permit context-free uploads using the destination's `default` file blueprint |

Direct uploads enforce native create/accept rules but do not claim Panel field/list capacity enforcement.
MCP uploads serialize their capacity/collision checks; Panel or other external writers do not share that lock.
Set reverse-proxy/PHP body limits too. Tickets use private local storage under `.kirby-mcp/uploads`;
multi-host deployments need shared request routing to the same storage, not independent ticket stores.

## Move pages

`kirby_move_page` takes `id`, destination `parent` and `confirm`. Preview returns the existing page and
destination. Execution uses native `Page::move()`, rejecting cycles, collisions and invalid blueprint
destinations; it returns old/new path IDs and parents, the stable UUID, URL and updated resource links.

## Remote permissions

Remote reads use the runtime scope; uploads/moves use write. Capability leaves are `content.pages.read`,
`content.files.read`, `content.files.create`, and `content.pages.move`, with all ancestors also required.
