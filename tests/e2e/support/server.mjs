import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { openSync, existsSync } from 'node:fs';
import { environment, root, runtime } from './environment.mjs';
import { database } from './database.mjs';

if (existsSync(resolve(root, 'public/hot'))) {
    throw new Error('Stop Vite and remove its stale public/hot file before browser QA.');
}
if (!existsSync(resolve(root, 'public/build/manifest.json'))) {
    throw new Error('Run npm run build before browser QA.');
}
database('migrate');
database('reset');
const server = spawn(process.env.E2E_PHP ?? 'php', [
    '-d', 'variables_order=EGPCS',
    '-S', '127.0.0.1:8877', '-t', resolve(root, 'public'),
    resolve(root, 'tests/e2e/support/router.php'),
], { cwd: resolve(root, 'public'), env: environment, stdio: ['ignore', openSync(resolve(runtime, 'logs/server.log'), 'w'), openSync(resolve(runtime, 'logs/server-errors.log'), 'w')] });
for (const signal of ['SIGTERM', 'SIGINT']) {
    process.on(signal, () => server.kill());
}
server.on('exit', code => process.exit(code ?? 1));
