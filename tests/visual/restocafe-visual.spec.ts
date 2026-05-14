import { expect, test, type Page } from '@playwright/test';
import {
    accounts,
    applyStabilityStyles,
    captureElement,
    captureFullPage,
    loginAs,
    openPath,
    preparePage,
    resetVisualState,
    seededIds,
    setDesktop,
    setMobile,
} from './support/visual-helpers';

test.describe.configure({ mode: 'serial' });

async function expectNoBrokenImages(page: Page): Promise<void> {
    const brokenImages = await page
        .locator('img')
        .evaluateAll((images) =>
            images.filter((image) => !image.complete || image.naturalWidth === 0).map((image) => image.getAttribute('src') ?? ''),
        );

    expect(brokenImages).toEqual([]);
}

test.beforeEach(async ({ page }) => {
    await resetVisualState();
    await preparePage(page);
});

test('captures login, validation, and auth shell states', async ({ page }) => {
    await setDesktop(page);
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expectNoBrokenImages(page);
    await captureFullPage(page, 'login-desktop.png');

    await page.locator('#email').fill('wrong@restocafe.test');
    await page.locator('#password').fill('wrong-password');
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL('**/login');
    await captureFullPage(page, 'login-validation-error-desktop.png');
    await captureElement(page, 'form', 'login-validation-block.png');

    await setMobile(page);
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expectNoBrokenImages(page);
    await captureFullPage(page, 'login-mobile.png');

    await page
        .getByRole('group', { name: /Language/i })
        .first()
        .getByRole('button', { name: 'AR' })
        .click();
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.getByRole('heading', { name: 'ادخل إلى مساحة العمل' })).toBeVisible();
    await expectNoBrokenImages(page);
});

test('captures dashboard roles, sidebar, and user menu components', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.admin);
    await captureFullPage(page, 'dashboard-admin-desktop.png');
    await captureElement(page, '[data-sidebar="sidebar"]', 'sidebar-admin.png');
    await captureElement(page, 'main .grid.gap-4, main .grid.md\\:grid-cols-2', 'dashboard-stat-cards.png');
    await page.locator('header button').last().click();
    await applyStabilityStyles(page);
    await captureElement(page, '[data-radix-popper-content-wrapper]', 'user-menu-dropdown.png');

    await loginAs(page, accounts.manager);
    await captureFullPage(page, 'dashboard-manager-desktop.png');

    await loginAs(page, accounts.waiter);
    await captureFullPage(page, 'dashboard-waiter-desktop.png');

    await loginAs(page, accounts.kitchen);
    await captureFullPage(page, 'dashboard-kitchen-desktop.png');

    await loginAs(page, accounts.cashier);
    await captureFullPage(page, 'dashboard-cashier-desktop.png');

    await loginAs(page, accounts.emptyManager);
    await captureFullPage(page, 'dashboard-empty-manager-desktop.png');

    await setMobile(page);
    await loginAs(page, accounts.waiter);
    await captureFullPage(page, 'dashboard-waiter-mobile.png');
});

test('captures notifications, messages, AI assistant, and not-found states', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.admin);
    await openPath(page, '/notifications', 'Notifications');
    await captureFullPage(page, 'notifications-empty-desktop.png');

    await openPath(page, '/messages?preview=1', 'Messages');
    await page.getByRole('button', { name: /Kitchen Station/i }).click();
    await captureFullPage(page, 'messages-desktop.png');

    await openPath(page, '/assistant', 'AI Assistant');
    await captureFullPage(page, 'ai-assistant-page.png');

    await page.goto('/missing-path', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expect(page.getByRole('heading', { name: 'Page not found' })).toBeVisible();
    await captureFullPage(page, 'not-found-desktop.png');

    await setMobile(page);
    await loginAs(page, accounts.admin);
    await openPath(page, '/messages?preview=1', 'Messages');
    await captureFullPage(page, 'messages-mobile-list.png');
    await page.getByRole('button', { name: /Kitchen Station/i }).click();
    await captureFullPage(page, 'messages-mobile-thread.png');
    await page.getByRole('button', { name: 'Back' }).click();
    await expect(page.getByPlaceholder('Search conversations…')).toBeVisible();
});

test('captures table, user, and settings screens with permission-limited states', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.manager);
    await openPath(page, '/tables', 'Tables');
    await captureFullPage(page, 'tables-index-manager-desktop.png');
    await captureElement(page, 'main .grid.gap-4 > div, main .grid.gap-4 > article, main .grid.gap-4 > *', 'table-card.png');

    await loginAs(page, accounts.waiter);
    await openPath(page, '/tables', 'Tables');
    await captureFullPage(page, 'tables-index-waiter-desktop.png');

    await loginAs(page, accounts.cashier);
    await openPath(page, '/tables', 'Tables');
    await captureFullPage(page, 'tables-index-cashier-desktop.png');

    await loginAs(page, accounts.emptyManager);
    await openPath(page, '/tables', 'Tables');
    await captureFullPage(page, 'tables-index-empty-manager-desktop.png');

    await loginAs(page, accounts.manager);
    await openPath(page, '/tables/create', 'Create Table');
    await captureFullPage(page, 'tables-form-create-desktop.png');
    await openPath(page, `/tables/${seededIds.tables.reserved}/edit`, 'Edit Table');
    await captureFullPage(page, 'tables-form-edit-desktop.png');

    await loginAs(page, accounts.admin);
    await openPath(page, '/users', 'Users & Roles');
    await captureFullPage(page, 'users-index-admin-desktop.png');
    await captureElement(page, 'tbody tr:last-child td:nth-child(4)', 'user-inactive-badge.png');

    await openPath(page, '/users/create', 'Create User');
    await captureFullPage(page, 'users-form-create-desktop.png');
    await openPath(page, `/users/${seededIds.users.inactiveWaiter}/edit`, 'Edit User');
    await captureFullPage(page, 'users-form-edit-inactive-desktop.png');

    await openPath(page, '/settings/branch', 'Branch Settings');
    await captureFullPage(page, 'branch-settings-desktop.png');

    await openPath(page, '/settings/profile', 'Profile information');
    await captureFullPage(page, 'settings-profile-desktop.png');

    await openPath(page, '/settings/password', 'Update password');
    await captureFullPage(page, 'settings-password-desktop.png');

    await openPath(page, '/settings/appearance', 'Appearance settings');
    await captureFullPage(page, 'settings-appearance-desktop.png');

    await setMobile(page);
    await openPath(page, '/users', 'Users & Roles');
    await captureFullPage(page, 'users-index-admin-mobile.png');
});

test('captures menu category and menu item surfaces', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.manager);
    await openPath(page, '/menu/categories', 'Menu Categories');
    await captureFullPage(page, 'menu-categories-index-desktop.png');

    await openPath(page, '/menu/categories/create', 'Create Category');
    await captureFullPage(page, 'menu-categories-form-create-desktop.png');
    await openPath(page, `/menu/categories/${seededIds.categories.coffee}/edit`, 'Edit Category');
    await captureFullPage(page, 'menu-categories-form-edit-desktop.png');

    await openPath(page, '/menu/items', 'Menu Management');
    await captureFullPage(page, 'menu-items-index-desktop.png');
    await captureElement(page, 'tbody tr:last-child td:nth-child(4)', 'menu-item-unavailable-badge.png');

    await openPath(page, '/menu/items/create', 'Create Item');
    await captureFullPage(page, 'menu-items-form-create-desktop.png');
    await openPath(page, `/menu/items/${seededIds.items.unavailablePasta}/edit`, 'Edit Item');
    await captureFullPage(page, 'menu-items-form-edit-desktop.png');

    await loginAs(page, accounts.emptyManager);
    await openPath(page, '/menu/categories', 'Menu Categories');
    await captureFullPage(page, 'menu-categories-empty-desktop.png');

    await openPath(page, '/menu/items', 'Menu Management');
    await captureFullPage(page, 'menu-items-empty-desktop.png');
});

test('captures order index, create, submission, and show states', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.waiter);
    await openPath(page, '/orders', 'Orders');
    await captureFullPage(page, 'orders-index-waiter-desktop.png');

    await openPath(page, '/orders/create?table_id=1', 'Create Order');
    await captureFullPage(page, 'orders-create-desktop.png');
    await page.getByRole('button', { name: 'Create order' }).click();
    await page.waitForURL('**/orders/create?table_id=1');
    await captureFullPage(page, 'orders-create-validation-desktop.png');
    await captureElement(page, '[role="alert"]', 'order-validation-block.png');

    // Validation POST can reset client state — reopen POS and pick any available table.
    await page.goto('/orders/create', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expect(page.getByRole('heading', { name: 'Create Order' })).toBeVisible();
    await page.locator('[role="combobox"]').first().click();
    await page.locator('[role="option"]').first().click();

    await page.route('**/orders', async (route) => {
        if (route.request().method() === 'POST') {
            await page.waitForTimeout(500);
        }
        await route.continue();
    });

    await page.locator('main').getByRole('checkbox').first().check();
    await expect(page.getByRole('button', { name: 'Create order' })).toBeEnabled();
    await page.getByRole('button', { name: 'Create order' }).click();
    await captureFullPage(page, 'orders-create-processing-desktop.png');
    await page.waitForURL('**/orders/*');
    await captureFullPage(page, 'orders-show-created-success-desktop.png');
    await page.unroute('**/orders');

    await openPath(page, `/orders/${seededIds.orders.new}`, `Order #${seededIds.orders.new}`);
    await captureFullPage(page, 'orders-show-new-desktop.png');
    await captureElement(page, 'main .space-y-4 > div', 'order-item-row.png');

    await loginAs(page, accounts.cashier);
    await openPath(page, `/orders/${seededIds.orders.ready}`, `Order #${seededIds.orders.ready}`);
    await captureFullPage(page, 'orders-show-ready-cashier-desktop.png');

    await loginAs(page, accounts.emptyWaiter);
    await openPath(page, '/orders', 'Orders');
    await captureFullPage(page, 'orders-index-empty-waiter-desktop.png');

    await page.goto('/orders/create', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await applyStabilityStyles(page);
    await expect(page.getByRole('heading', { name: 'Create Order' })).toBeVisible({ timeout: 60_000 });
    await captureFullPage(page, 'orders-create-empty-waiter-desktop.png');

    await setMobile(page);
    await loginAs(page, accounts.waiter);
    await page.goto('/orders/create', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await applyStabilityStyles(page);
    await expect(page.getByRole('heading', { name: 'Create Order' })).toBeVisible({ timeout: 60_000 });
    await captureFullPage(page, 'orders-create-mobile.png');
    await openPath(page, `/orders/${seededIds.orders.new}`, `Order #${seededIds.orders.new}`);
    await captureFullPage(page, 'orders-show-new-mobile.png');
});

test('captures kitchen board and invoice states', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.kitchen);
    await openPath(page, '/kitchen', 'Kitchen Board');
    await captureFullPage(page, 'kitchen-index-desktop.png');
    await captureElement(page, 'main .grid.gap-4 > div, main .grid.gap-4 > article, main .grid.gap-4 > *', 'kitchen-status-card.png');

    await loginAs(page, accounts.emptyKitchen);
    await openPath(page, '/kitchen', 'Kitchen Board');
    await captureFullPage(page, 'kitchen-empty-desktop.png');

    await loginAs(page, accounts.cashier);
    await openPath(page, '/invoices', 'Invoices');
    await captureFullPage(page, 'invoices-index-desktop.png');

    await openPath(page, `/invoices/${seededIds.invoices.unpaid}`, 'INV-2026-000001');
    await captureFullPage(page, 'invoice-show-unpaid-desktop.png');
    await captureElement(page, 'main .grid.gap-6 > div:last-child, main .grid.gap-6 > *:last-child', 'invoice-summary-card.png');

    await openPath(page, `/invoices/${seededIds.invoices.paidCash}`, 'INV-2026-000002');
    await captureFullPage(page, 'invoice-show-paid-cash-desktop.png');

    await openPath(page, `/invoices/${seededIds.invoices.paidCard}`, 'INV-2026-000003');
    await captureFullPage(page, 'invoice-show-paid-card-desktop.png');

    await loginAs(page, accounts.emptyCashier);
    await openPath(page, '/invoices', 'Invoices');
    await captureFullPage(page, 'invoices-empty-desktop.png');

    await setMobile(page);
    await loginAs(page, accounts.kitchen);
    await openPath(page, '/kitchen', 'Kitchen Board');
    await captureFullPage(page, 'kitchen-index-mobile.png');

    await loginAs(page, accounts.cashier);
    await openPath(page, '/invoices', 'Invoices');
    await captureFullPage(page, 'invoices-index-mobile.png');
    await openPath(page, `/invoices/${seededIds.invoices.unpaid}`, 'INV-2026-000001');
    await captureFullPage(page, 'invoice-show-unpaid-mobile.png');
});

test('captures inventory and report surfaces', async ({ page }) => {
    await setDesktop(page);

    await loginAs(page, accounts.manager);
    await openPath(page, '/inventory', 'Inventory');
    await captureFullPage(page, 'inventory-index-desktop.png');
    await captureElement(page, 'main .grid.gap-4 > div, main .grid.gap-4 > article, main .grid.gap-4 > *', 'inventory-status-card.png');

    const outOfStockCard = page.locator('main .grid.gap-4 > *').filter({ hasText: 'Cappuccino Stock' }).first();
    await outOfStockCard.getByPlaceholder('Adjustment (+/-)').fill('-1');
    await outOfStockCard.getByPlaceholder('Reason').fill('Visual negative check');
    await outOfStockCard.getByRole('button', { name: 'Apply Adjustment' }).click();
    await page.waitForURL('**/inventory');
    await captureElement(page, '[role="alert"]', 'flash-error-inventory-adjustment.png');

    const inStockCard = page.locator('main .grid.gap-4 > *').filter({ hasText: 'Espresso Stock' }).first();
    await inStockCard.getByPlaceholder('Adjustment (+/-)').fill('1');
    await inStockCard.getByPlaceholder('Reason').fill('Visual restock');
    await inStockCard.getByRole('button', { name: 'Apply Adjustment' }).click();
    await page.waitForURL('**/inventory');
    await captureElement(page, '[role="alert"]', 'flash-success-inventory-adjustment.png');

    await openPath(page, '/inventory/create', 'Create Inventory Item');
    await captureFullPage(page, 'inventory-form-create-desktop.png');
    await openPath(page, `/inventory/${seededIds.inventory.outOfStock}/edit`, 'Edit Inventory Item');
    await captureFullPage(page, 'inventory-form-edit-desktop.png');

    await openPath(page, '/reports?date=2026-04-18', 'Reports Lite');
    await captureFullPage(page, 'reports-index-desktop.png');

    await loginAs(page, accounts.emptyManager);
    await openPath(page, '/inventory', 'Inventory');
    await captureFullPage(page, 'inventory-empty-desktop.png');

    await openPath(page, '/reports?date=2026-04-18', 'Reports Lite');
    await captureFullPage(page, 'reports-empty-desktop.png');

    await setMobile(page);
    await loginAs(page, accounts.manager);
    await openPath(page, '/reports?date=2026-04-18', 'Reports Lite');
    await captureFullPage(page, 'reports-mobile.png');
});
