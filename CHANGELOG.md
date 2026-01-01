# Changelog

All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [Unreleased]

- Added Mago tool detection to the composer audit (`carthage-software/mago` or `mago` binary).
- Added `kirby_online_plugins` tool to search the official Kirby plugin directory (plugins.getkirby.com) and optionally fetch plugin details as markdown.

## [1.0.2] - 2025-12-21

- Remove composer.lock from composer audit outputs to reduce payload size for init/info tools/resources. thanks @medienbaecker

## [1.0.1] - 2025-12-21

- Fixed CI workflows and minor PHPStan reported errors.

## [1.0.0] - 2025-12-21

- Initial release.
