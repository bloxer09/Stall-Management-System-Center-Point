import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

export const root = resolve(import.meta.dirname, '../../..');
export const runtime = resolve(root, 'tests/e2e/.runtime');
export const baseURL = 'http://127.0.0.1:8877';
for (const dir of ['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/private', 'app/public', 'bootstrap']) {
    mkdirSync(resolve(runtime, dir), { recursive: true });
}
export const environment = {
    ...process.env,
    APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: baseURL,
    APP_KEY: `base64:${Buffer.alloc(32, 7).toString('base64')}`,
    LARAVEL_STORAGE_PATH: runtime,
    APP_CONFIG_CACHE: 'tests/e2e/.runtime/bootstrap/config.php',
    APP_ROUTES_CACHE: 'tests/e2e/.runtime/bootstrap/routes.php',
    APP_EVENTS_CACHE: 'tests/e2e/.runtime/bootstrap/events.php',
    APP_SERVICES_CACHE: 'tests/e2e/.runtime/bootstrap/services.php',
    APP_PACKAGES_CACHE: 'tests/e2e/.runtime/bootstrap/packages.php',
    DB_CONNECTION: 'sqlite', DB_DATABASE: resolve(runtime, 'e2e.sqlite'), DATABASE_URL: '',
    CACHE_DRIVER: 'file', CACHE_STORE: 'file', CACHE_PREFIX: 'e2e',
    SESSION_DRIVER: 'file', SESSION_COOKIE: 'e2e_session', SESSION_DOMAIN: '', SESSION_SECURE_COOKIE: 'false',
    VIEW_COMPILED_PATH: resolve(runtime, 'framework/views'),
    BCRYPT_ROUNDS: '4', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync',
    TURNSTILE_SITE_KEY: '', TURNSTILE_SECRET_KEY: '',
    SEMAPHORE_API_KEY: '', MAILJET_API_KEY: '', MAILJET_SECRET_KEY: '',
    PAYMONGO_SECRET_KEY: '', PAYMONGO_PUBLIC_KEY: '', PAYMONGO_WEBHOOK_TOKEN: '',
};
