import { test, expect } from '../fixtures';
import { InvoicesPage } from '../pages/invoices.page';

test('pending invoice selection stays synchronized across bulk actions, themes, and desktop/mobile views', async ({ auth, leases, page, db }, info) => {
    test.setTimeout(90000);
    await auth.login('admin');
    await leases.openAssignment(db.ids.tenantId, db.ids.availableStallId);
    await leases.assign();
    await auth.logout();
    await auth.login();
    const invoices = new InvoicesPage(page);
    await invoices.goto('/user/payments');
    const before = db.snapshot();
    await expect(invoices.selection).not.toBeChecked();
    await expect(invoices.payment).toBeDisabled();

    for (const theme of ['light', 'dark']) {
        await page.locator('html').evaluate((html, theme) => html.setAttribute('data-theme', theme), theme);
        await invoices.toggleAll(true);
        await expect(invoices.payment).toHaveText(/Pay Selected \(₱1,800\.00\)/);
        await info.attach(`selected-${theme}`, { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
        await invoices.toggleAll(false);
        await invoices.selectInvoice(true);
        await expect(page.getByRole('button', { name: /^Deselect All Pending/ })).toBeVisible();
        await invoices.selectInvoice(false);
    }

    await invoices.toggleAll(true);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.keyboard.press('Escape');
    await expect(invoices.selection).toBeChecked();
    await info.attach('selected-mobile', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
    await invoices.selectInvoice(false);
    await invoices.toggleAll(true);
    await page.setViewportSize({ width: 1280, height: 720 });
    await expect(invoices.selection).toBeChecked();
    await invoices.toggleAll(false);
    await expect(page).toHaveURL(/\/user\/payments$/);
    expect(db.snapshot().invoices).toEqual(before.invoices);
    expect(db.snapshot().payment_transactions).toEqual(before.payment_transactions);
});
