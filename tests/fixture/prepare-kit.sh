#!/usr/bin/env bash
set -euo pipefail

root="$(cd -- "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
kit="${1:-starterkit}"
case "$kit" in starterkit|plainkit) ;; *) echo 'Unsupported fixture kit' >&2; exit 1 ;; esac
version=6.0.0-beta.3
kit_version=6.0.0-beta.2
archive="$(mktemp)"
staging="$(mktemp -d)"
trap 'rm -f "$archive"; rm -rf "$staging"' EXIT
curl --fail --location --retry 3 "https://github.com/getkirby/$kit/archive/refs/tags/$kit_version.zip" -o "$archive"
unzip -q "$archive" -d "$staging"
kit_root="$staging/$kit-$kit_version"
# Kits lag the CMS release; replace their runtime with the exact tested core.
curl --fail --location --retry 3 "https://github.com/getkirby/kirby/archive/refs/tags/$version.zip" -o "$archive"
unzip -q "$archive" -d "$staging"
rm -rf "$kit_root/kirby"
mv "$staging/kirby-$version" "$kit_root/kirby"
composer install --working-dir="$kit_root/kirby" --no-dev --no-scripts --no-interaction --prefer-dist
actual="$(php -r 'require $argv[1] . "/kirby/bootstrap.php"; echo Kirby\Cms\App::version();' "$kit_root")"
locked="$(php -r '$lock = json_decode(file_get_contents($argv[1]), true); foreach ($lock["packages-dev"] as $package) { if ($package["name"] === "getkirby/cms") echo $package["version"]; }' "$root/composer.lock")"
if [[ "$actual" != "$version" || "$locked" != "$version" ]]; then
    echo "Fixture mismatch: kit=$actual, lock=$locked, expected=$version" >&2
    exit 1
fi
rm -rf "$root/tests/cms"
mv "$kit_root" "$root/tests/cms"
echo "Prepared $kit with Kirby $actual (matches composer.lock)"
