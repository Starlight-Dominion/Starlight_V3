import { randomUUID } from 'node:crypto';

import { expect, type Page } from '@playwright/test';

export type CommanderCredentials = {
  username: string;
  email: string;
  dominionName: string;
  password: string;
};

export function makeCommanderCredentials(prefix = 'e2e'): CommanderCredentials {
  const stamp = Date.now();
  const uniqueSuffix = `${stamp}_${randomUUID().slice(0, 8)}`;

  return {
    username: `${prefix}_commander_${uniqueSuffix}`,
    email: `${prefix}_${uniqueSuffix}@example.test`,
    dominionName: `${prefix.toUpperCase()} Dominion ${uniqueSuffix}`,
    password: 'E2Epass123!',
  };
}

export async function registerAndLoginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  // 1. Initial hit to get CSRF token
  await page.goto('/register');
  const csrf = await page.evaluate(() => (window as any).__CSRF_TOKEN__);

  if (!csrf) {
      throw new Error("Failed to extract CSRF token from /register shell.");
  }

  // 2. Perform Registration via Fetch for speed/reliability
  const regResult = await page.evaluate(async ({ creds, csrf }) => {
    const fd = new FormData();
    fd.append('username', creds.username);
    fd.append('email', creds.email);
    fd.append('dominion_name', creds.dominionName);
    fd.append('race', 'Human');
    fd.append('password', creds.password);
    fd.append('password_confirmation', creds.password);
    fd.append('_csrf', csrf);

    const res = await fetch('/register', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return res.json();
  }, { creds, csrf });

  if (!regResult.success) {
      throw new Error(`E2E Registration failed: ${regResult.message || JSON.stringify(regResult)}`);
  }

  // 3. Perform Login via Fetch to establish session
  const loginResult = await page.evaluate(async ({ creds, csrf }) => {
    const fd = new FormData();
    fd.append('username', creds.username);
    fd.append('password', creds.password);
    fd.append('_csrf', csrf);

    const res = await fetch('/login', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return res.json();
  }, { creds, csrf });

  if (!loginResult.success) {
      throw new Error(`E2E Login failed: ${loginResult.message || JSON.stringify(loginResult)}`);
  }

  // 4. Verify we can reach the dashboard
  await page.goto('/dashboard');
  await expect(page).toHaveURL(/\/dashboard$/);
}
