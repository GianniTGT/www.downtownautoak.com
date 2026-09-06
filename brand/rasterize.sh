#!/usr/bin/env bash
# Rasterizes an SVG to a PNG at an exact pixel size.
#
# Headless Chromium refuses to make a window smaller than a few hundred pixels,
# so a 16 x 16 --window-size silently returns a clipped shot of a bigger canvas.
# The SVG is therefore drawn at its true size in the corner of a large window
# and the corner is cropped out afterwards. What comes back is the real
# vector rendering at that size, not a downsample of a larger one.
#
# Usage: rasterize.sh <in.svg> <out.png> <size>
set -euo pipefail
CHROME=${CHROME:-/opt/pw-browsers/chromium-1194/chrome-linux/chrome}
in=$1; out=$2; size=$3
tmp=$(mktemp -d)
cp "$in" "$tmp/m.svg"
cat > "$tmp/p.html" <<HTML
<style>html,body{margin:0;padding:0;background:transparent}img{display:block;width:${size}px;height:${size}px}</style>
<img src="m.svg">
HTML
canvas=$(( size > 1100 ? size + 40 : 1140 ))
"$CHROME" --headless --disable-gpu --no-sandbox --hide-scrollbars --default-background-color=00000000 \
  --window-size="${canvas},${canvas}" --screenshot="$tmp/full.png" "file://$tmp/p.html" >/dev/null 2>&1
php -r '
$src = imagecreatefrompng($argv[1]);
$s   = (int) $argv[3];
$dst = imagecreatetruecolor($s, $s);
imagealphablending($dst, false); imagesavealpha($dst, true);
imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
imagecopy($dst, $src, 0, 0, 0, 0, $s, $s);
imagepng($dst, $argv[2]);
' "$tmp/full.png" "$out" "$size"
rm -rf "$tmp"
