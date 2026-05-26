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

  await page.getByTestId('register-email').fill(creds.email);
  await page.getByTestId('register-username').fill(creds.username);
  await page.getByTestId('register-dominion-name').fill(creds.dominionName);
  await page.getByTestId('register-race').selectOption('Human');
  await page.getByTestId('register-password').fill(creds.password);
  await page.getByTestId('register-password-confirmation').fill(creds.password);
  await page.getByTestId('register-submit').click();

  await expect(page).toHaveURL(/\/login\?success=1$/);
}

export async function loginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await page.getByTestId('login-username').fill(creds.username);
  await page.getByTestId('login-password').fill(creds.password);
  await page.getByTestId('login-submit').click();

  await expect(page).toHaveURL(/\/dashboard$/);
}

export async function registerAndLoginCommander(page: Page, creds: CommanderCredentials): Promise<void> {
  await registerCommander(page, creds);
  await loginCommander(page, creds);
}
