#!/usr/bin/env bash
# One step: commit and push the site repo (lullberry) and all scripts (LL_scripts).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
MSG="${1:-Update $(date +%F)}"
"$ROOT/scripts-repo/sync.sh"
cd "$ROOT"
git add -A
if git diff --cached --quiet; then echo "Site: nothing new."; else git commit -q -m "$MSG"; echo "Site: committed."; fi
git push -q
echo "Done."
