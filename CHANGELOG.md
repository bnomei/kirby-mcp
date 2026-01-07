# Changelog

All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [1.2.0] - 2026-01-07

- Dropped prompt-driven setup guidance in favor of Skills.
- Added bundled Codex/Claude Skills and documented how to copy them into the client.

## [1.1.1] - 2026-01-01

- Tiny improvement to the `kb/update-schema/blueprint-file.md` guide.

## [1.1.0] - 2026-01-01

- Updated MCP PHP SDK dependency to `mcp/sdk` v0.2.2.
- Added SIGINT/SIGTERM handling for graceful stdio server shutdown.
- Added Mago tool detection to the composer audit (`carthage-software/mago` or `mago` binary).
- Added `kirby_online_plugins` tool to search the official Kirby plugin directory (plugins.getkirby.com) and optionally fetch plugin details as markdown.
- Added runtime tools (`kirby_read_site_content`, `kirby_read_file_content`, `kirby_read_user_content`, `kirby_update_site_content`, `kirby_update_file_content`, `kirby_update_user_content`) plus resources (`kirby://site/content`, `kirby://file/content/{encodedIdOrUuid}`, `kirby://user/content/{encodedIdOrEmail}`) and blueprint update-schema guides.
- Added KB resources `kirby://kb` and `kirby://kb/{path}` to list and read bundled knowledge base documents.
- Added the Panel development KB (`kb/panel/`) with kirbyup + kirbyuse focus for better extension DX.

## [1.0.2] - 2025-12-21

- Remove composer.lock from composer audit outputs to reduce payload size for init/info tools/resources. thanks @medienbaecker

## [1.0.1] - 2025-12-21

- Fixed CI workflows and minor PHPStan reported errors.

## [1.0.0] - 2025-12-21

- Initial release.
