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

export async function registerCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await page.goto('/register');

  await page.getByLabel(/comms frequency/i).fill(creds.email);
  await page.getByLabel(/identity handle/i).fill(creds.username);
  await page.getByLabel(/dominion designation/i).fill(creds.dominionName);
  await page.getByLabel(/evolutionary strain/i).selectOption('Human');
  await page.getByLabel(/^cipher$/i).fill(creds.password);
  await page.getByLabel(/verify/i).fill(creds.password);
  await page.getByRole('button', { name: /establish sovereignty/i }).click();

  await expect(page).toHaveURL(/\/login\?success=1$/);
}

export async function loginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await page.getByLabel(/commander identity/i).fill(creds.username);
  await page.getByLabel(/encryption key/i).fill(creds.password);
  await page.getByRole('button', { name: /authorize access/i }).click();

  await expect(page).toHaveURL(/\/dashboard$/);
}

export async function registerAndLoginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await registerCommander(page, creds);
  await loginCommander(page, creds);
}
