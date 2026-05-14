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
    setDesktop,
    setMobile,
} from './support/visual-helpers';

test.describe.configure({ mode: 'serial' });

const referenceAssets = [
    '/assets/reference/apple-icon.png',
    '/assets/reference/icon-dark-32x32.png',
    '/assets/reference/icon-light-32x32.png',
    '/assets/reference/icon.svg',
    '/assets/reference/placeholder-logo.png',
    '/assets/reference/placeholder-logo.svg',
    '/assets/reference/placeholder-user.jpg',
    '/assets/reference/placeholder.jpg',
    '/assets/reference/placeholder.svg',
    '/assets/reference/photos/about-restaurant.jpg',
    '/assets/reference/photos/category-appetizers.jpg',
    '/assets/reference/photos/category-beverages.jpg',
    '/assets/reference/photos/category-desserts.jpg',
    '/assets/reference/photos/category-main-courses.jpg',
    '/assets/reference/photos/category-pasta.jpg',
    '/assets/reference/photos/category-seafood.jpg',
    '/assets/reference/photos/chef-avatar.jpg',
    '/assets/reference/photos/dish-caprese-salad.jpg',
    '/assets/reference/photos/dish-lamb-chops.jpg',
    '/assets/reference/photos/dish-ribeye-steak.jpg',
    '/assets/reference/photos/dish-truffle-bruschetta.jpg',
    '/assets/reference/photos/landing-hero.jpg',
    '/assets/reference/photos/login-hero.jpg',
];

async function expectReferenceAssetsAvailable(page: Page): Promise<void> {
    for (const asset of referenceAssets) {
        const response = await page.request.get(asset);
        expect(response.ok(), `${asset} should load`).toBe(true);
    }
}

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

test('captures public landing page desktop and mobile', async ({ page }) => {
    await setDesktop(page);
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expectReferenceAssetsAvailable(page);
    await expectNoBrokenImages(page);
    await captureFullPage(page, 'public-landing-desktop.png');

    // Hero section element-level
    await captureElement(page, 'section', 'public-landing-hero-desktop.png');

    // Public menu proof section (if present)
    const featuredSection = page.locator('section').filter({ hasText: 'A customer-facing menu' });
    if ((await featuredSection.count()) > 0) {
        await captureElement(page, 'section:has-text("A customer-facing menu")', 'public-landing-featured-desktop.png');
    }

    // Public nav header
    await captureElement(page, 'nav', 'public-landing-header-desktop.png');

    // Footer
    await captureElement(page, 'footer', 'public-landing-footer-desktop.png');

    // Mobile
    await setMobile(page);
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expectNoBrokenImages(page);
    await captureFullPage(page, 'public-landing-mobile.png');

    await page
        .getByRole('group', { name: /Language/i })
        .first()
        .getByRole('button', { name: 'AR' })
        .click();
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.getByText('منصة إدارة مطاعم بنمط SaaS')).toBeVisible();
    await expectNoBrokenImages(page);
});

test('captures public menu page desktop and mobile', async ({ page }) => {
    await setDesktop(page);
    await page.goto('/menu', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);

    // The public menu only renders if branch is_public=true (seeded in VisualRegressionSeeder)
    await expect(page).not.toHaveURL('**/404');
    await captureFullPage(page, 'public-menu-desktop.png');

    // Hero strip
    await captureElement(page, 'main section', 'public-menu-hero-desktop.png');

    // A menu item card
    const itemCard = page.locator('article').filter({ hasText: 'Espresso' }).first();
    if ((await itemCard.count()) > 0) {
        await captureElement(page, 'article', 'public-menu-item-card-desktop.png');
    }

    // Mobile
    await setMobile(page);
    await page.goto('/menu', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await captureFullPage(page, 'public-menu-mobile.png');
});

test('captures branch settings branding tab', async ({ page }) => {
    await setDesktop(page);
    await loginAs(page, accounts.manager);

    await openPath(page, '/settings/branch', 'Branch Settings');
    await captureFullPage(page, 'branch-settings-tab-settings-desktop.png');

    // Click Branding tab
    await page.getByRole('button', { name: 'Branding' }).click();
    await applyStabilityStyles(page);
    await captureFullPage(page, 'branch-settings-tab-branding-desktop.png');

    // Brand colors card
    const colorsCard = page.locator('div.grid').filter({ hasText: 'Brand Colors' });
    if ((await colorsCard.count()) > 0) {
        await captureElement(page, 'div:has-text("Brand Colors")', 'branch-settings-color-pickers-desktop.png');
    }

    // Social card
    const socialCard = page.locator('div.grid').filter({ hasText: 'Social' });
    if ((await socialCard.count()) > 0) {
        await captureElement(page, 'div:has-text("Social")', 'branch-settings-social-card-desktop.png');
    }

    // Mobile
    await setMobile(page);
    await openPath(page, '/settings/branch', 'Branch Settings');
    await page.getByRole('button', { name: 'Branding' }).click();
    await applyStabilityStyles(page);
    await captureFullPage(page, 'branch-settings-tab-branding-mobile.png');
});

test('captures public landing with slug query param', async ({ page }) => {
    await setDesktop(page);
    await page.goto('/menu?slug=restocafe', { waitUntil: 'domcontentloaded' });
    await applyStabilityStyles(page);
    await expect(page).not.toHaveURL('**/404');
    await captureFullPage(page, 'public-menu-by-slug-desktop.png');
});
