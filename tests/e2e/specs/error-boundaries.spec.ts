import { test, expect } from '../fixtures';
import { callsAction } from '../pages/base.page';
import { AuthPage } from '../pages/auth.page';
import { RequestErrorPage } from '../pages/request-error.page';

const messages: Record<number, RegExp> = {
    400: /request could not be accepted/i,
    401: /session needs to be renewed/i,
    404: /resource could not be found/i,
    419: /session needs to be renewed/i,
    500: /server could not complete/i,
};

for (const status of [400, 401, 404, 419, 500]) {
    test(`ticket submission handles HTTP ${status} with a clear alert and recovery`, async ({ auth, tickets, page, db }) => {
        await auth.login();
        await tickets.open();
        const errors = new RequestErrorPage(page);
        const issue = `Recoverable ticket submission after HTTP ${status}.`;
        await tickets.compose(issue);
        await page.route('**/livewire/update', route => {
            if (!callsAction(route.request(), 'createTicket')) return route.continue();
            return route.fulfill({ status, contentType: 'application/json', json: { message: `QA simulated HTTP ${status}: unable to submit your request.` } });
        });
        await tickets.action('createTicket', () => tickets.submit.click(), status);
        await expect(page).toHaveURL(/\/user\/tickets$/);
        await expect(page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
        await expect(tickets.form).toBeVisible();
        await expect(tickets.issue).toHaveValue(issue);
        expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(0);
        await errors.visible(messages[status], tickets.form);
        if ([401, 419].includes(status)) {
            await expect(errors.signIn).toHaveAttribute('href', '/login');
            await expect(errors.signIn).toHaveAttribute('target', '_blank');
            await expect(errors.alert).toContainText('copy your unfinished input before refreshing');
        }
        await test.info().attach(`http-${status}-error-state`, { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
        await expect(tickets.submit).toBeEnabled();
        await page.unroute('**/livewire/update');
        await tickets.action('createTicket', () => tickets.submit.click());
        await expect(tickets.replyForm).toBeVisible();
        await expect(errors.alert).toHaveCount(0);
        await expect(tickets.form).not.toBeVisible();
        expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(1);
    });
}

test('connection failure shows a recoverable error instead of an empty page', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    const errors = new RequestErrorPage(page);
    const issue = 'Network interruption preserves this ticket draft.';
    await tickets.compose(issue);
    await page.route('**/livewire/update', route => callsAction(route.request(), 'createTicket') ? route.abort('failed') : route.continue());
    const failed = page.waitForEvent('requestfailed', request => callsAction(request, 'createTicket'));
    await tickets.submit.click();
    expect((await failed).failure()?.errorText).toBeTruthy();
    await expect(page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
    await expect(tickets.issue).toHaveValue(issue);
    await test.info().attach('connection-error-state', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
    await errors.visible(/connection failed/i, tickets.form);
    await expect(page).toHaveURL(/\/user\/tickets$/);
    await expect(errors.alert).toContainText('whether your submission completed');
    await expect(tickets.submit).toBeEnabled();
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(0);
    await page.unroute('**/livewire/update');
    await tickets.action('createTicket', () => tickets.submit.click());
    await expect(tickets.replyForm).toBeVisible();
    await expect(errors.alert).toHaveCount(0);
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(1);
});

test('expired sessions offer a separate sign-in tab and retain the original draft', async ({ auth, tickets, page, context, db }) => {
    test.setTimeout(90000);
    await auth.login();
    await tickets.open();
    const errors = new RequestErrorPage(page);
    const issue = 'Keep this draft while renewing an expired session.';
    await tickets.compose(issue);
    await context.clearCookies();
    await tickets.action('createTicket', () => tickets.submit.click(), 419);
    await errors.visible(/session needs to be renewed/i, tickets.form);
    const opened = context.waitForEvent('page');
    await errors.signIn.click();
    const signInPage = await opened;
    await expect(signInPage).toHaveURL(/\?login=1$/);
    const separateAuth = new AuthPage(signInPage);
    await expect(separateAuth.loginForm).toBeVisible();
    await separateAuth.login();
    await expect(page).toHaveURL(/\/user\/tickets$/);
    await expect(tickets.issue).toHaveValue(issue);
    await tickets.action('createTicket', () => tickets.submit.click(), 419);
    await expect(errors.alert).toHaveCount(1);
    await expect(errors.alert).toContainText('copy your unfinished input before refreshing');
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(0);
    expect((await page.reload())?.status()).toBe(200);
    await tickets.create(issue);
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(1);
});

test('guest login errors persist in the dialog and can be dismissed with the keyboard', async ({ auth, page }) => {
    await auth.open();
    await page.setViewportSize({ width: 390, height: 844 });
    const errors = new RequestErrorPage(page);
    await page.locator('#field-loginEmail').fill('tenant@example.test');
    await page.locator('#field-loginPassword').fill('Defense!2026');
    await page.route('**/livewire/update', route => callsAction(route.request(), 'processLogin')
        ? route.fulfill({ status: 500, contentType: 'text/html', body: '<h1>QA simulated private server details</h1>' }) : route.continue());
    await auth.action('processLogin', () => auth.loginForm.locator('button[type="submit"]').click(), 500);
    await errors.visible(/server could not complete/i, auth.loginForm);
    await expect(page).toHaveURL(/\?login=1$/);
    await expect(page.locator('#field-loginEmail')).toHaveValue('tenant@example.test');
    await expect(page.locator('#field-loginPassword')).toHaveValue('Defense!2026');
    await expect(auth.loginForm.locator('button[type="submit"]')).toBeEnabled();
    // Existing transient notifications disappear after 4.2 seconds; request errors must remain.
    await page.waitForTimeout(4500);
    await expect(errors.alert).toBeVisible();
    await test.info().attach('mobile-guest-error-state', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
    await errors.dismiss.focus();
    await expect(errors.dismiss).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(errors.alert).toHaveCount(0);
    await expect(auth.loginForm.locator('button[type="submit"]')).toBeFocused();
    await expect(page.locator('#field-loginPassword')).toHaveValue('Defense!2026');
    await page.unroute('**/livewire/update');
    await auth.action('processLogin', () => auth.loginForm.locator('button[type="submit"]').click());
    await expect(page).toHaveURL(/\/user\/dashboard$/);
    await expect(auth.loginForm).not.toBeVisible();
});

test('Livewire navigation and browser history do not duplicate request alerts', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    const errors = new RequestErrorPage(page);
    await page.route('**/livewire/update', route => callsAction(route.request(), 'createTicket')
        ? route.fulfill({ status: 500, contentType: 'application/json', json: { message: 'QA simulated failure' } }) : route.continue());
    await tickets.compose('An alert before navigating away.');
    await tickets.action('createTicket', () => tickets.submit.click(), 500);
    await errors.visible(/server could not complete/i, tickets.form);
    await page.evaluate(() => (window as any).Livewire.navigate('/user/leases'));
    await expect(page).toHaveURL(/\/user\/leases$/);
    await expect(page.locator('h1')).toBeVisible();
    await expect(errors.alert).toHaveCount(0);
    await page.goBack();
    await expect(page).toHaveURL(/\/user\/tickets$/);
    await expect(page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
    await page.goForward();
    await expect(page).toHaveURL(/\/user\/leases$/);
    await page.goBack();
    await expect(page).toHaveURL(/\/user\/tickets$/);
    const issue = 'One request and one alert after browser history navigation.';
    // Livewire history restores the dialog and unfinished form from its cached page.
    await expect(tickets.form).toBeVisible();
    await tickets.issue.fill(issue);
    for (let attempt = 0; attempt < 2; attempt++) {
        await tickets.action('createTicket', () => tickets.submit.click(), 500);
        await errors.visible(/server could not complete/i, tickets.form);
        await expect(tickets.issue).toHaveValue(issue);
    }
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(0);
    await page.unroute('**/livewire/update');
    await tickets.action('createTicket', () => tickets.submit.click());
    await expect(tickets.replyForm).toBeVisible();
    await expect(errors.alert).toHaveCount(0);
    expect(db.snapshot().tickets.filter(row => row.issue === issue)).toHaveLength(1);
});

test('session alerts fit a narrow dark-theme dialog and keep recovery controls reachable', async ({ auth, tickets, page }) => {
    await auth.login();
    await page.setViewportSize({ width: 375, height: 812 });
    await tickets.open();
    await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));
    const errors = new RequestErrorPage(page);
    await tickets.compose('A mobile draft survives an expired session response.');
    await page.route('**/livewire/update', route => callsAction(route.request(), 'createTicket')
        ? route.fulfill({ status: 401, contentType: 'application/json', json: { message: 'QA simulated session failure' } }) : route.continue());
    await tickets.action('createTicket', () => tickets.submit.click(), 401);
    await errors.visible(/session needs to be renewed/i, tickets.form);
    await errors.signIn.scrollIntoViewIfNeeded();
    await expect(errors.signIn).toBeInViewport();
    const bounds = await errors.alert.boundingBox();
    expect(bounds?.x).toBeGreaterThanOrEqual(0);
    expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(375);
    expect(await errors.signIn.evaluate(element => element.getBoundingClientRect().height)).toBeGreaterThanOrEqual(44);
    await test.info().attach('mobile-dark-session-error-state', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
    await errors.dismiss.scrollIntoViewIfNeeded();
    await errors.dismiss.click();
    await expect(errors.alert).toHaveCount(0);
    await expect(tickets.issue).toHaveValue('A mobile draft survives an expired session response.');
});

test('a failed action outside a dialog shows a page alert and can recover', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    const errors = new RequestErrorPage(page);
    const initialTickets = db.snapshot().tickets.length;
    await page.route('**/livewire/update', route => callsAction(route.request(), 'openCreateModal')
        ? route.fulfill({ status: 500, contentType: 'application/json', json: { message: 'QA simulated failure' } }) : route.continue());
    await tickets.action('openCreateModal', () => page.getByRole('button', { name: 'New Ticket' }).click(), 500);
    await expect(errors.alert).toHaveCount(1);
    await expect(errors.alert).toBeVisible();
    await expect(errors.alert).toHaveAttribute('role', 'alert');
    await expect(errors.alert).toContainText(/server could not complete/i);
    expect(await errors.alert.evaluate(element => element.parentElement === document.querySelector('main'))).toBe(true);
    await expect(page.locator('#livewire-error')).toHaveCount(0);
    await expect(page).toHaveURL(/\/user\/tickets$/);
    await expect(page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
    await expect(tickets.form).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'New Ticket' })).toBeEnabled();
    expect(db.snapshot().tickets.length).toBe(initialTickets);
    await test.info().attach('page-request-error-state', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
    await page.unroute('**/livewire/update');
    await tickets.compose('A usable form after recovering from a page-level error.');
    await expect(errors.alert).toHaveCount(0);
    expect(db.snapshot().tickets.length).toBe(initialTickets);
});
