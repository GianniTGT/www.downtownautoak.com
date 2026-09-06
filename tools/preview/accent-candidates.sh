#!/usr/bin/env bash
# Renders the real home page with each accent candidate so the colour is chosen
# off the components at real size, which is what the brief asks for — twice on
# the sales site the render overturned what the reasoning had predicted.
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
CHROME=${CHROME:-/opt/pw-browsers/chromium-1194/chrome-linux/chrome}
mkdir -p "$DIR/shots"
i=0
for pair in "$@"; do
  name=${pair%%:*}; hex=${pair#*:}
  i=$((i+1))
  python3 - "$DIR/out/home.html" "$DIR/out/_accent-$name.html" "$hex" <<'PY'
import sys
src, dst, hex_ = sys.argv[1], sys.argv[2], sys.argv[3]
html = open(src).read()
def darker(h, f=0.85):
    r,g,b = (int(h[i:i+2],16) for i in (1,3,5))
    return '#%02X%02X%02X' % (int(r*f), int(g*f), int(b*f))
style = f'<style>:root{{--accent:{hex_};--accent-hover:{darker(hex_)}}}</style>'
open(dst,'w').write(html.replace('</head>', style + '\n<style>body{padding-top:0}</style></head>'))
PY
  "$CHROME" --headless --disable-gpu --no-sandbox --hide-scrollbars --virtual-time-budget=3000 \
    --window-size=1280,1180 --screenshot="$DIR/shots/accent-$name.png" "file://$DIR/out/_accent-$name.html" >/dev/null 2>&1
done
echo "rendered $i candidates"
