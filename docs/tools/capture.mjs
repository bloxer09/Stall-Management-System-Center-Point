// Screenshots of the running system for the manuscript and deck.
// Drives headless Brave over the Chrome DevTools Protocol (Node 24 built-in WebSocket, no npm deps).
//
// Usage: node docs/tools/capture.mjs [base-url] [only-name]
//   base-url defaults to http://127.0.0.1:8001 (a demo-data copy of the system; see the plan).
import { spawn } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const BASE = process.argv[2] || 'http://127.0.0.1:8001';
const ONLY = process.argv[3] || null;
const OUT = join(here, '..', 'assets');
const BRAVE = 'C:/Program Files/BraveSoftware/Brave-Browser/Application/brave.exe';
const PORT = 9333;
const shots = JSON.parse(readFileSync(join(here, 'capture-shots.json'), 'utf8'));
// Demo accounts created by database/seeders/DemoSeeder.php (local demo data only).
const ACCOUNTS = { admin: ['admin@stallm.com', 'password'], tenant: ['tenant1@demo.test', 'password'] };

mkdirSync(OUT, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const brave = spawn(BRAVE, [
    '--headless=new', `--remote-debugging-port=${PORT}`, '--window-size=1440,900',
    '--hide-scrollbars', '--force-device-scale-factor=1', '--no-first-run', '--no-default-browser-check',
    `--user-data-dir=${join(process.env.TEMP || '.', 'stallm-capture-profile')}`, 'about:blank',
], { stdio: 'ignore' });

async function target() {
    for (let i = 0; i < 50; i++) {
        try {
            const list = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
            const page = list.find((t) => t.type === 'page');
            if (page) return page.webSocketDebuggerUrl;
        } catch { /* not up yet */ }
        await sleep(200);
    }
    throw new Error('Brave did not start');
}

const ws = new WebSocket(await target());
await new Promise((r) => ws.addEventListener('open', r, { once: true }));
let seq = 0;
const pending = new Map();
ws.addEventListener('message', (e) => {
    const msg = JSON.parse(e.data);
    if (msg.id && pending.has(msg.id)) {
        pending.get(msg.id)(msg);
        pending.delete(msg.id);
    }
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++seq;
    pending.set(id, (msg) => (msg.error ? reject(new Error(`${method}: ${msg.error.message}`)) : resolve(msg.result)));
    ws.send(JSON.stringify({ id, method, params }));
});
const evaluate = async (expression) => {
    const res = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (res.exceptionDetails) throw new Error(res.exceptionDetails.exception?.description || res.exceptionDetails.text);
    return res.result.value;
};
const settle = async (extra = 900) => {
    for (let i = 0; i < 60; i++) {
        if (await evaluate('document.readyState') === 'complete') break;
        await sleep(150);
    }
    await sleep(extra);
};
const go = async (path) => { await send('Page.navigate', { url: BASE + path }); await settle(); };

// Livewire helper available on every page: lw('component.name').call(...) / .set(...)
const LW = `window.lw = (name) => { const c = Livewire.all().find((x) => x.name === name); if (!c) throw new Error('no component ' + name); return c.$wire; };`;

let signedInAs = 'guest';
async function signIn(role) {
    if (signedInAs === role) return;
    await send('Network.clearBrowserCookies');
    signedInAs = 'guest';
    if (role === 'guest') return;
    await go('/');
    const [email, password] = ACCOUNTS[role];
    await evaluate(`${LW}
        (async () => {
            const w = lw('auth.auth-modals');
            await w.set('loginEmail', ${JSON.stringify(email)});
            await w.set('loginPassword', ${JSON.stringify(password)});
            await w.call('processLogin');
        })()`);
    await sleep(2500);
    await settle();
    signedInAs = role;
}

await send('Page.enable');
await send('Runtime.enable');
await send('Network.enable');
await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
// Light theme reads better in a printed manuscript (the app follows prefers-color-scheme).
await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-color-scheme', value: 'light' }] });

const done = [];
for (const shot of shots) {
    if (ONLY && shot.name !== ONLY) continue;
    await signIn(shot.as || 'guest');
    await go(shot.url);
    if (shot.js) {
        await evaluate(`${LW} (async () => { ${shot.js} })()`);
        await settle(shot.wait ?? 1200);
    }
    if (shot.type) {
        // Real keyboard input, so focus/input handlers (Alpine, wire:model.live) fire as for a user.
        await evaluate(`document.querySelector(${JSON.stringify(shot.type.selector)}).focus()`);
        await send('Input.insertText', { text: shot.type.text });
        await settle(shot.wait ?? 1500);
    }
    const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    writeFileSync(join(OUT, `${shot.name}.png`), Buffer.from(data, 'base64'));
    done.push(shot.name);
    console.log('captured', shot.name);
}

ws.close();
brave.kill();
console.log(`done: ${done.length} screenshots in docs/assets`);
process.exit(0);
