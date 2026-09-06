#!/usr/bin/env node
/**
 * Builds the DAAK brand marks and the favicon.
 *
 * The dealership's badge is a navy plate: white wing bars flanking a ringed
 * circle with the letters inside. The rental brand keeps that silhouette and
 * changes the letters, because the 4.6-from-92 reputation only transfers if a
 * customer recognises the same firm in half a second.
 *
 * Four letters where there were three is the entire risk in this job, and it
 * fails first at 16px. It was measured, not guessed (brand/out/proof.png):
 *
 *   full badge, DAAK in the ring   48px readable, 32px mush, 16px gone
 *   stacked DAAK square            32px and up readable, 16px mush
 *   AK monogram                    reads at 16px
 *
 * So the favicon is the monogram, exactly as the brief's fallback says, and the
 * ICO carries the right drawing for each size rather than one drawing squeezed:
 * 16 and 32 are the AK monogram, 48 and up are the stacked DAAK.
 *
 * Everything here is drawn as geometry. No font has to be installed anywhere
 * for the marks to render identically.
 *
 * Run:  node brand/build-brand.js       (needs headless Chromium for the PNGs)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const NAVY = '#08318B';   // measured off the stylesheet the site actually serves
const WHITE = '#FFFFFF';
const DIR = __dirname;
const OUT = path.join(DIR, 'out');

/** Geometric stroked letterforms on a 60 x 80 box. Heavy, condensed, font-free. */
const GLYPH = {
  D: 'M10 6 V74 M10 6 H30 C58 6 58 74 30 74 H10',
  A: 'M4 74 L30 6 L56 74 M14 52 H46',
  K: 'M10 6 V74 M52 6 L16 40 M24 32 L54 74',
};
const GW = 60, GH = 80;

function word(letters, { x, y, s, w, gap, cap = 'butt' }) {
  let out = '', cx = x;
  for (const ch of letters) {
    out += `<path d="${GLYPH[ch]}" transform="translate(${cx.toFixed(2)} ${y.toFixed(2)}) scale(${s})"`
         + ` fill="none" stroke="${WHITE}" stroke-width="${w}" stroke-linecap="${cap}" stroke-linejoin="miter"/>`;
    cx += GW * s + gap;
  }
  return out;
}
const wordWidth = (n, s, gap) => n * GW * s + (n - 1) * gap;
const svg = (body, S = 512, label = '') =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${S} ${S}" width="${S}" height="${S}"`
  + (label ? ` role="img" aria-label="${label}"` : ' aria-hidden="true"') + `>\n  ${body}\n</svg>\n`;

/** The full badge: navy plate, wings, ring, DAAK. Header, print, large PNGs. */
function badge() {
  const S = 512, c = S / 2, s = 0.62, gap = 3, w = 11;
  const ww = wordWidth(4, s, gap);
  let wings = '';
  for (let i = 0; i < 3; i++) {
    const y = c - 41 + i * 34, inset = i === 1 ? 0 : 16;
    wings += `<rect x="${20 + inset}" y="${y}" width="${84 - inset}" height="13" rx="6.5" fill="${WHITE}"/>`
           + `<rect x="${S - 104 + inset}" y="${y}" width="${84 - inset}" height="13" rx="6.5" fill="${WHITE}"/>`;
  }
  return svg(`<rect width="${S}" height="${S}" rx="76" fill="${NAVY}"/>\n  ${wings}\n`
    + `  <circle cx="${c}" cy="${c}" r="150" fill="none" stroke="${WHITE}" stroke-width="14"/>\n  `
    + word('DAAK', { x: c - ww / 2, y: c - (GH * s) / 2, s, w, gap }), S, 'Downtown Auto AK');
}

/** The stacked square: DAAK two-by-two. Reads from 32px up. */
function stacked() {
  const S = 512, s = 1.8, gap = 16, w = 18, rowGap = 22;
  const ww = wordWidth(2, s, gap), h = GH * s, top = (S - (h * 2 + rowGap)) / 2;
  return svg(`<rect width="${S}" height="${S}" rx="72" fill="${NAVY}"/>\n  `
    + word('DA', { x: (S - ww) / 2, y: top, s, w, gap })
    + word('AK', { x: (S - ww) / 2, y: top + h + rowGap, s, w, gap }), S, 'DAAK');
}

/** The monogram: AK alone, drawn open enough to survive 16 pixels. */
function monogram() {
  const S = 512, s = 2.6, gap = 44, w = 22;
  const ww = wordWidth(2, s, gap), h = GH * s;
  return svg(`<rect width="${S}" height="${S}" rx="88" fill="${NAVY}"/>\n  `
    + word('AK', { x: (S - ww) / 2, y: (S - h) / 2, s, w, gap }), S, 'Downtown Auto AK');
}

/** Header lockup: the badge with the wordmark beside it, which is what a four-letter mark needs. */
function lockup() {
  const inner = badge().replace(/^[\s\S]*?>\n/, '').replace(/<\/svg>\s*$/, '');
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 468 96" width="468" height="96" role="img" aria-label="Downtown Auto AK">
  <g transform="translate(0 4) scale(0.1719)">${inner}</g>
  <text x="106" y="45" font-family="'Barlow Condensed','Arial Narrow',sans-serif" font-size="36" font-weight="800" letter-spacing="1" fill="${NAVY}">DOWNTOWN AUTO</text>
  <text x="106" y="79" font-family="'Barlow','Segoe UI',sans-serif" font-size="24" font-weight="600" letter-spacing="7.5" fill="#5A6785">ALASKA</text>
</svg>\n`;
}

/** ICO with PNG payloads: one directory entry per size, each its own rendering. */
function ico(entries) {
  const n = entries.length;
  const head = Buffer.alloc(6);
  head.writeUInt16LE(0, 0); head.writeUInt16LE(1, 2); head.writeUInt16LE(n, 4);
  const dir = Buffer.alloc(16 * n);
  let offset = 6 + 16 * n;
  const blobs = [];
  entries.forEach(([size, file], i) => {
    const png = fs.readFileSync(file);
    const b = i * 16;
    dir.writeUInt8(size >= 256 ? 0 : size, b);       // 0 means 256 in the ICO directory
    dir.writeUInt8(size >= 256 ? 0 : size, b + 1);
    dir.writeUInt8(0, b + 2); dir.writeUInt8(0, b + 3);
    dir.writeUInt16LE(1, b + 4); dir.writeUInt16LE(32, b + 6);
    dir.writeUInt32LE(png.length, b + 8); dir.writeUInt32LE(offset, b + 12);
    offset += png.length;
    blobs.push(png);
  });
  return Buffer.concat([head, dir, ...blobs]);
}

const raster = (svgFile, pngFile, size) =>
  execFileSync(path.join(DIR, 'rasterize.sh'), [svgFile, pngFile, String(size)], { stdio: 'inherit' });

fs.mkdirSync(OUT, { recursive: true });
const files = {
  'daak-badge.svg': badge(),
  'daak-mark.svg': stacked(),
  'daak-monogram.svg': monogram(),
  'daak-lockup.svg': lockup(),
};
for (const [name, body] of Object.entries(files)) fs.writeFileSync(path.join(DIR, name), body);

if (process.argv.includes('--svg-only')) { console.log('SVG masters written.'); process.exit(0); }

for (const size of [512, 1024]) raster(path.join(DIR, 'daak-badge.svg'), path.join(OUT, `daak-badge-${size}.png`), size);
for (const size of [16, 32]) raster(path.join(DIR, 'daak-monogram.svg'), path.join(OUT, `icon-${size}.png`), size);
for (const size of [48, 64, 128, 256]) raster(path.join(DIR, 'daak-mark.svg'), path.join(OUT, `icon-${size}.png`), size);
raster(path.join(DIR, 'daak-mark.svg'), path.join(OUT, 'daak-mark-512.png'), 512);

fs.writeFileSync(path.join(OUT, 'favicon.ico'),
  ico([16, 32, 48, 64, 128, 256].map(s => [s, path.join(OUT, `icon-${s}.png`)])));

console.log('Brand built: SVG masters, PNG 512/1024, favicon.ico (16/32/48/64/128/256).');
