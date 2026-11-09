import { test, expect } from '../fixtures';
import { PortalPage } from '../pages/portal.page';
import { database } from '../support/database.mjs';

const adminPages = [
    ['dashboard', 'Analytics & Insights'], ['stalls', 'Stalls & Spaces'], ['floors', 'Floors & Locations'],
    ['grid-editor', 'Floor Map & Space Planner'], ['applications', 'Stall Applications'],
    ['mappings', 'Commercial Lease Contracts'], ['payments', 'Payments & Invoices'],
    ['tickets', 'Support Tickets'], ['calendar', 'Calendar'], ['tenants', 'Tenants'],
    ['announcements', 'Announcements'], ['reports', 'Financial Reports'], ['audit-logs', 'Audit Logs'],
    ['error-logs', 'Error Logs'], ['settings', 'System Settings'],
];

test('all admin pages render and every sidebar destination navigates successfully', async ({ auth, page }, info) => {
    test.setTimeout(180000);
    await auth.login('admin');
    const portal = new PortalPage(page);
    const failures: string[] = [];
    page.on('response', response => { if (response.status() >= 500) failures.push(`${response.status()} ${response.url()}`); });
    for (const [path, heading] of adminPages) await portal.checkPage(`/admin/${path}`, heading);
    await portal.goto('/admin/dashboard');
    const destinations = await page.locator('#app-sidebar nav a').evaluateAll(links => links.map(link => (link as HTMLAnchorElement).pathname));
    for (const path of destinations) {
        const menu = page.getByRole('button', { name: 'Menu', exact: true });
        if (await menu.isVisible() && await menu.getAttribute('aria-expanded') === 'false') await menu.click();
        await page.locator(`#app-sidebar nav a[href$="${path}"]`).click();
        await expect(page).toHaveURL(new RegExp(`${path}$`));
        await expect(page.locator('main h1').first()).toBeVisible();
    }
    await portal.goto('/admin/analytics');
    await expect(page).toHaveURL(/\/admin\/dashboard$/);
    expect(failures).toEqual([]);
    await info.attach('admin-page-inventory', { body: JSON.stringify({ pages: adminPages, sidebar: destinations }), contentType: 'application/json' });
});

test('all tenant pages, application status, public pages and history navigation render', async ({ auth, page }) => {
    test.setTimeout(120000);
    const portal = new PortalPage(page);
    for (const [path, heading] of [['/stalls', 'Browse Available Stalls'], ['/privacy-policy', 'Privacy Policy'], ['/terms-and-conditions', 'Terms and Conditions']]) await portal.checkPage(path, heading);
    await auth.login();
    for (const path of ['/user/dashboard', '/user/leases', '/user/payments', '/user/tickets', '/user/documents', '/application-status']) await portal.checkPage(path);
    await portal.goto('/user/dashboard');
    await portal.goto('/user/leases');
    await page.goBack();
    await expect(page).toHaveURL(/\/user\/dashboard$/);
    await page.goForward();
    await expect(page).toHaveURL(/\/user\/leases$/);
    expect((await page.reload())?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: 'My Leases', exact: true })).toBeVisible();
});

test('floor and stall create, manage, edit and cancel dialogs preserve database state', async ({ auth, page, db }) => {
    test.setTimeout(90000);
    await auth.login('admin');
    const portal = new PortalPage(page);
    await portal.goto('/admin/floors');
    await portal.openModal('openModal', () => page.locator('main [wire\\:click="openModal"]').click(), 'Add New Floor Level');
    await portal.dialog.locator('input[wire\\:model="name"]').fill('Browser QA Floor');
    await portal.action('saveFloor', () => portal.dialog.getByRole('button', { name: 'Save Floor Level', exact: true }).click());
    await expect(portal.dialog).toHaveCount(0);
    const row = page.getByRole('row').filter({ hasText: 'Browser QA Floor' });
    await portal.openModal('openDetailModal', () => row.getByRole('button', { name: 'Manage', exact: true }).click(), 'Browser QA Floor');
    await portal.action('openModal', () => portal.dialog.getByRole('button', { name: /Edit/ }).click());
    await expect(portal.dialog.getByRole('heading', { name: 'Edit Floor Level' })).toBeVisible();
    await portal.closeModal('closeModal');

    await portal.goto('/admin/stalls');
    await portal.openModal('openModal', () => page.locator('main [wire\\:click="openModal"]').first().click(), 'Add Stall');
    await portal.dialog.locator('input[wire\\:model="stall_number"]').fill('QA-NEW');
    await portal.dialog.locator('select[wire\\:model="location_id"]').selectOption({ label: 'Browser QA Floor' });
    await portal.dialog.locator('input[wire\\:model="price"]').fill('2500');
    await portal.action('saveStall', () => portal.dialog.getByRole('button', { name: 'Save Stall', exact: true }).click());
    await expect(portal.dialog).toHaveCount(0);
    expect(db.snapshot().stalls.filter(stall => stall.stall_number === 'QA-NEW')).toHaveLength(1);
    const stallRow = page.getByRole('row').filter({ hasText: 'QA-NEW' });
    await portal.openModal('openDetailModal', () => stallRow.getByRole('button', { name: 'Manage', exact: true }).click(), 'Stall QA-NEW');
    await portal.action('openModal', () => portal.dialog.getByRole('button', { name: /Edit/ }).click());
    await portal.dialog.locator('input[wire\\:model="price"]').fill('9999');
    await portal.closeModal('closeModal');
    expect(Number(db.snapshot().stalls.find(stall => stall.stall_number === 'QA-NEW')?.price)).toBe(2500);
});

test('calendar controls and financial report downloads work without navigation errors', async ({ auth, page }, info) => {
    test.setTimeout(90000);
    await auth.login('admin');
    const portal = new PortalPage(page);
    await portal.goto('/admin/calendar');
    const month = page.locator('main .min-w-36');
    const initial = await month.innerText();
    await portal.action('nextMonth', () => page.getByRole('button', { name: 'Next month', exact: true }).click());
    await expect(month).not.toHaveText(initial);
    await portal.action('previousMonth', () => page.getByRole('button', { name: 'Previous month', exact: true }).click());
    await expect(month).toHaveText(initial);
    await portal.action('thisMonth', () => page.getByRole('button', { name: 'Today', exact: true }).click());
    await portal.goto('/admin/reports');
    for (const [action, label, extension] of [['exportXlsx', 'Export XLSX', '.xlsx'], ['exportPdf', 'Export PDF', '.pdf']]) {
        const downloadPromise = page.waitForEvent('download');
        await portal.action(action, () => page.getByRole('button', { name: label, exact: true }).click());
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toMatch(new RegExp(`\\${extension}$`));
        expect(await download.failure()).toBeNull();
        await download.saveAs(info.outputPath(download.suggestedFilename()));
    }
    await expect(page).toHaveURL(/\/admin\/reports$/);
});

test('tenant application submission is persisted and admin approval reaches the tenant', async ({ auth, page, db }) => {
    test.setTimeout(90000);
    await auth.login();
    const portal = new PortalPage(page);
    await portal.goto(`/stalls?stall=${db.ids.availableStallId}`);
    await portal.action('openApply', () => page.getByRole('button', { name: 'Apply for this stall', exact: true }).click());
    const form = portal.dialog.locator('form[wire\\:submit="submitApplication"]');
    await form.locator('#application-applicant_phone').fill('9123456789');
    await form.locator('input[wire\\:model="form.business_name"]').fill('Browser QA Shop');
    await form.locator('input[wire\\:model="form.business_type"]').fill('Retail');
    await portal.action('submitApplication', () => form.getByRole('button', { name: 'Submit application', exact: true }).click());
    await expect(portal.dialog).toContainText('Application submitted.');
    const reference = (await portal.dialog.innerText()).match(/APP-\d{4}-\d+/)![0];
    const application = db.snapshot().stall_applications.find(row => row.business_name === 'Browser QA Shop')!;
    expect(application).toMatchObject({ user_id: db.ids.tenantId, preferred_stall_id: db.ids.availableStallId, status: 'pending' });
    await portal.goto('/user/dashboard');
    await auth.logout();
    await auth.login('admin');
    await portal.goto('/admin/applications');
    await portal.action('openDetail', () => page.getByRole('row').filter({ hasText: 'Browser QA Shop' }).getByRole('button', { name: 'Review', exact: true }).click());
    page.once('dialog', dialog => dialog.accept());
    await portal.action('approve', () => portal.dialog.getByRole('button', { name: 'Approve', exact: true }).click());
    expect(db.snapshot().stall_applications.find(row => row.id === application.id)?.status).toBe('approved');
    expect(db.snapshot().notifications.some(row => row.user_id === db.ids.tenantId && row.title === 'Application approved')).toBe(true);
    await portal.goto('/admin/dashboard');
    await auth.logout();
    await auth.login();
    await portal.goto('/application-status');
    await page.locator('#statusReference').fill(reference);
    await page.locator('#statusEmail').fill('tenant@example.test');
    await portal.action('lookup', () => page.getByRole('button', { name: 'Check status', exact: true }).click());
    await expect(page.locator('main')).toContainText('Approved');
});

test('floor map placement and removal persist and survive refresh', async ({ auth, page, db }) => {
    await auth.login('admin');
    const portal = new PortalPage(page);
    await portal.goto('/admin/grid-editor');
    await portal.update('selectedStallId', () => page.getByRole('button', { name: /^Stall QA-002/ }).click());
    await portal.action('placeStallOnGrid', () => page.getByRole('button', { name: 'Row 1, column 1, place selected stall', exact: true }).click());
    expect(db.snapshot().stalls.find(row => row.id === db.ids.availableStallId)).toMatchObject({ grid_row: 1, grid_col: 1 });
    expect((await page.reload())?.status()).toBe(200);
    await expect(page.getByRole('button', { name: 'Row 1, column 1, Stall QA-002', exact: true })).toBeVisible();
    await portal.action('removeStallFromGrid', () => page.getByRole('button', { name: 'Remove Stall QA-002 from grid', exact: true }).click());
    expect(db.snapshot().stalls.find(row => row.id === db.ids.availableStallId)).toMatchObject({ grid_row: null, grid_col: null });
});

test('renewal request, approval, payment details, cash completion and receipt are consistent', async ({ auth, page, db }) => {
    test.setTimeout(120000);
    const fixture = database('renewal-fixture');
    const portal = new PortalPage(page);
    await auth.login();
    await portal.goto('/user/leases');
    await portal.action('openRenewal', () => page.getByRole('button', { name: 'Request renewal', exact: true }).click());
    await portal.action('submitRenewal', () => portal.dialog.getByRole('button', { name: 'Send request', exact: true }).click());
    expect(db.snapshot().stall_mappings.find(row => row.id === fixture.leaseId)).toMatchObject({ renewal_status: 'requested', end_date: fixture.endDate });
    await auth.logout();
    await auth.login('admin');
    await portal.goto('/admin/mappings');
    await portal.action('openDetailModal', () => page.getByRole('row').filter({ hasText: 'QA-001' }).getByRole('button', { name: 'Manage', exact: true }).click());
    await portal.action('approveRenewal', () => portal.dialog.getByRole('button', { name: 'Confirm term and invoice', exact: true }).click());
    const pending = db.snapshot();
    const lease = pending.stall_mappings.find(row => row.id === fixture.leaseId)!;
    expect(lease).toMatchObject({ renewal_status: 'awaiting_payment', end_date: fixture.endDate });
    const invoice = pending.invoices.find(row => row.id === lease.renewal_invoice_id)!;
    expect(invoice).toMatchObject({ status: 'pending', is_renewal: 1 });
    await portal.goto('/admin/dashboard');
    await auth.logout();
    await auth.login();
    await portal.goto('/user/leases');
    await expect(page.getByRole('link', { name: 'Pay renewal invoice', exact: true })).toHaveCount(0);
    await portal.action('openDetailModal', () => page.getByRole('button', { name: 'View Details', exact: true }).click());
    await expect(portal.dialog.getByRole('link', { name: 'Pay renewal invoice', exact: true })).toBeVisible();
    await portal.dialog.getByRole('link', { name: 'Pay renewal invoice', exact: true }).click();
    await expect(page).toHaveURL(/\/user\/payments$/);
    await expect(page.locator('main')).toContainText(invoice.invoice_number);
    await auth.logout();
    await auth.login('admin');
    await portal.goto('/admin/mappings');
    await portal.action('openDetailModal', () => page.getByRole('row').filter({ hasText: 'QA-001' }).getByRole('button', { name: 'Manage', exact: true }).click());
    page.once('dialog', dialog => dialog.accept());
    await portal.action('recordRenewalCashPayment', () => portal.dialog.getByRole('button', { name: 'Record full renewal cash payment', exact: true }).click());
    const paid = db.snapshot();
    expect(paid.invoices.find(row => row.id === invoice.id)?.status).toBe('paid');
    expect(paid.stall_mappings.find(row => row.id === lease.id)).toMatchObject({ renewal_status: 'approved', end_date: invoice.rent_period_end });
    expect(paid.payment_transactions.filter(row => row.invoice_id === invoice.id && row.status === 'successful')).toHaveLength(1);
    await portal.goto('/admin/dashboard');
    await auth.logout();
    await auth.login();
    await portal.goto('/user/payments');
    const invoiceView = page.locator('[data-mobile-invoices] article, table tr')
        .filter({ hasText: invoice.invoice_number }).filter({ visible: true });
    await portal.action('openDetailModal', () => invoiceView.getByRole('button', { name: /^Manage/ }).click());
    const receipt = portal.dialog.getByRole('link', { name: 'Download Receipt PDF', exact: true });
    await expect(receipt).toBeVisible();
    const response = await page.request.get((await receipt.getAttribute('href'))!);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/pdf');
    expect((await response.body()).subarray(0, 5).toString()).toBe('%PDF-');
});
