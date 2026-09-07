#!/usr/bin/env bash
# Screenshots every rendered page at phone and desktop width.
# Usage: tools/preview/shoot.sh [width] [suffix]
set -euo pipefail
CHROME=${CHROME:-/opt/pw-browsers/chromium-1194/chrome-linux/chrome}
DIR="$(cd "$(dirname "$0")" && pwd)"
W=${1:-390}
SUF=${2:-phone}
mkdir -p "$DIR/shots"
for f in "$DIR"/out/*.html; do
  name=$(basename "$f" .html)
  "$CHROME" --headless --disable-gpu --no-sandbox --hide-scrollbars \
    --virtual-time-budget=3000 --window-size="$W,2400" \
    --screenshot="$DIR/shots/$name-$SUF.png" "file://$f" >/dev/null 2>&1
done
echo "shot $(ls "$DIR"/out/*.html | wc -l) pages at ${W}px into $DIR/shots"
