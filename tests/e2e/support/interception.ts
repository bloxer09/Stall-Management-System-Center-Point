import type { Page, Route } from '@playwright/test';
import { callsAction } from '../pages/base.page';

export async function holdAction(page: Page, action: string) {
    let release!: () => void;
    let arrived!: () => void;
    const gate = new Promise<void>(resolve => { release = resolve; });
    const held = new Promise<void>(resolve => { arrived = resolve; });
    let count = 0;
    const handler = async (route: Route) => {
        if (!callsAction(route.request(), action)) return route.continue();
        count++;
        arrived();
        await gate;
        await route.continue().catch(() => {});
    };
    await page.route('**/livewire/update', handler);
    return {
        held,
        release,
        count: () => count,
        dispose: async () => { release(); await page.unroute('**/livewire/update', handler); },
    };
}
