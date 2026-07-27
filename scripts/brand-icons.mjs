// Single source of truth for the brand mark. Emits every icon artifact:
//   public/favicon.svg, public/favicon.ico, public/apple-touch-icon.png,
//   public/icon-192.png, public/icon-512.png
// Usage: node scripts/brand-icons.mjs
// Env: CHROMIUM (path to a Chromium binary, when Playwright's own is unavailable).
//
// The mark is a review bubble (RTL: tail on the bottom right) holding three
// score bars that rise with the reading direction — the same "product UI as
// graphic" idea as the social card in scripts/og-image.html.
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PUBLIC = path.join(ROOT, 'public');

/** The bubble outline, authored left-tailed and mirrored into RTL on render. */
const BUBBLE = 'M21 13h22a10 10 0 0 1 10 10v13a10 10 0 0 1-10 10H33l-10.5 8.5a1.4 1.4 0 0 1-2.3-1.1V46H21a10 10 0 0 1-10-10V23a10 10 0 0 1 10-10Z';

const GRADIENT = `<linearGradient id="brand-mark-gradient" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0EA5E9"/>
      <stop offset="0.55" stop-color="#3B82F6"/>
      <stop offset="1" stop-color="#6366F1"/>
    </linearGradient>`;

const GLYPH = `<path transform="translate(64 0) scale(-1 1)" fill="#FFFFFF" d="${BUBBLE}"/>
  <rect x="20" y="20" width="6.5" height="20" rx="3.25" fill="#2563EB"/>
  <rect x="28.75" y="26" width="6.5" height="14" rx="3.25" fill="#2563EB" opacity="0.8"/>
  <rect x="37.5" y="30.5" width="6.5" height="9.5" rx="3.25" fill="#2563EB" opacity="0.62"/>`;

/**
 * @param {{size: number, radius: number, inset: number}} options
 *   radius — tile corner radius on the 64 grid (0 for platform-masked icons).
 *   inset  — glyph scale about the centre; maskable icons keep the glyph inside
 *            the safe circle while the tile stays full bleed.
 */
function markup({ size, radius, inset }) {
    const glyph = inset === 1
        ? GLYPH
        : `<g transform="translate(${(32 * (1 - inset)).toFixed(3)} ${(32 * (1 - inset)).toFixed(3)}) scale(${inset})">${GLYPH}</g>`;

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 64 64" role="img" aria-label="تقييم التدريب">
  <defs>
    ${GRADIENT}
  </defs>
  <rect width="64" height="64" rx="${radius}" fill="url(#brand-mark-gradient)"/>
  ${glyph}
</svg>
`;
}

/** Assembles a PNG-payload .ico. Every browser since IE11 reads this form. */
function buildIco(pngs) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0);
    header.writeUInt16LE(1, 2);
    header.writeUInt16LE(pngs.length, 4);

    let offset = 6 + pngs.length * 16;
    const entries = pngs.map(({ size, data }) => {
        const entry = Buffer.alloc(16);
        entry.writeUInt8(size >= 256 ? 0 : size, 0);
        entry.writeUInt8(size >= 256 ? 0 : size, 1);
        entry.writeUInt8(0, 2);
        entry.writeUInt8(0, 3);
        entry.writeUInt16LE(1, 4);
        entry.writeUInt16LE(32, 6);
        entry.writeUInt32LE(data.length, 8);
        entry.writeUInt32LE(offset, 12);
        offset += data.length;

        return entry;
    });

    return Buffer.concat([header, ...entries, ...pngs.map(({ data }) => data)]);
}

const browser = await chromium.launch({ executablePath: process.env.CHROMIUM || undefined });
const context = await browser.newContext({ viewport: { width: 512, height: 512 }, deviceScaleFactor: 2 });
const page = await context.newPage();

async function raster({ size, radius, inset }) {
    await page.setViewportSize({ width: size, height: size });
    await page.setContent(
        `<style>*{margin:0;padding:0}html,body{width:${size}px;height:${size}px;overflow:hidden;background:transparent}</style>${markup({ size, radius, inset })}`,
        { waitUntil: 'load' }
    );

    return page.screenshot({ type: 'png', scale: 'css', omitBackground: true });
}

/** Rounded tile: the mark stands on its own (favicon, browser tab, nav). */
const rounded = { radius: 16, inset: 1 };
/** Square full-bleed tile: the platform rounds and masks it (PWA, iOS). */
const masked = { radius: 0, inset: 0.82 };

const written = [];

fs.writeFileSync(path.join(PUBLIC, 'favicon.svg'), markup({ size: 512, ...rounded }));
written.push('favicon.svg');

for (const [file, size, variant] of [
    ['apple-touch-icon.png', 180, masked],
    ['icon-192.png', 192, masked],
    ['icon-512.png', 512, masked],
]) {
    fs.writeFileSync(path.join(PUBLIC, file), await raster({ size, ...variant }));
    written.push(`${file} (${size}x${size})`);
}

const icoSizes = [16, 32, 48];
const icoPngs = [];
for (const size of icoSizes) {
    icoPngs.push({ size, data: await raster({ size, ...rounded }) });
}
fs.writeFileSync(path.join(PUBLIC, 'favicon.ico'), buildIco(icoPngs));
written.push(`favicon.ico (${icoSizes.join(', ')})`);

await browser.close();

console.log(written.map((line) => `Wrote public/${line}`).join('\n'));
