import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { expect, Page } from '@playwright/test';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..', '..');

export const accounts = {
    admin: 'admin@restocafe.test',
    manager: 'manager@restocafe.test',
    waiter: 'waiter@restocafe.test',
    kitchen: 'kitchen@restocafe.test',
    cashier: 'cashier@restocafe.test',
    emptyManager: 'empty-manager@restocafe.test',
    emptyWaiter: 'empty-waiter@restocafe.test',
    emptyKitchen: 'empty-kitchen@restocafe.test',
    emptyCashier: 'empty-cashier@restocafe.test',
} as const;

export const seededIds = {
    tables: {
        reserved: 2,
    },
    users: {
        inactiveWaiter: 11,
    },
    categories: {
        coffee: 1,
    },
    items: {
        unavailablePasta: 5,
    },
    inventory: {
        outOfStock: 2,
    },
    orders: {
        new: 2,
        ready: 4,
    },
    invoices: {
        unpaid: 1,
        paidCash: 2,
        paidCard: 3,
    },
} as const;

export async function resetVisualState(): Promise<void> {
    execSync(
        'php artisan migrate:fresh --seed --force && php artisan db:seed --class=Database\\Seeders\\VisualRegressionSeeder --force',
        {
            cwd: ROOT,
            stdio: 'inherit',
        },
    );
}

export async function preparePage(page: Page): Promise<void> {
    await page.route('https://fonts.bunny.net/**', (route) => route.abort());
    await page.addInitScript(() => {
        localStorage.setItem('appearance', 'light');
    });
}

export async function setDesktop(page: Page): Promise<void> {
    await page.setViewportSize({ width: 1440, height: 1200 });
}

export async function setMobile(page: Page): Promise<void> {
    await page.setViewportSize({ width: 390, height: 844 });
}

export async function loginAs(page: Page, email: string): Promise<void> {
    await page.context().clearCookies();
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('password');
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL('**/dashboard');
    await applyStabilityStyles(page);
    await expect(page.getByRole('heading', { name: /Operations|Dashboard/i })).toBeVisible();
}

export async function openPath(page: Page, pathName: string, heading?: string): Promise<void> {
    await page.goto(pathName, { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    if (heading) {
        const headingLocator = page.getByRole('heading', { name: new RegExp(heading, 'i') }).first();

        try {
            await expect(headingLocator).toBeVisible({ timeout: 5_000 });
        } catch {
            await expect(page).toHaveTitle(new RegExp(heading, 'i'));
        }
    }
}

export async function captureFullPage(page: Page, name: string): Promise<void> {
    await applyStabilityStyles(page);
    await expect(page).toHaveScreenshot(name, { fullPage: true });
}

export async function captureElement(page: Page, selector: string, name: string): Promise<void> {
    const locator = page.locator(selector).first();
    await expect(locator).toBeVisible();
    await expect(locator).toHaveScreenshot(name);
}

export async function applyStabilityStyles(page: Page): Promise<void> {
    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
                caret-color: transparent !important;
                scroll-behavior: auto !important;
                font-family: "Segoe UI", Arial, sans-serif !important;
            }
        `,
    });
    await page.waitForTimeout(100);
}
