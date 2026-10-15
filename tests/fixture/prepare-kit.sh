#!/usr/bin/env bash
set -euo pipefail

root="$(cd -- "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
kit="${1:-starterkit}"
case "$kit" in starterkit|plainkit) ;; *) echo 'Unsupported fixture kit' >&2; exit 1 ;; esac
version=6.0.0-beta.1
archive="$(mktemp)"
staging="$(mktemp -d)"
trap 'rm -f "$archive"; rm -rf "$staging"' EXIT
curl --fail --location --retry 3 "https://github.com/getkirby/$kit/archive/refs/tags/$version.zip" -o "$archive"
unzip -q "$archive" -d "$staging"
kit_root="$staging/$kit-$version"
actual="$(php -r 'require $argv[1] . "/kirby/bootstrap.php"; echo Kirby\Cms\App::version();' "$kit_root")"
locked="$(php -r '$lock = json_decode(file_get_contents($argv[1]), true); foreach ($lock["packages-dev"] as $package) { if ($package["name"] === "getkirby/cms") echo $package["version"]; }' "$root/composer.lock")"
if [[ "$actual" != "$version" || "$locked" != "$version" ]]; then
    echo "Fixture mismatch: kit=$actual, lock=$locked, expected=$version" >&2
    exit 1
fi
rm -rf "$root/tests/cms"
mv "$kit_root" "$root/tests/cms"
echo "Prepared $kit with Kirby $actual (matches composer.lock)"
