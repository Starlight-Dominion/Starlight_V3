# High Command: Admin Suite E2E Testing Documentation

## Overview
The Admin Suite is a mission-critical component of Starlight Dominion. To ensure its technical integrity, we employ a rigorous End-to-End (E2E) testing suite powered by **Playwright**.

This suite simulates a live administrator interacting with every subsystem, verifying that state changes are correctly persisted and UI notifications are triggered. The tests guarantee that all 13 modular subsystems in the Command Center are fully functional.

## Prerequisites
- **Node.js** (v18+)
- **PHP 8.4+**
- **SQLite/MariaDB/MySQL** (depending on your `.env` configuration)
- **Playwright Browsers**: Run `npx playwright install`

## Test Structure
The primary tests are located in `tests/e2e/admin.spec.ts`.

### Helpers
- `tests/e2e/helpers/auth.ts`: Handles commander registration, CSRF token extraction, and session initialization via direct fetch requests to ensure speed and reliability.
- `tests/e2e/helpers/admin.ts`: Contains the `promoteToAdmin` utility which uses the PHP CLI to modify the database state, granting admin privileges to the test account.

## Running the Tests

### Local Execution
To run the full suite across all configured browsers:
```bash
npm run test:e2e
```

To run only the Admin Suite tests using Chromium in serialized mode (safest for database concurrency):
```bash
npx playwright test tests/e2e/admin.spec.ts --project=chromium --workers=1
```

To run with a visible browser (headed mode):
```bash
npx playwright test tests/e2e/admin.spec.ts --headed
```

---

## Exhaustive Subsystems Covered (13 Modules)

The `admin.spec.ts` suite verifies the following modules. Each test is hardened with network-aware synchronization (`page.waitForResponse`) and precise locators to prevent UI flakiness.

### 1. Command Overview
- **Action:** Navigates to the Command Overview.
- **Verification:** Asserts that core high-level telemetry metrics render correctly on the dashboard (e.g., "Total Sovereigns", "Active Sectors", "Total Wealth").

### 2. Global Mechanics
- **Action:** Selects the "Global Mechanics" module. Locates the global transmission input field.
- **Verification:** Enters a unique timestamped broadcast message, submits the form, waits for the `/admin/update-setting` API response, and verifies the `Committed Successfully` notification.

### 3. Battle Doctrine
- **Action:** Selects the "Battle Doctrine" module. Targets the "guard floor" calibration input.
- **Verification:** Modifies the baseline multiplier, submits the doctrine update, synchronizes with the API response, and validates the success notification.

### 4. A.I. Advisor Panel
- **Action:** Selects the "A.I. Advisor Panel" module. Locates the Dominion News Wire broadcast `textarea`.
- **Verification:** Injects a custom news broadcast, publishes the update, and waits for the API response. Asserts that the confirmation notification appears, ensuring news propagates to the Tactical Sidebar.

### 5. Armory Forge
- **Action:** Selects "Armory Forge" and clicks "Calibrate Asset" for the first military asset.
- **Verification:** Switches to the "Prerequisites" tab, increments the "Requisition Cost", and commits the asset. Validates the `/admin/update-armory-item` endpoint response and UI success banner.

### 6. War Room (Units)
- **Action:** Selects "War Room (Units)" and opens the "Tactical Calibration" inspector.
- **Verification:** Navigates to the "Tactical Yield" tab, increments the "Offensive Power" stat for a specific unit class, and commits the doctrine. Asserts the successful API exchange and UI feedback.

### 7. Structural Engineering
- **Action:** Selects "Structural Engineering" and accesses the "Structural Calibration" modal.
- **Verification:** Navigates to the "Evolution Matrix", modifies the "Integrity (HP)" of a specific rank/level, and saves the structure level. Validates the `/admin/update-structure-level` API call and UI success.

### 8. Evolutionary Strains
- **Action:** Selects "Evolutionary Strains" and clicks "Genetic Calibration" for a specific race.
- **Verification:** Switches to the "Neural Bonuses" tab, updates the "Neural Multiplier" decimal value, and commits the genetics. Validates the `/admin/update-race` endpoint and UI success state.

### 9. Sovereign Oversight
- **Action:** Selects "Sovereign Oversight" and performs a search for the active test commander's exact username.
- **Verification:** Waits for the search API to return, selects the precise user from the list, and opens the "Sovereign Inspector". Modifies the commander's handle (Username) and commits the profile update. Validates multiple endpoints including `/admin/search`, `/admin/kingdom/profile`, and `/admin/update-kingdom`.

### 10. Neural API Gate
- **Action:** Selects the "Neural API Gate" module.
- **Verification:** Asserts that the "Key Matrix" tab renders correctly and the data table (containing the "Commander" column) is visible, ensuring API keys are loaded and displayed.

### 11. Automation Suite
- **Action:** Selects the "Automation Suite" and clicks "Calibrate Profile" on an active bot profile.
- **Verification:** Locates the "Action Frequency" input, adjusts the cycle time (minutes), and commits the protocol. Validates the `/admin/automation/profiles/update` endpoint and UI success.

### 12. Documentation
- **Action:** Selects the "Documentation" module.
- **Verification:** Locates the Markdown textarea containing the "Official Rules", appends an E2E test footer to the existing content, and commits the changes. Validates the setting update and UI notification.

### 13. Battle Records
- **Action:** Selects the "Battle Records" module.
- **Verification:** Asserts that the battle telemetry table headers ("Engagement", "Outcome", "Credits Siphoned") are properly rendered, ensuring combat logs can be viewed.

---

## Adding New Tests
When expanding the Admin Suite and adding new administrative modules, adhere to the following E2E integration protocol:
1. **Identify Target:** Identify the core "Happy Path" (Create/Update/Delete).
2. **Add Test Block:** Add a new `test('Module: [Name] - [Action]', async ({ page }) => { ... })` block to `admin.spec.ts`.
3. **Synchronize:** Use `Promise.all` with `page.waitForResponse` to synchronize the UI interaction with the expected JSON API response. This prevents race conditions and flaky tests.
4. **Assert:** Conclude the test by asserting `await expect(page.locator('div').filter({ hasText: /Committed/i }).first()).toBeVisible();`.
