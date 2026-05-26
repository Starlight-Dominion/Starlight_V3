import { expect, type Page } from '@playwright/test';

export type CommanderCredentials = {
  username: string;
  email: string;
  dominionName: string;
  password: string;
};

export function makeCommanderCredentials(prefix = 'e2e'): CommanderCredentials {
  const stamp = Date.now();

  return {
    username: `${prefix}_commander_${stamp}`,
    email: `${prefix}_${stamp}@example.test`,
    dominionName: `${prefix.toUpperCase()} Dominion ${stamp}`,
    password: 'E2Epass123!',
  };
}

export async function registerCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await page.goto('/register');

  await page.locator('input[type="email"]').fill(creds.email);
  await page.locator('input[type="text"]').first().fill(creds.username);
  await page.locator('input[type="text"]').nth(1).fill(creds.dominionName);
  await page.locator('select').selectOption('Human');
  await page.locator('input[type="password"]').first().fill(creds.password);
  await page.locator('input[type="password"]').nth(1).fill(creds.password);
  await page.getByRole('button', { name: /establish sovereignty/i }).click();

  await expect(page).toHaveURL(/\/login\?success=1$/);
}

export async function loginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await page.locator('input[type="text"]').fill(creds.username);
  await page.locator('input[type="password"]').fill(creds.password);
  await page.getByRole('button', { name: /authorize access/i }).click();

  await expect(page).toHaveURL(/\/dashboard$/);
}

export async function registerAndLoginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await registerCommander(page, creds);
  await loginCommander(page, creds);
}
