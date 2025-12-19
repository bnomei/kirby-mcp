#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd -- "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
fixture_root="${FIXTURE_ROOT:-$script_dir}"
cms_root="${CMS_ROOT:-$script_dir/../cms}"

usage() {
  echo "Usage: $(basename "$0") <fixture-subpath> [<fixture-subpath> ...]" >&2
}

resolve_dest() {
  local rel="$1"

  case "$rel" in
    site/*|content/*|media/*|storage/*|public/*|kirby/*|.kirby-mcp|.kirby-mcp/*)
      printf '%s\n' "$cms_root/$rel"
      ;;
    *)
      printf '%s\n' "$cms_root/site/$rel"
      ;;
  esac
}

copy_dir() {
  local rel="$1"
  local src="$fixture_root/$rel"

  if [[ ! -d "$src" ]]; then
    echo "Missing fixture directory: $src" >&2
    exit 1
  fi

  local dest
  dest="$(resolve_dest "$rel")"

  mkdir -p "$dest"
  command cp -R "$src/." "$dest/"
}

if [[ $# -lt 1 ]]; then
  usage
  exit 2
fi

mkdir -p "$cms_root"

for rel in "$@"; do
  copy_dir "$rel"
done
