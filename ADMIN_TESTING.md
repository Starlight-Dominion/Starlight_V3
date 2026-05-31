# High Command: Admin Suite E2E Testing Documentation

## Overview
The Admin Suite is a mission-critical component of Starlight Dominion. To ensure its technical integrity, we employ a rigorous End-to-End (E2E) testing suite powered by **Playwright**.

This suite simulates a live administrator interacting with every subsystem, verifying that state changes are correctly persisted and UI notifications are triggered.

## Prerequisites
- **Node.js** (v18+)
- **PHP 8.4+**
- **SQLite/MariaDB/MySQL** (depending on your `.env` configuration)
- **Playwright Browsers**: Run `npx playwright install`

## Test Structure
The tests are located in `tests/e2e/admin.spec.ts`.

### Helpers
- `tests/e2e/helpers/auth.ts`: Handles commander registration and session initialization.
- `tests/e2e/helpers/admin.ts`: Contains the `promoteToAdmin` utility which uses the PHP CLI to modify the database state, granting admin privileges to the test account.

## Running the Tests

### Local Execution
To run the full suite across all configured browsers:
```bash
npm run test:e2e
```

To run only the Admin Suite tests:
```bash
npx playwright test tests/e2e/admin.spec.ts
```

To run with a visible browser (headed mode):
```bash
npx playwright test tests/e2e/admin.spec.ts --headed
```

### CI/CD Integration
In the GitHub Actions workflow (`ci.yml`), the tests are executed in a headless environment. Artifacts (screenshots, videos, traces) are retained on failure for debugging.

## Subsystems Covered
1.  **Command Overview**: Verifies statistical rendering.
2.  **Global Mechanics**: Tests real-time parameter updates.
3.  **War Room (Units)**: Validates unit stat calibration via modals.
4.  **Structural Engineering**: Checks rank/evolution matrix synchronization.
5.  **Sovereign Oversight**: Tests sector search and identity modification.
6.  **Automation Suite**: Verifies bot profile calibration.
7.  **Battle Records**: Ensures data mapping and relational integrity for combat telemetry.

## Adding New Tests
When adding a new administrative module:
1. Identify the core "Happy Path" (Create/Update/Delete).
2. Add a new `test()` block to `admin.spec.ts`.
3. Use `page.locator()` with high-signal text or ARIA labels.
4. Assert that the `Directives Committed Successfully` notification appears.
