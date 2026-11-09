import { expect } from '@playwright/test';
import { BasePage } from './base.page';

export class PortalPage extends BasePage {
    get dialog() { return this.page.getByRole('dialog').filter({ visible: true }); }
    async update(property: string, trigger: () => Promise<unknown>) {
        const responsePromise = this.page.waitForResponse(response => {
            const request = response.request();
            return request.method() === 'POST' && new URL(request.url()).pathname === '/livewire/update'
                && request.postDataJSON()?.components?.some((component: any) => property in component.updates);
        });
        await trigger();
        const response = await responsePromise;
        expect(response.status()).toBe(200);
        expect((await response.json()).components).toBeInstanceOf(Array);
    }
    async checkPage(path: string, heading?: string) {
        await this.goto(path);
        expect(new URL(this.page.url()).pathname).toBe(path);
        await expect(this.page.locator('main h1').first()).toBeVisible();
        if (heading) await expect(this.page.locator('main h1').first()).toHaveText(heading);
        await expect(this.page.locator('#livewire-error')).toHaveCount(0);
    }

    async openModal(action: string, trigger: () => Promise<unknown>, heading: string) {
        await this.action(action, trigger);
        await expect(this.dialog).toBeVisible();
        await expect(this.dialog.getByRole('heading', { name: heading, exact: true })).toBeVisible();
        await expect(this.dialog.getByRole('button', { name: 'Close', exact: true }).first()).toBeFocused();
    }

    async closeModal(action: string) {
        await this.action(action, () => this.page.keyboard.press('Escape'));
        await expect(this.dialog).toHaveCount(0);
    }
}
