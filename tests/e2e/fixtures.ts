import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

import { expect, test as base } from '@playwright/test';

const shouldCollectCoverage = process.env.PLAYWRIGHT_COLLECT_COVERAGE === '1';

function sanitizeSegment(value: string): string {
  return value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 64) || 'test';
}

async function writeCoverageBlob(data: unknown, name: string): Promise<void> {
  const outputDir = path.join(process.cwd(), '.nyc_output');
  await mkdir(outputDir, { recursive: true });
  const outputPath = path.join(outputDir, `${name}.json`);
  await writeFile(outputPath, JSON.stringify(data), 'utf8');
}

export const test = base.extend({
  page: async ({ page }, use, testInfo) => {
    await use(page);

    if (!shouldCollectCoverage) {
      return;
    }

    let coverage: unknown = null;
    try {
      coverage = await page.evaluate(() => {
        return (window as { __coverage__?: unknown }).__coverage__ ?? null;
      });
    } catch {
      coverage = null;
    }

    if (!coverage || typeof coverage !== 'object') {
      return;
    }

    const blobName = [
      sanitizeSegment(testInfo.project.name),
      `w${testInfo.workerIndex}`,
      `r${testInfo.retry}`,
      sanitizeSegment(testInfo.title),
      Date.now().toString(),
      randomUUID().slice(0, 8),
    ].join('-');

    await writeCoverageBlob(coverage, blobName);
  },
});

export { expect };
