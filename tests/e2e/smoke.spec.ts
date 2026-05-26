import { expect, test } from '@playwright/test';

test.describe('smoke', () => {
  test('landing page boots home component', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/Starlight Dominion/i);

    const initialState = await page.evaluate(() => {
      return (window as { __INITIAL_STATE__?: Record<string, unknown> }).__INITIAL_STATE__;
    });

    expect(initialState).toBeTruthy();
    expect(initialState?.component).toBe('home');
  });

  test('login page boots auth/login component', async ({ page }) => {
    await page.goto('/login');

    const initialState = await page.evaluate(() => {
      return (window as { __INITIAL_STATE__?: Record<string, unknown> }).__INITIAL_STATE__;
    });

    expect(initialState).toBeTruthy();
    expect(initialState?.component).toBe('auth/login');
  });

  test('dashboard route redirects anonymous users to login', async ({ page }) => {
    await page.goto('/dashboard');

    await expect(page).toHaveURL(/\/login(?:\?.*)?$/);

    const initialState = await page.evaluate(() => {
      return (window as { __INITIAL_STATE__?: Record<string, unknown> }).__INITIAL_STATE__;
    });

    expect(initialState).toBeTruthy();
    expect(initialState?.component).toBe('auth/login');
  });

  test('new commander can register and reach dashboard', async ({ page }) => {
    const stamp = Date.now();
    const username = `e2e_commander_${stamp}`;
    const email = `e2e_${stamp}@example.test`;
    const dominion = `E2E Dominion ${stamp}`;
    const password = 'E2Epass123!';

    await page.goto('/register');

    await page.locator('input[type="email"]').fill(email);
    await page.locator('input[type="text"]').first().fill(username);
    await page.locator('input[type="text"]').nth(1).fill(dominion);
    await page.locator('select').selectOption('Human');
    await page.locator('input[type="password"]').first().fill(password);
    await page.locator('input[type="password"]').nth(1).fill(password);
    await page.getByRole('button', { name: /establish sovereignty/i }).click();

    await expect(page).toHaveURL(/\/login\?success=1$/);

    await page.locator('input[type="text"]').fill(username);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /authorize access/i }).click();

    await expect(page).toHaveURL(/\/dashboard$/);

    const initialState = await page.evaluate(() => {
      return (window as { __INITIAL_STATE__?: Record<string, unknown> }).__INITIAL_STATE__;
    });

    expect(initialState).toBeTruthy();
    expect(initialState?.component).toBe('dashboard/index');
  });
});
