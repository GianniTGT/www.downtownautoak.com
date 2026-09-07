#!/usr/bin/env node
/**
 * Looks at the rendered pages the way a browser does, and fails on the two
 * things a screenshot alone will not tell you:
 *
 *   1. horizontal scroll at phone width — the commonest way a mobile-first
 *      layout quietly stops being mobile-first
 *   2. a JavaScript error on load
 *
 * It also reports tap targets under 44px and images without an alt attribute.
 *
 * No dependencies: it drives Chromium over the DevTools protocol using Node's
 * own WebSocket. Run it after tools/preview/render.php.
 *
 *   node tools/preview/audit.js [width]
 */
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const OUT = path.join(__dirname, 'out');
const WIDTH = parseInt(process.argv[2] || '390', 10);
const PORT = 9222 + (WIDTH % 100);

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function main() {
  const chrome = spawn(CHROME, [
    '--headless', '--disable-gpu', '--no-sandbox', '--hide-scrollbars',
    `--remote-debugging-port=${PORT}`, `--window-size=${WIDTH},900`, 'about:blank',
  ], { stdio: 'ignore' });

  let version = null;
  for (let i = 0; i < 60 && !version; i++) {
    try { version = await (await fetch(`http://127.0.0.1:${PORT}/json/version`)).json(); }
    catch { await sleep(200); }
  }
  if (!version) { chrome.kill(); throw new Error('Chromium did not start'); }

  const pages = fs.readdirSync(OUT).filter((f) => f.endsWith('.html')).sort();
  const failures = [];

  for (const file of pages) {
    const target = await (await fetch(`http://127.0.0.1:${PORT}/json/new?about:blank`, { method: 'PUT' })).json();
    const result = await inspect(target.webSocketDebuggerUrl, 'file://' + path.join(OUT, file));
    await fetch(`http://127.0.0.1:${PORT}/json/close/${target.id}`);

    const overflow = result.scrollWidth > result.innerWidth;
    const flag = (overflow || result.errors.length) ? 'FAIL' : ' ok ';
    console.log(
      `${flag}  ${file.padEnd(20)} width ${String(result.scrollWidth).padStart(5)}/${result.innerWidth}` +
      `  small taps ${String(result.smallTaps).padStart(2)}${result.smallTaps ? ' (' + result.smallList.join(', ') + ')' : ''}  imgs missing alt ${String(result.noAlt).padStart(2)}` +
      (result.errors.length ? `  JS: ${result.errors[0]}` : '')
    );
    if (overflow) { failures.push(`${file}: scrolls sideways (${result.scrollWidth} > ${result.innerWidth})`); }
    for (const e of result.errors) { failures.push(`${file}: ${e}`); }
  }

  chrome.kill();
  if (failures.length) {
    console.log('\n' + failures.length + ' problem(s):');
    failures.forEach((f) => console.log('  - ' + f));
    process.exit(1);
  }
  console.log(`\nAll ${pages.length} pages clean at ${WIDTH}px.`);
}

function inspect(wsUrl, url) {
  return new Promise((resolve, reject) => {
    const ws = new WebSocket(wsUrl);
    const errors = [];
    let id = 0;
    const pending = new Map();
    const send = (method, params = {}) => new Promise((res) => {
      const msgId = ++id;
      pending.set(msgId, res);
      ws.send(JSON.stringify({ id: msgId, method, params }));
    });

    ws.onerror = reject;
    ws.onmessage = (ev) => {
      const msg = JSON.parse(ev.data);
      if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); return; }
      if (msg.method === 'Runtime.exceptionThrown') {
        errors.push(msg.params.exceptionDetails.exception?.description || msg.params.exceptionDetails.text);
      }
      if (msg.method === 'Log.entryAdded' && msg.params.entry.level === 'error') {
        // A blocked font or a missing placeholder is the harness's problem, not the theme's.
        if (!/net::ERR|Failed to load resource/.test(msg.params.entry.text)) { errors.push(msg.params.entry.text); }
      }
    };

    ws.onopen = async () => {
      await send('Runtime.enable');
      await send('Log.enable');
      await send('Page.enable');
      // A tab opened over the protocol does not inherit --window-size, so the
      // viewport is set explicitly. Without this the audit measures 500px and
      // passes a layout that breaks on a real phone.
      await send('Emulation.setDeviceMetricsOverride', {
        width: WIDTH, height: 844, deviceScaleFactor: 1, mobile: WIDTH < 700,
      });
      await send('Page.navigate', { url });
      await sleep(1200);
      const evaluated = await send('Runtime.evaluate', {
        returnByValue: true,
        expression: `(() => {
          // Only the things a thumb is meant to hit: buttons, form controls and
          // menu links. An inline link inside a paragraph is not a tap target.
          const small = [...document.querySelectorAll('.btn,button,input:not(.hp input),select,textarea,.menu a')].filter(el => {
            const r = el.getBoundingClientRect();
            if (!r.width || !r.height) { return false; }
            // A checkbox is 18px by design; what the thumb hits is the label
            // wrapped around it, so that is what gets measured.
            const box = (el.type === 'checkbox' || el.type === 'radio') ? (el.closest('label') || el) : el;
            return box.getBoundingClientRect().height < 44;
          }).map(el => el.tagName.toLowerCase() + (el.className ? '.' + String(el.className).split(' ')[0] : ''));
          const noAlt = [...document.images].filter(i => !i.hasAttribute('alt')).length;
          return {
            scrollWidth: document.documentElement.scrollWidth,
            innerWidth: window.innerWidth,
            smallTaps: small.length,
            smallList: [...new Set(small)].slice(0, 4),
            noAlt
          };
        })()`,
      });
      ws.close();
      resolve(Object.assign({ errors }, evaluated.result.value));
    };
  });
}

main().catch((e) => { console.error(e); process.exit(1); });
