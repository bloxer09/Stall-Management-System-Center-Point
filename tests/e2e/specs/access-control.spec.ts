import { test, expect } from '../fixtures';

const adminPaths = [
    '/admin/dashboard', '/admin/stalls', '/admin/floors', '/admin/grid-editor', '/admin/applications',
    '/admin/mappings', '/admin/payments', '/admin/tickets', '/admin/calendar', '/admin/tenants',
    '/admin/announcements', '/admin/reports', '/admin/audit-logs', '/admin/error-logs', '/admin/settings',
    '/admin/backups/private.sql',
];
const tenantPaths = ['/user/dashboard', '/user/leases', '/user/payments', '/user/tickets', '/user/documents'];

test('guest direct navigation redirects every restricted route to sign-in', async ({ page, auth }) => {
    test.setTimeout(90000);
    for (const path of [...adminPaths, ...tenantPaths, '/receipt/1/pdf', '/statement/1/pdf', '/files/contract/1', '/files/application-document/1', '/files/ticket-attachment/1']) {
        await test.step(path, async () => {
            const response = await page.goto(path);
            expect(response?.status()).toBe(200);
            const redirect = response?.request().redirectedFrom();
            expect(redirect?.url()).toContain(path);
            expect((await redirect?.response())?.status()).toBe(302);
            await expect(page).toHaveURL(/\?login=1$/);
            await expect(auth.loginForm).toBeVisible();
        });
    }
});

test('tenant direct navigation to every admin endpoint returns forbidden', async ({ auth, page }) => {
    test.setTimeout(60000);
    await auth.login();
    for (const path of adminPaths) {
        await test.step(path, async () => {
            expect((await page.goto(path))?.status()).toBe(403);
            await expect(page).toHaveURL(new RegExp(`${path.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$`));
            await expect(page.locator('body')).toContainText(/403|forbidden|unauthorized/i);
        });
    }
});

test('admin cannot enter tenant-only routes', async ({ auth, page }) => {
    await auth.login('admin');
    for (const path of tenantPaths) {
        expect((await page.goto(path))?.status()).toBe(403);
        await expect(page.locator('body')).toContainText(/403|forbidden/i);
    }
});

test('admin with two-factor enabled cannot bypass the challenge by URL', async ({ auth, page }) => {
    await auth.login('secure');
    for (const path of ['/admin/settings', '/admin/dashboard', '/admin/payments']) {
        expect((await page.goto(path))?.status()).toBe(200);
        await expect(page).toHaveURL(/\/two-factor$/);
        await expect(page.locator('body')).toContainText(/verification|security code|two.factor/i);
    }
});

test('tenant cannot read another tenant ticket through its URL', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.goto(`/user/tickets?ticket=${db.ids.privateTicketId}`);
    await expect(page).toHaveURL(new RegExp(`ticket=${db.ids.privateTicketId}$`));
    await expect(page.getByText('Private other tenant ticket', { exact: true })).not.toBeVisible();
    await expect(page.getByText('Private other tenant message', { exact: true })).not.toBeVisible();
    await expect(tickets.replyForm).not.toBeVisible();
});
