import { expect, test } from '@playwright/test';
import { makeCommanderCredentials, registerAndLoginCommander } from './helpers/auth';
import { promoteToAdmin } from './helpers/admin';

test.describe('Admin Suite Exhaustive Validation', () => {
  test.slow();

  test.beforeEach(async ({ page }) => {
    const creds = makeCommanderCredentials('admin_test');
    await registerAndLoginCommander(page, creds);
    await promoteToAdmin(creds.username);
    
    (page as any).creds = creds;

    await page.goto('/dashboard');
    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.getByRole('heading', { name: /Command Center/i })).toBeVisible();
  });

  test('Module: Command Overview renders correctly', async ({ page }) => {
    await expect(page.locator('text=Total Sovereigns')).toBeVisible();
    await expect(page.locator('text=Active Sectors')).toBeVisible();
  });

  test('Module: Global Mechanics - Update setting', async ({ page }) => {
    await page.click('button:has-text("Global Mechanics")');
    const input = page.locator('input[placeholder="Enter global transmission..."]');
    await input.waitFor({ state: 'visible' });
    await input.fill('E2E TEST BROADCAST ' + Date.now());
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-setting') && res.status() === 200),
      page.click('button:has-text("Transmit")')
    ]);
  });

  test('Module: Battle Doctrine - Multiplier Update', async ({ page }) => {
    await page.click('button:has-text("Battle Doctrine")');
    const input = page.locator('div:has(span:has-text("guard floor")) input').first();
    await input.waitFor({ state: 'visible', timeout: 15000 });
    await input.fill('25000'); 
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-setting') && res.status() === 200),
      page.click('button:has-text("COMMIT") >> nth=0')
    ]);
  });

  test('Module: A.I. Advisor Panel - News Update', async ({ page }) => {
    await page.click('button:has-text("A.I. Advisor Panel")');
    const newsInput = page.locator('textarea[placeholder="Enter current sector news..."]');
    await newsInput.waitFor({ state: 'visible', timeout: 15000 });
    await newsInput.fill('E2E TEST NEWS BROADCAST ' + Date.now());
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-setting') && res.status() === 200),
      page.click('button:has-text("PUBLISH NEWS")')
    ]);
  });

  test('Module: Armory Forge - Asset Calibration', async ({ page }) => {
    await page.click('button:has-text("Armory Forge")');
    const inspectBtn = page.locator('button:has-text("Calibrate Asset")').first();
    await inspectBtn.waitFor({ state: 'visible' });
    await inspectBtn.click();
    
    await page.click('button:has-text("Prerequisites")');
    
    const costInput = page.locator('div:has(span:has-text("Requisition Cost")) input').first();
    await costInput.waitFor({ state: 'visible' });
    const oldVal = await costInput.inputValue();
    await costInput.fill((parseInt(oldVal) + 100).toString());
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-armory-item') && res.status() === 200),
      page.click('button:has-text("COMMIT ASSET")')
    ]);
  });

  test('Module: War Room (Units) - Tactical Calibration', async ({ page }) => {
    await page.click('button:has-text("War Room (Units)")');
    const inspectBtn = page.locator('button:has-text("Tactical Calibration")').first();
    await inspectBtn.waitFor({ state: 'visible' });
    await inspectBtn.click();
    
    await page.click('button:has-text("Tactical Yield")');
    
    const atkInput = page.locator('div:has(span:has-text("Offensive Power")) input').first();
    await atkInput.waitFor({ state: 'visible' });
    const oldVal = await atkInput.inputValue();
    await atkInput.fill((parseInt(oldVal) + 1).toString());
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-unit') && res.status() === 200),
      page.click('button:has-text("COMMIT DOCTRINE")')
    ]);
  });

  test('Module: Structural Engineering - Modify Ranks', async ({ page }) => {
    await page.click('button:has-text("Structural Engineering")');
    const inspectBtn = page.locator('button:has-text("Structural Calibration")').first();
    await inspectBtn.waitFor({ state: 'visible' });
    await inspectBtn.click();
    
    await page.click('button:has-text("Evolution Matrix")');
    
    const hpInput = page.locator('div:has(span:has-text("Integrity (HP)")) input').first();
    await hpInput.waitFor({ state: 'visible' });
    await hpInput.fill('1500');
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-structure-level') && res.status() === 200),
      page.locator('button').filter({ hasText: 'SAVE' }).first().click()
    ]);
  });

  test('Module: Evolutionary Strains - Genetic Calibration', async ({ page }) => {
    await page.click('button:has-text("Evolutionary Strains")');
    const item = page.locator('button:has-text("Genetic Calibration")').first();
    await item.waitFor({ state: 'visible', timeout: 15000 });
    await item.click();
    
    await page.click('button:has-text("Neural Bonuses")');
    
    const multInput = page.locator('div:has(span:has-text("Neural Multiplier")) input').first();
    await multInput.waitFor({ state: 'visible' });
    await multInput.fill('1.05');
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-race') && res.status() === 200),
      page.click('button:has-text("COMMIT GENETICS")')
    ]);
  });

  test('Module: Sovereign Oversight - Search and Inspect', async ({ page }) => {
    const creds = (page as any).creds;
    await page.click('button:has-text("Sovereign Oversight")');
    const searchInput = page.locator('input[placeholder="Search kingdoms..."]');
    await searchInput.waitFor({ state: 'visible', timeout: 15000 });
    
    await searchInput.fill(creds.username);
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/search') && res.status() === 200),
      page.click('button:has-text("Search")')
    ]);
    
    const result = page.locator('.bg-dark-translucent').filter({ hasText: `CDR: ${creds.username.toUpperCase()}` }).first();
    await result.waitFor({ state: 'visible', timeout: 15000 });
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/kingdom/profile') && res.status() === 200),
      result.locator('button:has-text("Sovereign Inspector")').click({ force: true })
    ]);
    
    const nameInput = page.locator('div:has(span:has-text("Username")) input').first();
    await nameInput.waitFor({ state: 'visible' });
    await nameInput.fill(creds.username + '_renamed');
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-kingdom') && res.status() === 200),
      page.click('button:has-text("COMMIT PROFILE")')
    ]);
  });

  test('Module: Neural API Gate - Key Matrix', async ({ page }) => {
    await page.click('button:has-text("Neural API Gate")');
    await expect(page.locator('button:has-text("Key Matrix")')).toBeVisible();
    await expect(page.locator('th:has-text("Commander")')).toBeVisible();
  });

  test('Module: Automation Suite - Calibrate Profile', async ({ page }) => {
    await page.click('button:has-text("Automation Suite")');
    const inspectBtn = page.locator('button:has-text("Calibrate Profile")').first();
    await inspectBtn.waitFor({ state: 'visible' });
    await inspectBtn.click();
    
    const freqInput = page.locator('div:has(span:has-text("Action Frequency")) input').first();
    await freqInput.waitFor({ state: 'visible' });
    await freqInput.fill('45');
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/automation/profiles/update') && res.status() === 200),
      page.click('button:has-text("COMMIT PROTOCOL")')
    ]);
  });

  test('Module: Documentation - Protocols Update', async ({ page }) => {
    await page.click('button:has-text("Documentation")');
    const docInput = page.locator('textarea[placeholder="# Neural Protocols..."]');
    await docInput.waitFor({ state: 'visible', timeout: 15000 });
    const oldVal = await docInput.inputValue();
    await docInput.fill(oldVal + '\n\n## E2E TEST FOOTER');
    
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/admin/update-setting') && res.status() === 200),
      page.click('button:has-text("COMMIT CHANGES")')
    ]);
  });

  test('Module: Battle Records - Data Rendering', async ({ page }) => {
    await page.click('button:has-text("Battle Records")');
    await expect(page.locator('th:has-text("Engagement")')).toBeVisible();
    await expect(page.locator('th:has-text("Outcome")')).toBeVisible();
  });
});
