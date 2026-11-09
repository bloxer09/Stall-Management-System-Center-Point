import { test as base, expect, type Page } from '@playwright/test';
import { readFileSync, existsSync, readdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { database } from './support/database.mjs';
import { runtime } from './support/environment.mjs';
import { AuthPage } from './pages/auth.page';
import { TicketsPage } from './pages/tickets.page';
import { AnnouncementsPage } from './pages/announcements.page';
import { LeasesPage } from './pages/leases.page';

export type Row = Record<string, any>;
export type Snapshot = Record<string, Row[]>;
type Fixtures = {
    db: { snapshot: () => Snapshot; ids: { tenantId: number; adminId: number; availableStallId: number; privateTicketId: number } };
    auth: AuthPage;
    tickets: TicketsPage;
    announcements: AnnouncementsPage;
    leases: LeasesPage;
    diagnostics: void;
};

export const test = base.extend<Fixtures>({
    db: async ({}, use, info) => {
        if (info.config.workers !== 1 || process.env.TEST_SHARD || info.config.shard) {
            throw new Error('The isolated database requires one worker and no sharding.');
        }
        const ids = database('reset');
        await use({ ids, snapshot: () => database('snapshot') as Snapshot });
    },
    diagnostics: [async ({ context, page, db }, use, info) => {
        const events: object[] = [];
        const errors: string[] = [];
        const watch = (observed: Page) => {
            observed.on('console', msg => {
                if (['error', 'warning'].includes(msg.type())) events.push({ type: msg.type(), message: msg.text(), location: msg.location() });
            });
            observed.on('pageerror', error => { errors.push(error.message); events.push({ type: 'pageerror', url: observed.url(), message: error.stack }); });
            observed.on('requestfailed', request => events.push({ type: 'requestfailed', method: request.method(), url: request.url(), error: request.failure() }));
            observed.on('response', response => {
                if (response.status() >= 400) events.push({ type: 'http-error', status: response.status(), method: response.request().method(), url: response.url() });
            });
        };
        watch(page);
        context.on('page', watch);
        await context.route(/https?:\/\/(?!127\.0\.0\.1:8877(?:\/|$))/, route => route.abort());
        const logPath = resolve(runtime, 'logs/laravel.log');
        const logOffset = existsSync(logPath) ? readFileSync(logPath).length : 0;
        await use();
        const isolatedSessions = readdirSync(resolve(runtime, 'framework/sessions'));
        if (!isolatedSessions.length) events.push({ type: 'isolation-error', message: 'No HTTP sessions were written to isolated storage.' });
        if (info.status !== info.expectedStatus || errors.length || !isolatedSessions.length) {
            await info.attach('browser-diagnostics', { body: JSON.stringify({ url: page.url(), events, errors: info.errors }, null, 2), contentType: 'application/json' });
            await info.attach('database-state', { body: JSON.stringify(db.snapshot(), null, 2), contentType: 'application/json' });
            if (existsSync(logPath)) {
                await info.attach('laravel-log', { body: readFileSync(logPath).subarray(logOffset), contentType: 'text/plain' });
            }
            for (const [index, openPage] of context.pages().entries()) {
                if (!openPage.isClosed()) {
                    const screenshot = await openPage.screenshot({ fullPage: true, timeout: 5000 }).catch(() => null);
                    if (screenshot) await info.attach(`failure-page-${index}`, { body: screenshot, contentType: 'image/png' });
                }
            }
        }
        expect(errors, 'Uncaught browser errors').toEqual([]);
        expect(isolatedSessions.length, 'HTTP sessions must use tests/e2e/.runtime storage').toBeGreaterThan(0);
    }, { auto: true }],
    auth: async ({ page }, use) => use(new AuthPage(page)),
    tickets: async ({ page }, use) => use(new TicketsPage(page)),
    announcements: async ({ page }, use) => use(new AnnouncementsPage(page)),
    leases: async ({ page }, use) => use(new LeasesPage(page)),
});
export { expect };
