import { test, expect } from '../fixtures';

test('profile phone validation rejects invalid input and preserves the saved number', async ({ auth, page, db }) => {
    await auth.login();
    await auth.goto('/user/dashboard?profile=1');
    const dialog = page.getByRole('dialog', { name: 'Account Profile' });
    const input = dialog.locator('#field-phone');
    const saved = db.snapshot().users.find(user => user.id === db.ids.tenantId)?.phone;
    await input.fill('917abc4567');
    expect(await input.evaluate((element: HTMLInputElement) => element.validity.patternMismatch)).toBe(true);
    const form = dialog.locator('form[wire\\:submit\\.prevent="updateProfile"]');
    await auth.serverSubmit(form, 'updateProfile');
    await expect(dialog.locator('#field-phone-error')).toContainText('valid Philippine mobile number');
    await expect(input).toHaveAttribute('aria-invalid', 'true');
    await expect(input).toHaveValue('917abc4567');
    expect(db.snapshot().users.find(user => user.id === db.ids.tenantId)?.phone).toBe(saved);
    await expect(dialog).toBeVisible();
});

test('registration phone validation rejects an incorrect mobile prefix without creating an account', async ({ auth, page, db }) => {
    await auth.open('register');
    await auth.fillRegistration({ regPhone: '8123456789' });
    const phone = page.locator('#field-regPhone');
    expect(await phone.evaluate((element: HTMLInputElement) => element.validity.patternMismatch)).toBe(true);
    await auth.serverSubmit(auth.registerForm, 'processRegister');
    await expect(page.locator('#field-regPhone-error')).toContainText('valid Philippine mobile number');
    await expect(phone).toHaveAttribute('aria-invalid', 'true');
    expect(db.snapshot().users.filter(user => user.email === 'applicant@example.test')).toHaveLength(0);
    await expect(page).toHaveURL(/register=1$/);
});

test('phone fields display +63, preserve existing numbers, and accept pasted international numbers', async ({ auth, page, db }, info) => {
    test.setTimeout(60_000);
    await auth.login();
    await auth.goto('/user/dashboard?profile=1');
    const dialog = page.getByRole('dialog', { name: 'Account Profile' });
    const phone = dialog.locator('#field-phone');
    await expect(dialog.locator('#field-phone-country')).toHaveText('+63');
    await expect(phone).toHaveValue('9000000000');
    await auth.action('updateProfile', () => dialog.getByRole('button', { name: 'Save Profile' }).click());
    expect(db.snapshot().users.find(user => user.id === db.ids.tenantId)?.phone).toBe('09000000000');
    await auth.goto('/user/dashboard?profile=1');
    await phone.fill('+639171234567');
    await phone.blur();
    await auth.action('updateProfile', () => dialog.getByRole('button', { name: 'Save Profile' }).click());
    expect(db.snapshot().users.find(user => user.id === db.ids.tenantId)?.phone).toBe('09171234567');
    await auth.logout();
    await page.setViewportSize({ width: 375, height: 812 });
    await auth.open('register');
    await expect(auth.registerForm.locator('#field-regPhone-country')).toHaveText('+63');
    await auth.fillRegistration({ regPhone: '9177654321' });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await info.attach('phone-country-prefix-mobile', { body: await page.screenshot(), contentType: 'image/png' });
    await auth.action('processRegister', () => auth.registerForm.getByRole('button', { name: 'Create Account' }).click());
    await expect(page).toHaveURL(/\/user\/dashboard$/);
    expect(db.snapshot().users.find(user => user.email === 'applicant@example.test')?.phone).toBe('09177654321');
});
