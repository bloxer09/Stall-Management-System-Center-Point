import { expect, type Locator } from '@playwright/test';
import { BasePage } from './base.page';

export class RequestErrorPage extends BasePage {
    get alert() { return this.page.locator('[data-request-error]'); }
    get signIn() { return this.alert.getByRole('link', { name: 'Sign in in a new tab' }); }
    get dismiss() { return this.alert.getByRole('button', { name: 'Dismiss request error' }); }

    async visible(message: RegExp, form: Locator) {
        await expect(this.alert).toHaveCount(1);
        await expect(this.alert).toBeVisible();
        await expect(this.alert).toHaveAttribute('role', 'alert');
        await expect(this.alert).toContainText(message);
        await expect(this.alert).not.toContainText('QA simulated');
        await expect(this.page.locator('#livewire-error')).toHaveCount(0);
        await expect(form).toBeVisible();
        expect(await form.evaluate(element => element.closest('dialog')?.contains(document.querySelector('[data-request-error]')))).toBe(true);
        await expect(this.dismiss).toBeEnabled();
    }
}
