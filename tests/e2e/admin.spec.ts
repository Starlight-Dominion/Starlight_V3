import { expect, test } from '@playwright/test';
import { makeCommanderCredentials, registerAndLoginCommander } from './helpers/auth';
import { promoteToAdmin } from './helpers/admin';

test.describe('Admin Suite', () => {
  test.beforeEach(async ({ page }) => {
    const creds = makeCommanderCredentials('admin_test');
    await registerAndLoginCommander(page, creds);
    await promoteToAdmin(creds.username);
    
    // Refresh to get admin session state
    await page.goto('/dashboard');
    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('h1')).toContainText(/Command Center/i);
  });

  test('Module: Command Overview renders correctly', async ({ page }) => {
    await expect(page.locator('text=Total Sovereigns')).toBeVisible();
    await expect(page.locator('text=Active Sectors')).toBeVisible();
    await expect(page.locator('text=Total Wealth')).toBeVisible();
  });

  test('Module: Global Mechanics - Update setting', async ({ page }) => {
    await page.click('button:has-text("Global Mechanics")');
    const input = page.locator('input[placeholder="Enter global transmission..."]');
    await input.fill('E2E TEST BROADCAST ' + Date.now());
    await page.click('button:has-text("Transmit")');
    
    await expect(page.locator('text=Directives Committed Successfully')).toBeVisible();
  });

  test('Module: War Room (Units) - Tactical Calibration', async ({ page }) => {
    await page.click('button:has-text("War Room (Units)")');
    await expect(page.locator('h3:has-text("Combat Doctrine")')).toBeVisible();
    
    // Open inspector for first unit
    await page.click('button:has-text("Tactical Calibration") >> nth=0');
    await expect(page.locator('p').filter({ hasText: 'TACTICAL CALIBRATION' }).first()).toBeVisible();
    
    // Go to yield tab
    await page.click('button:has-text("Tactical Yield")');
    
    // Change offense power
    const atkInput = page.locator('span:has-text("Offensive Power") + input');
    await expect(atkInput).toBeVisible();
    const oldVal = await atkInput.inputValue();
    await atkInput.fill((parseInt(oldVal) + 1).toString());
    
    await page.click('button:has-text("COMMIT DOCTRINE")');
    await expect(page.locator('text=Directives Committed Successfully')).toBeVisible();
  });

  test('Module: Structural Engineering - Modify Ranks', async ({ page }) => {
    await page.click('button:has-text("Structural Engineering")');
    await page.click('button:has-text("Structural Calibration") >> nth=0');
    
    await page.click('button:has-text("Evolution Matrix")');
    await page.waitForTimeout(1000);
    
    // Change Integrity (HP) for first rank row
    const hpInput = page.locator('span:has-text("Integrity (HP)") + input').first();
    await expect(hpInput).toBeVisible();
    await hpInput.fill('1500');
    
    await page.locator('button').filter({ hasText: 'Sync Rank' }).first().click();
    await expect(page.locator('text=Directives Committed Successfully')).toBeVisible();
  });

  test('Module: Sovereign Oversight - Search and Inspect', async ({ page }) => {
    await page.click('button:has-text("Sovereign Oversight")');
    await expect(page.locator('h3:has-text("Sovereign Oversight")')).toBeVisible();
    
    // Search for self
    const searchInput = page.locator('input[placeholder="Search kingdoms..."]');
    await searchInput.fill('admin_test');
    await page.press('input[placeholder="Search kingdoms..."]', 'Enter');
    
    // Wait for results
    await page.waitForTimeout(4000);
    
    // Find the result and click inspector
    const result = page.locator('.bg-dark-translucent').filter({ hasText: 'CDR: admin_test' }).first();
    await expect(result).toBeVisible();
    await result.locator('button:has-text("Sovereign Inspector")').click({ force: true });
    
    await expect(page.locator('p').filter({ hasText: 'DEEP-DIVE SOVEREIGN OVERSIGHT' }).first()).toBeVisible();
    
    // Change name
    const nameInput = page.locator('span:has-text("Username") + input');
    await nameInput.fill('admin_test_renamed');
    await page.click('button:has-text("COMMIT PROFILE")');
    
    await expect(page.locator('text=Directives Committed Successfully')).toBeVisible();
  });

  test('Module: Automation Suite - Calibrate Profile', async ({ page }) => {
    await page.click('button:has-text("Automation Suite")');
    await expect(page.locator('h3:has-text("Automation Suite")')).toBeVisible();
    
    await page.click('button:has-text("Calibrate Profile") >> nth=0');
    await expect(page.locator('p').filter({ hasText: 'AUTOMATION CALIBRATION' }).first()).toBeVisible();
    
    // Change frequency
    const freqInput = page.locator('span:has-text("Action Frequency") + input');
    await freqInput.fill('45');
    
    await page.click('button:has-text("COMMIT PROTOCOL")');
    await expect(page.locator('text=Directives Committed Successfully')).toBeVisible();
  });

  test('Module: Battle Records - Data Rendering', async ({ page }) => {
    await page.click('button:has-text("Battle Records")');
    
    // We might not have logs in a fresh DB, but the table headers should be there
    await expect(page.locator('th:has-text("Engagement")')).toBeVisible();
    await expect(page.locator('th:has-text("Outcome")')).toBeVisible();
    await expect(page.locator('th:has-text("Credits Siphoned")')).toBeVisible();
  });
});
