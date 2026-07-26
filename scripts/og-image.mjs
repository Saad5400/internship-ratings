// Renders scripts/og-image.html to public/og-image.png (1200x630) with Playwright.
// Usage: node scripts/og-image.mjs
// Env: OUT (default public/og-image.png), FONT_DIR (dir holding the IBM Plex Sans
//      Arabic woff2 subsets; downloaded from npm into a temp dir when absent),
//      CHROMIUM (path to a Chromium binary, when Playwright's own is unavailable).
//
// The page is laid out at 1200x630 CSS pixels and rasterised at deviceScaleFactor
// 2, then captured with scale: 'css' so the PNG is exactly 1200x630 downsampled
// from a 2x render — noticeably crisper than a straight 1x capture.
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SOURCE = path.join(ROOT, 'scripts/og-image.html');
const OUT = process.env.OUT || path.join(ROOT, 'public/og-image.png');

const FONT_PACKAGE = '@fontsource/ibm-plex-sans-arabic';
const WEIGHTS = [400, 500, 600, 700];
const SUBSETS = ['arabic', 'latin'];

/**
 * The font is not vendored: fetch the npm tarball into a temp dir on first run.
 * Set FONT_DIR to reuse an existing checkout and skip the download.
 */
function resolveFontDir() {
    if (process.env.FONT_DIR) {
        return process.env.FONT_DIR;
    }

    const cache = path.join(os.tmpdir(), 'og-image-fonts');
    const files = path.join(cache, 'package/files');
    if (fs.existsSync(files)) {
        return files;
    }

    fs.mkdirSync(cache, { recursive: true });
    const packed = execFileSync('npm', ['pack', FONT_PACKAGE, '--silent'], { cwd: cache, encoding: 'utf8' }).trim().split('\n').pop();
    execFileSync('tar', ['xzf', packed], { cwd: cache });

    return files;
}

function buildFontCss(dir) {
    return WEIGHTS.flatMap((weight) => SUBSETS.map((subset) => {
        const file = path.join(dir, `ibm-plex-sans-arabic-${subset}-${weight}-normal.woff2`);
        const data = fs.readFileSync(file).toString('base64');

        return `@font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:${weight};font-display:block;src:url(data:font/woff2;base64,${data}) format('woff2');}`;
    })).join('\n');
}

const html = fs.readFileSync(SOURCE, 'utf8').replace('__FONT_CSS__', buildFontCss(resolveFontDir()));

const browser = await chromium.launch({ executablePath: process.env.CHROMIUM || undefined });
const context = await browser.newContext({
    viewport: { width: 1200, height: 630 },
    deviceScaleFactor: 2,
});
const page = await context.newPage();
await page.setContent(html, { waitUntil: 'load' });
await page.evaluate(async () => {
    await document.fonts.ready;
});
await page.screenshot({ path: OUT, type: 'png', scale: 'css' });

await browser.close();

console.log(`Wrote ${OUT}`);
