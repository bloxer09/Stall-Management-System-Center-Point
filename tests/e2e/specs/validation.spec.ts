import { test, expect } from '../fixtures';
import { password } from '../pages/auth.page';

test('empty login inputs are rejected by browser validation without a request', async ({ auth, page }) => {
    await auth.open();
    let submitted = false;
    page.on('request', request => { if (request.method() === 'POST' && request.url().endsWith('/livewire/update')) submitted = true; });
    await auth.loginForm.locator('button[type="submit"]').click();
    expect(await page.locator('#field-loginEmail').evaluate((input: HTMLInputElement) => input.validity.valueMissing)).toBe(true);
    await expect(page.locator('#field-loginEmail')).toBeFocused();
    await expect(page).toHaveURL(/\?login=1$/);
    expect(submitted).toBe(false);
});

test('server also rejects empty login fields when browser validation is bypassed', async ({ auth, page }) => {
    await auth.open();
    await auth.serverSubmit(auth.loginForm, 'processLogin');
    await expect(page.locator('#field-loginEmail-error')).toContainText(/required/i);
    await expect(page.locator('#field-loginPassword-error')).toContainText(/required/i);
    await expect(page).toHaveURL(/\?login=1$/);
});

for (const email of ['not-an-email', 'a@@example.test', '<script>alert(1)</script>']) {
    test(`login rejects malformed email ${email.slice(0, 35)}`, async ({ auth, page }) => {
        await auth.open();
        await page.locator('#field-loginEmail').fill(email);
        await page.locator('#field-loginPassword').fill(password);
        await auth.serverSubmit(auth.loginForm, 'processLogin');
        await expect(page.locator('#field-loginEmail-error')).toContainText(/valid email/i);
        await expect(auth.loginForm).toBeVisible();
        await expect(page).toHaveURL(/\?login=1$/);
    });
}

test('wrong credentials show a clear error and keep restricted routes guarded', async ({ auth, page }) => {
    await auth.open();
    await page.locator('#field-loginEmail').fill('tenant@example.test');
    await page.locator('#field-loginPassword').fill('incorrect-password');
    await auth.serverSubmit(auth.loginForm, 'processLogin');
    await expect(page.getByRole('alert')).toContainText('credentials do not match');
    await page.goto('/user/dashboard');
    await expect(page).toHaveURL(/\?login=1$/);
});

test('empty registration fields produce server errors without adding a user', async ({ auth, page, db }) => {
    await auth.open('register');
    await auth.serverSubmit(auth.registerForm, 'processRegister');
    for (const field of ['regName', 'regEmail', 'regPhone', 'regPassword']) {
        await expect(page.locator(`#field-${field}-error`)).toContainText(/required/i);
    }
    await expect(page.locator('#terms-error')).toContainText(/accepted/i);
    expect(db.snapshot().users).toHaveLength(4);
});

for (const entry of [
    { name: 'name exceeds 100', field: 'regName', value: 'N'.repeat(101), error: /100/ },
    { name: 'email exceeds 120', field: 'regEmail', value: `${'a'.repeat(60)}@${'b'.repeat(48)}.example.test`, error: /120/ },
    { name: 'phone exceeds 30', field: 'regPhone', value: '9'.repeat(31), error: /30/ },
    { name: 'duplicate email', field: 'regEmail', value: 'tenant@example.test', error: /already been taken/i },
    { name: 'invalid email', field: 'regEmail', value: 'name@@example.test', error: /valid email/i },
    { name: 'mismatched passwords', field: 'regPasswordConfirmation', value: 'Different!2026', error: /match/i },
]) {
    test(`registration rejects ${entry.name}`, async ({ auth, page, db }) => {
        await auth.open('register');
        await auth.fillRegistration({ [entry.field]: entry.value });
        await auth.serverSubmit(auth.registerForm, 'processRegister');
        const errorField = entry.field === 'regPasswordConfirmation' ? 'regPassword' : entry.field;
        await expect(page.locator(`#field-${errorField}-error`)).toContainText(entry.error);
        await expect(page).toHaveURL(/\?register=1$/);
        expect(db.snapshot().users).toHaveLength(4);
    });
}

test('registration accepts the 100-character name boundary', async ({ auth, page, db }) => {
    await auth.open('register');
    await auth.fillRegistration({ regName: 'N'.repeat(100) });
    await auth.serverSubmit(auth.registerForm, 'processRegister');
    await expect(page).toHaveURL(/\/user\/dashboard$/);
    expect(db.snapshot().users.find(user => user.email === 'applicant@example.test')?.name).toHaveLength(100);
});

for (const issue of ['', 'abcd']) {
    test(`ticket rejects ${issue ? 'less than five characters' : 'empty description'}`, async ({ auth, tickets, db }) => {
        await auth.login();
        await tickets.open();
        await tickets.compose(issue);
        await tickets.serverSubmit(tickets.form, 'createTicket');
        await expect(tickets.form.locator('.text-error')).toContainText(issue ? /at least 5/i : /required/i);
        await expect(tickets.form).toBeVisible();
        expect(db.snapshot().tickets).toHaveLength(1);
    });
}

test('ticket renders special characters as text without executing script', async ({ auth, tickets, page, db }) => {
    await auth.login();
    await tickets.open();
    const dialogs: string[] = [];
    page.on('dialog', dialog => { dialogs.push(dialog.message()); void dialog.dismiss(); });
    const issue = `<script>alert('qa-xss')</script> O'Brien & <img src=x onerror=alert(1)>`;
    await tickets.create(issue);
    await expect(page.getByText(issue, { exact: true }).last()).toBeVisible();
    expect(db.snapshot().tickets.filter(ticket => ticket.issue === issue)).toHaveLength(1);
    expect(dialogs).toEqual([]);
    expect(await page.locator('script').evaluateAll(scripts => scripts.some(script => script.textContent === "alert('qa-xss')"))).toBe(false);
});

test('announcement accepts maximum lengths and caps additional browser input', async ({ auth, announcements, page, db }) => {
    await auth.login('admin');
    await announcements.open();
    await announcements.fill('T'.repeat(150), 'B'.repeat(2000));
    await page.getByLabel('Title', { exact: true }).press('End');
    await page.getByLabel('Title', { exact: true }).pressSequentially('X');
    await page.getByLabel('Message', { exact: true }).press('End');
    await page.getByLabel('Message', { exact: true }).pressSequentially('X');
    await expect(page.getByLabel('Title', { exact: true })).toHaveValue('T'.repeat(150));
    await expect(page.getByLabel('Message', { exact: true })).toHaveValue('B'.repeat(2000));
    await announcements.action('post', () => announcements.submit.click());
    expect(db.snapshot().announcements[0]).toMatchObject({ title: 'T'.repeat(150), body: 'B'.repeat(2000) });
});

test('announcement rejects empty fields', async ({ auth, announcements, db }) => {
    await auth.login('admin');
    await announcements.open();
    await announcements.serverSubmit(announcements.form, 'post');
    await expect(announcements.form.locator('.text-error')).toHaveCount(2);
    await expect(announcements.form.locator('.text-error').first()).toContainText(/required/i);
    expect(db.snapshot().announcements).toHaveLength(0);
});

for (const entry of [
    { field: 'Title', length: 151, limit: 150 },
    { field: 'Message', length: 2001, limit: 2000 },
]) {
    test(`announcement server rejects ${entry.field.toLowerCase()} above ${entry.limit}`, async ({ auth, announcements, page, db }) => {
        await auth.login('admin');
        await announcements.open();
        await announcements.fill('Valid title', 'Valid announcement body.');
        const input = page.getByLabel(entry.field, { exact: true });
        await input.evaluate(element => element.removeAttribute('maxlength'));
        await input.fill('X'.repeat(entry.length));
        await announcements.serverSubmit(announcements.form, 'post');
        await expect(announcements.form.locator('.text-error')).toContainText(String(entry.limit));
        expect(db.snapshot().announcements).toHaveLength(0);
    });
}

for (const date of ['not-a-date', '2026-02-30', '31/12/2026']) {
    test(`lease rejects invalid date ${date}`, async ({ auth, leases, page, db }) => {
        await auth.login('admin');
        await leases.openAssignment(db.ids.tenantId, db.ids.availableStallId);
        // Date inputs cannot hold malformed values; change only the DOM type to exercise server validation.
        await page.locator('#assignment-start').evaluate((input: HTMLInputElement) => { input.type = 'text'; });
        await page.locator('#assignment-start').fill(date);
        await leases.serverSubmit(leases.form, 'saveMapping');
        await expect(leases.form.locator('.text-error').filter({ hasText: /valid date/i })).toBeVisible();
        expect(db.snapshot().stall_mappings).toHaveLength(1);
    });
}
