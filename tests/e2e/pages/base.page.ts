import { expect, type Page, type Request, type Locator } from '@playwright/test';

export function callsAction(request: Request, action: string): boolean {
    if (request.method() !== 'POST' || !new URL(request.url()).pathname.endsWith('/livewire/update')) return false;
    const payload = request.postDataJSON();
    return payload?.components?.some((component: any) => component.calls?.some((call: any) => call.method === action)) ?? false;
}

export class BasePage {
    constructor(readonly page: Page) {}

    async goto(path: string) {
        const response = await this.page.goto(path);
        expect(response?.status(), `GET ${path}`).toBe(200);
        await expect(this.page.locator('body')).toBeVisible();
    }

    async action(method: string, trigger: () => Promise<unknown>, status = 200) {
        const responsePromise = this.page.waitForResponse(response => callsAction(response.request(), method)).then(async response => {
            expect(response.status(), `Livewire ${method}`).toBe(status);
            if (status === 200) {
                expect(response.headers()['content-type']).toContain('application/json');
                // Chromium can discard auth response bodies when Livewire immediately redirects.
                if (!['processLogin', 'processRegister'].includes(method)) {
                    const body = await response.json();
                    expect(body.components, `Livewire ${method} response components`).toBeInstanceOf(Array);
                }
            }
            return response;
        });
        const [, response] = await Promise.all([trigger(), responsePromise]);
        return response;
    }

    async serverSubmit(form: Locator, method: string) {
        await form.evaluate((element: HTMLFormElement) => { element.noValidate = true; });
        return this.action(method, () => form.locator('button[type="submit"]').click());
    }

    async logout() {
        const menu = this.page.getByRole('button', { name: 'Menu', exact: true });
        if (await menu.isVisible() && await menu.getAttribute('aria-expanded') === 'false') await menu.click();
        const responsePromise = this.page.waitForResponse(response => new URL(response.url()).pathname === '/logout' && response.request().method() === 'POST');
        await this.page.locator('form[action$="/logout"] button').filter({ visible: true }).first().click();
        expect((await responsePromise).status()).toBe(302);
        await expect(this.page).toHaveURL(/\/$/);
        const response = await this.page.goto('/user/dashboard');
        expect(response?.status()).toBe(200);
        await expect(this.page).toHaveURL(/\?login=1$/);
    }
}
