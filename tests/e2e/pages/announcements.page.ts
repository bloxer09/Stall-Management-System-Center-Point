import { expect } from '@playwright/test';
import { BasePage } from './base.page';

export class AnnouncementsPage extends BasePage {
    get form() { return this.page.locator('form[wire\\:submit="post"]'); }
    get submit() { return this.form.getByRole('button', { name: 'Post announcement' }); }
    async open() {
        await this.goto('/admin/announcements');
        await expect(this.page).toHaveURL(/\/admin\/announcements$/);
        await expect(this.page.getByRole('heading', { name: 'Announcements', exact: true })).toBeVisible();
        await this.openCreate();
    }
    async openCreate() {
        await this.action('openCreateModal', () => this.page.getByRole('button', { name: 'New announcement', exact: true }).click());
        await expect(this.page.getByRole('dialog', { name: 'New announcement' })).toBeVisible();
    }
    async fill(title: string, body: string) {
        await this.page.getByLabel('Title', { exact: true }).fill(title);
        await this.page.getByLabel('Message', { exact: true }).fill(body);
    }
    async post(title: string, body: string) {
        await this.fill(title, body);
        await this.action('post', () => this.submit.click());
        await expect(this.page.getByRole('status').filter({ hasText: /Announcement posted/ })).toBeVisible();
        await expect(this.form).not.toBeVisible();
        await expect(this.page.getByRole('table').getByText(title, { exact: true })).toBeVisible();
    }
}
