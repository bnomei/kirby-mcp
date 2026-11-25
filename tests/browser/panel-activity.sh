#!/usr/bin/env bash
# Run against the prepared, disposable orb Starterkit after the PHP suite.
set -euo pipefail
cd "$(dirname "$0")/../.."
port="${1:?Pass the local port printed by amp orb service status kirby}"
[[ "$port" =~ ^[0-9]+$ ]]
base="http://127.0.0.1:$port"
portal="$(jq -er '.links[0].url' .amp/portals/kirby.json)"
marker=tests/cms/.kirby-mcp/activity
robot=.kirby-mcp-activity
session="kirby-panel-$$"
browser() { agent-browser --session "$session" "$@"; }
trap 'browser close >/dev/null; rm -f "$marker"' EXIT

rm -f "$marker"
test "$(curl -sS -o /dev/null -w '%{http_code}' "$base/api/kirby-mcp/activity")" = 401
browser open "${portal%/}/panel/login"
browser set viewport 1280 720 2
browser wait 'input[type=email]'
browser fill 'input[type=email]' "$(cat .amp/secrets/kirby-panel-email)" >/dev/null
browser fill 'input[type=password]' "$(cat .amp/secrets/kirby-panel-password)" >/dev/null
browser click 'button[type=submit]'
browser wait --fn "window.panel?.user?.role === 'admin' && document.querySelector('$robot')?.hidden === true"

# A real MCP operation, not a fabricated timestamp, must light up the badge.
curl -fsS "$base/mcp" -H "Authorization: Bearer $(cat .amp/secrets/kirby-mcp-token)" \
  -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' \
  -H 'MCP-Protocol-Version: 2026-07-28' -H 'Mcp-Method: resources/read' -H 'Mcp-Name: kirby://kb' \
  --data '{"jsonrpc":"2.0","id":1,"method":"resources/read","params":{"uri":"kirby://kb","_meta":{"io.modelcontextprotocol/protocolVersion":"2026-07-28","io.modelcontextprotocol/clientCapabilities":{},"io.modelcontextprotocol/clientInfo":{"name":"panel-check","version":"1"}}}}' \
  | jq -e '.result.resultType == "complete" and (.result.contents | length > 0)' >/dev/null
browser wait "$robot[data-state=active]:not([hidden])"
browser click "$robot"
browser wait --fn "getComputedStyle(document.querySelector('${robot}__label')).visibility === 'visible'"

# Accelerate the age of the real event. Only the backend knows the new age.
poll() { browser eval 'document.dispatchEvent(new Event("visibilitychange"))' >/dev/null; }
age() { printf %s "$(($(date +%s) - $1))" >"$marker"; poll; }
age 60; browser wait "$robot[data-state=recent]:not([hidden])"
browser set viewport 390 844 2
age 180; browser wait "$robot[data-state=idle]:not([hidden])"
age 301; browser wait --fn "document.querySelector('$robot').hidden"

age 0; browser wait "$robot[data-state=active]:not([hidden])"
browser network route '**/kirby-mcp/activity*' --abort
poll; browser wait --fn "document.querySelector('$robot').hidden"
browser network unroute '**/kirby-mcp/activity*'
poll; browser wait "$robot[data-state=active]:not([hidden])"

# In-place Panel navigation exercises stale user state on logout.
browser eval 'window.panel.open("logout").then(() => true)' >/dev/null
browser wait 'input[type=password]'
browser wait --fn "document.querySelector('$robot').hidden"
echo 'Panel activity passed: anonymous denial, admin login, MCP read, label, age states, poll failure/recovery, logout.'
