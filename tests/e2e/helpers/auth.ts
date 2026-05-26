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

async function getCsrfToken(page: Page): Promise<string> {
  return page.evaluate(() => {
    const state = (window as { __INITIAL_STATE__?: { csrf?: string } }).__INITIAL_STATE__;
    const token = (window as { __CSRF_TOKEN__?: string }).__CSRF_TOKEN__;

    return state?.csrf ?? token ?? '';
  });
}

async function submitAuthRequest(page: Page, path: string, payload: Record<string, string>): Promise<void> {
  const csrf = await getCsrfToken(page);

  const result = await page.evaluate(async ({ path, payload, csrf }) => {
    const submission = new FormData();

    for (const [key, value] of Object.entries(payload)) {
      submission.append(key, value);
    }

    submission.append('_csrf', csrf);

    const response = await fetch(path, {
      method: 'POST',
      body: submission,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    let body: unknown = null;
    try {
      body = await response.json();
    } catch {
      body = null;
    }

    return {
      ok: response.ok,
      status: response.status,
      body,
    };
  }, { path, payload, csrf });

  expect(result.ok, `${path} failed with status ${result.status}`).toBe(true);
}

export async function registerCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await page.goto('/register');

  await submitAuthRequest(page, '/register', {
    email: creds.email,
    username: creds.username,
    dominion_name: creds.dominionName,
    race: 'Human',
    password: creds.password,
    password_confirmation: creds.password,
  });

  await page.goto('/login?success=1');

  await expect(page).toHaveURL(/\/login\?success=1$/);
}

export async function loginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await submitAuthRequest(page, '/login', {
    username: creds.username,
    password: creds.password,
  });

  await page.goto('/dashboard');

  await expect(page).toHaveURL(/\/dashboard$/);
}

export async function registerAndLoginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await registerCommander(page, creds);
  await loginCommander(page, creds);
}
