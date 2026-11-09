import { expect } from '@playwright/test';
import { BasePage } from './base.page';

export class InvoicesPage extends BasePage {
    get selection() { return this.page.getByRole('checkbox', { name: /^Select invoice / }).filter({ visible: true }); }
    get payment() { return this.page.getByRole('button', { name: /^Pay Selected/ }); }

    async toggleAll(selected: boolean) {
        const label = selected ? /^Select All Pending/ : /^Deselect All Pending/;
        await this.action('selectAllPending', () => this.page.getByRole('button', { name: label }).click());
        await expect(this.selection).toBeChecked({ checked: selected });
        await expect(this.payment).toBeEnabled({ enabled: selected });
    }

    async selectInvoice(selected: boolean) {
        const response = this.page.waitForResponse(response => response.request().method() === 'POST'
            && new URL(response.url()).pathname === '/livewire/update');
        await this.selection.setChecked(selected);
        expect((await response).status()).toBe(200);
        await expect(this.selection).toBeChecked({ checked: selected });
        await expect(this.payment).toBeEnabled({ enabled: selected });
    }
}
