import { test, expect } from '../fixtures';
import { TicketsPage } from '../pages/tickets.page';
import { LeasesPage } from '../pages/leases.page';
import { holdAction } from '../support/interception';

test('rapid announcement clicks send one request and create one row', async ({ auth, announcements, page, db }) => {
    await auth.login('admin');
    await announcements.open();
    await announcements.fill('Rapid click notice', 'Only one announcement should be stored.');
    const gate = await holdAction(page, 'post');
    try {
        await announcements.submit.click();
        await gate.held;
        await expect(announcements.submit).toBeDisabled();
        const box = await announcements.submit.boundingBox();
        expect(box).not.toBeNull();
        for (let index = 0; index < 8; index++) await page.mouse.click(box!.x + box!.width / 2, box!.y + box!.height / 2);
        await announcements.action('post', async () => gate.release());
        await expect(announcements.form).not.toBeVisible();
        expect(gate.count()).toBe(1);
        expect(db.snapshot().announcements.filter(row => row.title === 'Rapid click notice')).toHaveLength(1);
    } finally {
        await gate.dispose();
    }
});

test('rapid ticket clicks remain busy and create exactly one ticket', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    await tickets.compose('Repeated clicks must create exactly one support ticket.');
    const gate = await holdAction(page, 'createTicket');
    try {
        await tickets.submit.click();
        await gate.held;
        const box = await tickets.submit.boundingBox();
        for (let index = 0; index < 8; index++) await page.mouse.click(box!.x + box!.width / 2, box!.y + box!.height / 2);
        await expect.soft(tickets.submit, 'Submit should disable while a request is pending').toBeDisabled();
        await tickets.action('createTicket', async () => gate.release());
        await expect(tickets.form).not.toBeVisible();
        expect(gate.count(), 'Duplicate createTicket requests').toBe(1);
        expect(db.snapshot().tickets.filter(row => row.user_id === db.ids.tenantId)).toHaveLength(1);
    } finally {
        await gate.dispose();
    }
});

test('refresh during an unsent submission creates no phantom ticket and allows retry', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    const issue = 'Refresh interruption should allow a safe manual retry.';
    await tickets.compose(issue);
    const gate = await holdAction(page, 'createTicket');
    try {
        await tickets.submit.click();
        await gate.held;
        expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(0);
        expect((await page.reload())?.status()).toBe(200);
        await expect(page).toHaveURL(/\/user\/tickets$/);
        await expect(page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
    } finally {
        await gate.dispose();
    }
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(0);
    await tickets.create(issue);
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(1);
});

test('refresh after backend commit but before its response preserves one announcement', async ({ auth, announcements, page, db }) => {
    await auth.login('admin');
    await announcements.open();
    const title = 'Committed before refresh';
    await announcements.fill(title, 'The write is complete while its response is delayed.');
    let release!: () => void;
    let committed!: () => void;
    const gate = new Promise<void>(resolve => { release = resolve; });
    const written = new Promise<void>(resolve => { committed = resolve; });
    await page.route('**/livewire/update', async route => {
        const { callsAction } = await import('../pages/base.page');
        if (!callsAction(route.request(), 'post')) return route.continue();
        const response = await route.fetch();
        expect(response.status()).toBe(200);
        committed();
        await gate;
        await route.fulfill({ response }).catch(() => {});
    });
    try {
        await announcements.submit.click();
        await written;
        expect(db.snapshot().announcements.filter(row => row.title === title)).toHaveLength(1);
        expect((await page.reload())?.status()).toBe(200);
        await expect(page.getByText(title, { exact: true })).toBeVisible();
        expect(db.snapshot().announcements.filter(row => row.title === title)).toHaveLength(1);
    } finally {
        release();
        await page.unroute('**/livewire/update');
    }
});

test('back and forward navigation keep the portal usable without replaying a write', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    await tickets.create('Navigation must not repeat this submitted ticket.');
    await tickets.goto('/user/leases');
    await page.goBack();
    await expect(page).toHaveURL(/\/user\/tickets$/);
    await expect(page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
    await page.goForward();
    await expect(page).toHaveURL(/\/user\/leases$/);
    await expect(page.locator('h1')).toBeVisible();
    await page.goBack();
    await expect(page).toHaveURL(/\/user\/tickets$/);
    await tickets.compose('A fresh form remains usable after navigation.');
    await expect(tickets.issue).toHaveValue('A fresh form remains usable after navigation.');
    expect(db.snapshot().tickets.filter(row => row.user_id === db.ids.tenantId)).toHaveLength(1);
});

test('two browser tabs submit distinct tickets without losing either write', async ({ auth, tickets, context, db }) => {
    await auth.login();
    await tickets.open();
    const second = new TicketsPage(await context.newPage());
    await second.open();
    await tickets.compose('First concurrent tab reports a water problem.');
    await second.compose('Second concurrent tab reports an electrical problem.');
    await Promise.all([
        tickets.action('createTicket', () => tickets.submit.click()),
        second.action('createTicket', () => second.submit.click()),
    ]);
    await expect(tickets.replyForm).toBeVisible();
    await expect(second.replyForm).toBeVisible();
    const rows = db.snapshot().tickets.filter(row => row.user_id === db.ids.tenantId);
    expect(rows).toHaveLength(2);
    expect(new Set(rows.map(row => row.issue)).size).toBe(2);
    for (const ticket of rows) expect(db.snapshot().ticket_messages.filter(row => row.ticket_id === ticket.id)).toHaveLength(1);
});

test('two tabs assigning the same stall persist one lease and one bill', async ({ auth, leases, context, db }) => {
    await auth.login('admin');
    await leases.openAssignment(db.ids.tenantId, db.ids.availableStallId);
    const second = new LeasesPage(await context.newPage());
    await second.openAssignment(2, db.ids.availableStallId);
    await Promise.all([
        leases.action('saveMapping', () => leases.form.locator('button[type="submit"]').click()),
        second.action('saveMapping', () => second.form.locator('button[type="submit"]').click()),
    ]);
    await expect.poll(async () => Number(await leases.form.isVisible()) + Number(await second.form.isVisible())).toBe(1);
    const rejected = await leases.form.isVisible() ? leases : second;
    await expect(rejected.form.locator('.text-error')).toContainText(/already has an active or reserved/i);
    const state = db.snapshot();
    expect(state.stall_mappings.filter(row => row.stall_id === db.ids.availableStallId)).toHaveLength(1);
    expect(state.invoices.filter(row => row.stall_id === db.ids.availableStallId)).toHaveLength(1);
    expect(state.stalls.find(row => row.id === db.ids.availableStallId)?.status).toBe('reserved');
});
