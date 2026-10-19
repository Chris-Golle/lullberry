#!/usr/bin/env bash
# Compare (default) or pull (--apply) the live theme from Hostinger into local.
# Usage: HOSTINGER_SSH="u123456789@1.2.3.4" ./sync-from-live.sh [--apply]
# Hostinger SSH port is usually 65002. Remote path may differ: check hPanel > Websites > SSH access.
set -euo pipefail
: "${HOSTINGER_SSH:?set HOSTINGER_SSH=user@host}"
PORT="${HOSTINGER_PORT:-65002}"
REMOTE="${HOSTINGER_PATH:-domains/lullberry.com/public_html/wp-content/themes/twentytwentyfive-child/}"
LOCAL="$(cd "$(dirname "$0")" && pwd)/app/public/wp-content/themes/twentytwentyfive-child/"
FLAGS=(-avzc --itemize-changes --exclude='.DS_Store' -e "ssh -p $PORT")
if [[ "${1:-}" == "--apply" ]]; then
  rsync "${FLAGS[@]}" "$HOSTINGER_SSH:$REMOTE" "$LOCAL"
else
  echo "Dry run (live -> local). Re-run with --apply to overwrite local."
  rsync "${FLAGS[@]}" --dry-run "$HOSTINGER_SSH:$REMOTE" "$LOCAL"
fi
