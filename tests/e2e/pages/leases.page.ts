import { expect } from '@playwright/test';
import { BasePage } from './base.page';

export class LeasesPage extends BasePage {
    get form() { return this.page.locator('form[wire\\:submit\\.prevent="saveMapping"]'); }
    async openAssignment(tenantId: number, stallId: number) {
        await this.goto(`/admin/mappings?user=${tenantId}&stall=${stallId}`);
        await expect(this.form).toBeVisible();
        await expect(this.page.locator('#assignment-tenant')).toHaveValue(String(tenantId));
        await expect(this.page.locator('#assignment-stall')).toHaveValue(String(stallId));
    }

    async assign() {
        await this.action('saveMapping', () => this.form.locator('button[type="submit"]').click());
        await expect(this.form).not.toBeVisible();
        await expect(this.page.getByRole('status').filter({ hasText: 'Stall assigned.' })).toBeVisible();
    }

    async recordCashPayment(id: number) {
        await this.action('openDetailModal', () => this.page.locator(`[wire\\:click="openDetailModal(${id})"]`).click());
        await expect(this.page.getByText('Awaiting Initial Payment', { exact: true })).toBeVisible();
        await this.action('recordInitialCashPayment', () => this.page.getByRole('button', { name: 'Record Office Cash Payment (Activate Lease)' }).click());
        await expect(this.page.getByRole('status').filter({ hasText: /Office cash payment recorded/ })).toBeVisible();
    }
}
