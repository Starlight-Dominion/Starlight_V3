import { expect, test } from './fixtures';
import { makeCommanderCredentials, registerAndLoginCommander } from './helpers/auth';

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
    const creds = makeCommanderCredentials();
    await registerAndLoginCommander(page, creds);

    const initialState = await page.evaluate(() => {
      return (window as { __INITIAL_STATE__?: Record<string, unknown> }).__INITIAL_STATE__;
    });

    expect(initialState).toBeTruthy();
    expect(initialState?.component).toBe('dashboard/index');
  });
});
