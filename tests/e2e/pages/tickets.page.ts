import { expect } from '@playwright/test';
import { BasePage } from './base.page';

export class TicketsPage extends BasePage {
    get form() { return this.page.locator('form[wire\\:submit\\.prevent="createTicket"]'); }
    get issue() { return this.form.locator('textarea'); }
    get submit() { return this.form.getByRole('button', { name: 'Submit Ticket' }); }
    get replyForm() { return this.page.locator('form[wire\\:submit\\.prevent="sendMessage"]'); }

    async open() {
        await this.goto('/user/tickets');
        await expect(this.page).toHaveURL(/\/user\/tickets$/);
        await expect(this.page.getByRole('heading', { name: 'Helpdesk & Support' })).toBeVisible();
    }
    async compose(issue?: string) {
        await this.action('openCreateModal', () => this.page.locator('[wire\\:click="openCreateModal"]').click());
        await expect(this.form).toBeVisible();
        if (issue !== undefined) await this.issue.fill(issue);
    }
    async create(issue: string) {
        await this.compose(issue);
        await this.action('createTicket', () => this.submit.click());
        await expect(this.form).not.toBeVisible();
        await expect(this.replyForm).toBeVisible();
        await expect(this.page.getByRole('status').filter({ hasText: 'Support ticket created successfully!' })).toBeVisible();
    }
    async reply(message: string) {
        await this.replyForm.locator('input[type="text"]').fill(message);
        await this.action('sendMessage', () => this.replyForm.getByRole('button', { name: 'Send message' }).click());
        await expect(this.replyForm.locator('input[type="text"]')).toHaveValue('');
        await expect(this.page.getByText(message, { exact: true })).toBeVisible();
    }
}
