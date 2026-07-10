// Headless screenshot pipeline for the Filament admin panel.
// Usage: node scripts/shots.mjs
// Env: BASE (default http://127.0.0.1:8199), EMAIL, PASSWORD, OUT dir, COMPANY (id), RATING (id)
import { chromium } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://127.0.0.1:8199';
const EMAIL = process.env.EMAIL || 'admin@example.com';
const PASSWORD = process.env.PASSWORD || 'password';
const OUT = process.env.OUT || '/tmp/claude-1000/-home-saad-code-internship-ratings/6aaa9392-0864-4869-a8a1-5ccbdc21cb1b/scratchpad/shots';

fs.mkdirSync(OUT, { recursive: true });

const shots = [
  { name: '01-dashboard', path: '/admin' },
  { name: '02-companies', path: '/admin/companies' },
  { name: '03-ratings', path: '/admin/ratings' },
  { name: '04-users', path: '/admin/users' },
];
if (process.env.COMPANY) { shots.push({ name: '05-company-view', path: `/admin/companies/${process.env.COMPANY}` }); }
if (process.env.RATING) { shots.push({ name: '06-rating-view', path: `/admin/ratings/${process.env.RATING}` }); }

const browser = await chromium.launch();
const results = [];

async function capture(ctx, tag) {
  const page = await ctx.newPage();
  // Login
  await page.goto(`${BASE}/admin/login`, { waitUntil: 'networkidle' });
  const email = page.locator('#data\\.email, input[type="email"]').first();
  const pass = page.locator('#data\\.password, input[type="password"]').first();
  await email.fill(EMAIL);
  await pass.fill(PASSWORD);
  await Promise.all([
    page.waitForURL('**/admin**', { timeout: 15000 }).catch(() => {}),
    page.locator('button[type="submit"]').first().click(),
  ]);
  await page.waitForTimeout(1500);

  for (const s of shots) {
    try {
      await page.goto(`${BASE}${s.path}`, { waitUntil: 'networkidle', timeout: 20000 });
      await page.waitForTimeout(1200);
      const file = `${OUT}/${tag}-${s.name}.png`;
      await page.screenshot({ path: file, fullPage: true });
      results.push(`OK  ${file}`);
    } catch (e) {
      results.push(`ERR ${s.name} (${tag}): ${e.message.split('\n')[0]}`);
    }
  }
  await page.close();
}

const desktop = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
await capture(desktop, 'desktop');

const mobile = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true });
// Only dashboard + companies + ratings on mobile
shots.length = Math.min(shots.length, 4);
await capture(mobile, 'mobile');

await browser.close();
console.log(results.join('\n'));
