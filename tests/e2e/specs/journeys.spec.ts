import { test, expect } from '../fixtures';

test('registration redirects to tenant dashboard and persists one account', async ({ auth, page, db }) => {
    await auth.open('register');
    await auth.fillRegistration();
    await auth.action('processRegister', () => auth.registerForm.getByRole('button', { name: 'Create Account' }).click());
    await expect(page).toHaveURL(/\/user\/dashboard$/);
    await expect(auth.registerForm).not.toBeVisible();
    const accounts = db.snapshot().users.filter(user => user.email === 'applicant@example.test');
    expect(accounts).toHaveLength(1);
    expect(accounts[0]).toMatchObject({ name: 'Defense Applicant', phone: '09123456789', role: 'user' });
    await auth.logout();
});

test('tenant signs in, creates a ticket, replies, refreshes, and signs out', async ({ auth, tickets, page, db }) => {
    await auth.login();
    expect(db.snapshot().audit_logs.some(log => log.action === 'Security: login succeeded' && log.user_id === db.ids.tenantId)).toBe(true);
    await tickets.open();
    const issue = 'Water leak beside the entrance needs maintenance.';
    await tickets.create(issue);
    const saved = db.snapshot().tickets.filter(ticket => ticket.issue === issue);
    expect(saved).toHaveLength(1);
    expect(saved[0]).toMatchObject({ user_id: db.ids.tenantId, stall_id: 1, status: 'open' });
    expect(db.snapshot().ticket_messages.filter(message => message.ticket_id === saved[0].id)).toHaveLength(1);
    expect(db.snapshot().notifications.some(notification => notification.user_id === db.ids.adminId && notification.type === 'ticket')).toBe(true);
    const reply = 'The maintenance team can visit after 10 AM.';
    await tickets.reply(reply);
    expect(db.snapshot().ticket_messages.filter(message => message.ticket_id === saved[0].id && message.message === reply)).toHaveLength(1);
    await tickets.goto(`/user/tickets?ticket=${saved[0].id}`);
    expect((await page.reload())?.status()).toBe(200);
    await expect(page.getByText(reply, { exact: true })).toBeVisible();
    await auth.logout();
});

test('admin posts an announcement and its tenant notification is persisted', async ({ auth, announcements, page, db }) => {
    await auth.login('admin');
    await announcements.open();
    const title = 'Scheduled water inspection';
    const body = 'Inspection starts at 10 AM. Please keep the entrance clear.';
    await announcements.post(title, body);
    const state = db.snapshot();
    expect(state.announcements).toHaveLength(1);
    expect(state.announcements[0]).toMatchObject({ title, body, posted_by: db.ids.adminId, emailed: 0, sms_requested: 0 });
    expect(state.notifications.filter(notification => notification.type === 'announcement')).toEqual([
        expect.objectContaining({ user_id: db.ids.tenantId, title }),
    ]);
    expect(state.audit_logs.some(log => log.entity_type === 'Announcement')).toBe(true);
    expect((await page.reload())?.status()).toBe(200);
    await expect(page.getByText(title, { exact: true })).toBeVisible();
    await auth.logout();
    await auth.login();
    await expect(page.locator('main').getByText(title, { exact: true })).toBeVisible();
});

test('admin assigns a stall, generates rent, records cash, and tenant sees the active lease', async ({ auth, leases, page, db }) => {
    test.setTimeout(60000);
    await auth.login('admin');
    await leases.openAssignment(db.ids.tenantId, db.ids.availableStallId);
    await leases.assign();
    const pending = db.snapshot();
    const lease = pending.stall_mappings.find(row => row.stall_id === db.ids.availableStallId)!;
    expect(lease).toMatchObject({ user_id: db.ids.tenantId, is_active: 0, payment_status: 'pending_initial', rent_billing_mode: 'upfront' });
    expect(pending.stalls.find(row => row.id === db.ids.availableStallId)?.status).toBe('reserved');
    const invoice = pending.invoices.find(row => row.id === lease.initial_invoice_id)!;
    expect(invoice).toMatchObject({ user_id: db.ids.tenantId, stall_id: db.ids.availableStallId, status: 'pending' });
    expect(Number(invoice.total_amount)).toBe(1800);
    expect(pending.invoice_items.filter(row => row.invoice_id === invoice.id)).toHaveLength(1);
    await leases.recordCashPayment(lease.id);
    const paid = db.snapshot();
    expect(paid.stall_mappings.find(row => row.id === lease.id)).toMatchObject({ is_active: 1, payment_status: 'good_standing' });
    expect(paid.stalls.find(row => row.id === db.ids.availableStallId)?.status).toBe('occupied');
    expect(paid.invoices.find(row => row.id === invoice.id)).toMatchObject({ status: 'paid', paid_at: expect.any(String) });
    expect(paid.payment_transactions.filter(row => row.invoice_id === invoice.id)).toEqual([
        expect.objectContaining({ gateway: 'office_cash', status: 'successful' }),
    ]);
    await auth.logout();
    await auth.login();
    await leases.goto('/user/leases');
    await expect(page).toHaveURL(/\/user\/leases$/);
    await expect(page.getByText(/QA-002/).first()).toBeVisible();
    expect((await page.reload())?.status()).toBe(200);
    await expect(page.getByText(/QA-002/).first()).toBeVisible();
});
