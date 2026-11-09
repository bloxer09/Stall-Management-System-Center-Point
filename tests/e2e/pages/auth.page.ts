import { expect } from '@playwright/test';
import { BasePage } from './base.page';

export const password = 'Defense!2026';
export class AuthPage extends BasePage {
    get loginForm() { return this.page.locator('form[wire\\:submit="processLogin"]'); }
    get registerForm() { return this.page.locator('form[wire\\:submit="processRegister"]'); }

    async open(tab: 'login' | 'register' = 'login') {
        await this.goto(`/?${tab}=1`);
        await expect(tab === 'login' ? this.loginForm : this.registerForm).toBeVisible();
    }

    async login(account: 'tenant' | 'other' | 'admin' | 'secure' = 'tenant') {
        await this.open();
        await this.page.locator('#field-loginEmail').fill(`${account}@example.test`);
        await this.page.locator('#field-loginPassword').fill(password);
        await this.action('processLogin', () => this.loginForm.locator('button[type="submit"]').click());
        await expect(this.page).toHaveURL(account === 'secure' ? /\/two-factor$/ : account === 'admin' ? /\/admin\/dashboard$/ : /\/user\/dashboard$/);
        await expect(this.loginForm).not.toBeVisible();
    }

    async fillRegistration(overrides: Partial<Record<'regName' | 'regEmail' | 'regPhone' | 'regPassword' | 'regPasswordConfirmation', string>> = {}) {
        const values = {
            regName: 'Defense Applicant', regEmail: 'applicant@example.test', regPhone: '09123456789',
            regPassword: password, regPasswordConfirmation: password, ...overrides,
        };
        for (const [field, value] of Object.entries(values)) await this.page.locator(`#field-${field}`).fill(value);
        await this.registerForm.locator('input[wire\\:model="terms"]').check();
    }
}
