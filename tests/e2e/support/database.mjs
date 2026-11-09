import { execFileSync } from 'node:child_process';
import { environment, root } from './environment.mjs';

export function database(command) {
    return JSON.parse(execFileSync(process.env.E2E_PHP ?? 'php', ['-d', 'variables_order=EGPCS', 'tests/e2e/support/database.php', command], {
        cwd: root, env: environment, encoding: 'utf8', timeout: 60000,
    }));
}
