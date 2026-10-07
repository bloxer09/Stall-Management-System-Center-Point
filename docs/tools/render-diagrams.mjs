// Renders draw.io pages to PNG with the diagrams.net viewer in headless Brave.
// Usage: node docs/tools/render-diagrams.mjs
import { spawn } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const DIAGRAMS = join(here, '..', 'diagrams');
const OUT = join(here, '..', 'assets');
const BRAVE = 'C:/Program Files/BraveSoftware/Brave-Browser/Application/brave.exe';
const PORT = 9334;
const SCALE = 2; // sharp enough for print

// [file, page name, output png]
const JOBS = [
    ['erd.drawio', null, 'fig-erd.png'],
    ['thesis-figures.drawio', 'System Flowchart for Admin', 'fig-flowchart-admin.png'],
    ['thesis-figures.drawio', 'System Flowchart for Tenant', 'fig-flowchart-tenant.png'],
    ['thesis-figures.drawio', 'Use Case Diagram', 'fig-use-case.png'],
    ['thesis-figures.drawio', 'Content Diagram', 'fig-content-diagram.png'],
];

mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const brave = spawn(BRAVE, ['--headless=new', `--remote-debugging-port=${PORT}`, '--window-size=1600,1200', '--no-first-run',
    `--user-data-dir=${join(process.env.TEMP || '.', 'stallm-render-profile')}`, 'about:blank'], { stdio: 'ignore' });

let wsUrl;
for (let i = 0; i < 50 && !wsUrl; i++) {
    try { wsUrl = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find((t) => t.type === 'page')?.webSocketDebuggerUrl; } catch { /* starting */ }
    if (!wsUrl) await sleep(200);
}
const ws = new WebSocket(wsUrl);
await new Promise((r) => ws.addEventListener('open', r, { once: true }));
let seq = 0;
const pending = new Map();
ws.addEventListener('message', (e) => { const m = JSON.parse(e.data); if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); } });
const send = (method, params = {}) => new Promise((res, rej) => { const id = ++seq; pending.set(id, (m) => (m.error ? rej(new Error(m.error.message)) : res(m.result))); ws.send(JSON.stringify({ id, method, params })); });
const evaluate = async (expression) => { const r = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true }); if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description || r.exceptionDetails.text); return r.result.value; };

await send('Page.enable');
const html = '<!doctype html><html><body style="margin:0;background:#fff"><div id="host"></div>'
    + '<script src="https://viewer.diagrams.net/js/viewer-static.min.js"></script></body></html>';
await send('Page.navigate', { url: 'data:text/html;charset=utf-8,' + encodeURIComponent(html) });
for (let i = 0; i < 100; i++) { if (await evaluate('typeof GraphViewer !== "undefined"')) break; await sleep(200); }

for (const [file, pageName, out] of JOBS) {
    const xml = readFileSync(join(DIAGRAMS, file), 'utf8');
    const pages = [...xml.matchAll(/<diagram[^>]*name="([^"]*)"/g)].map((m) => m[1].replace(/&amp;/g, '&'));
    const index = pageName ? pages.indexOf(pageName) : 0;
    if (index < 0) throw new Error(`page ${pageName} not in ${file}`);
    const box = await evaluate(`(async () => {
        const host = document.getElementById('host');
        host.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'mxgraph';
        div.setAttribute('data-mxgraph', JSON.stringify({ xml: ${JSON.stringify(xml)}, page: ${index}, nav: false, toolbar: '', resize: true, border: 20, 'auto-fit': false }));
        host.appendChild(div);
        GraphViewer.processElements();
        await new Promise(r => setTimeout(r, 1500));
        const svg = host.querySelector('svg');
        const r = svg.getBoundingClientRect();
        return { x: r.left + scrollX, y: r.top + scrollY, w: r.width, h: r.height };
    })()`);
    await send('Emulation.setDeviceMetricsOverride', { width: Math.ceil(box.x + box.w) + 10, height: Math.ceil(box.y + box.h) + 10, deviceScaleFactor: SCALE, mobile: false });
    await sleep(300);
    const { data } = await send('Page.captureScreenshot', { format: 'png', clip: { x: box.x, y: box.y, width: box.w, height: box.h, scale: 1 }, captureBeyondViewport: true });
    writeFileSync(join(OUT, out), Buffer.from(data, 'base64'));
    console.log('rendered', out, Math.round(box.w), 'x', Math.round(box.h));
}

ws.close();
brave.kill();
process.exit(0);
