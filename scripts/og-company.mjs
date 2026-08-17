// Renders a per-company Open Graph card (1200x630 PNG) with the Takumi
// renderer — no headless browser, unlike the static scripts/og-image.mjs.
//
// App\Support\CompanyOgImage shells out to this on a cache miss: it writes a
// JSON payload to stdin and reads the PNG back from stdout. Run it by hand for
// a preview with:
//   echo '{"name":"جهة التدريب","typeLabel":"خاص","score":"4.5","scoreTier":"good","countLabel":"٢٤ تقييمًا","host":"example.com"}' \
//     | node scripts/og-company.mjs > /tmp/card.png
//
// Env: FONT_DIR (dir holding the IBM Plex Sans Arabic woff2 subsets; the npm
//      tarball is fetched into a temp dir when absent, mirroring og-image.mjs).
import { Renderer } from '@takumi-rs/core';
import { fromHtml } from '@takumi-rs/helpers/html';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const WIDTH = 1200;
const HEIGHT = 630;

const FONT_PACKAGE = '@fontsource/ibm-plex-sans-arabic';
const WEIGHTS = [400, 500, 600, 700];
const SUBSETS = ['arabic', 'latin'];

/**
 * The font is not vendored: fetch the npm tarball into a temp dir on first run
 * and reuse it afterwards. Set FONT_DIR to point at an existing checkout and
 * skip the download entirely. Identical strategy to scripts/og-image.mjs so the
 * two share the same cache directory shape.
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

function readPayload() {
    const raw = fs.readFileSync(0, 'utf8').trim();

    return raw === '' ? {} : JSON.parse(raw);
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
}

// The brand mark, inlined from resources/views/components/app-logo-icon.blade.php.
// Takumi has no inline-<svg> parser, so it rides in as a data-URI <img>.
const MARK = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0EA5E9"/><stop offset="0.55" stop-color="#3B82F6"/><stop offset="1" stop-color="#6366F1"/></linearGradient></defs><rect width="64" height="64" rx="16" fill="url(#g)"/><path transform="translate(64 0) scale(-1 1)" fill="#FFFFFF" d="M21 13h22a10 10 0 0 1 10 10v13a10 10 0 0 1-10 10H33l-10.5 8.5a1.4 1.4 0 0 1-2.3-1.1V46H21a10 10 0 0 1-10-10V23a10 10 0 0 1 10-10Z"/><rect x="20" y="20" width="6.5" height="20" rx="3.25" fill="#2563EB"/><rect x="28.75" y="26" width="6.5" height="14" rx="3.25" fill="#2563EB" opacity="0.8"/><rect x="37.5" y="30.5" width="6.5" height="9.5" rx="3.25" fill="#2563EB" opacity="0.62"/></svg>`;
const MARK_URI = `data:image/svg+xml;base64,${Buffer.from(MARK).toString('base64')}`;

/**
 * Score chip palette, mirroring the tiers in the overall-score Blade component
 * (>= 4 emerald, >= 3 amber, else rose) so a card matches the page it links to.
 */
const TIERS = {
    good: { bg: '#ecfdf5', ring: 'rgba(5,150,105,.18)', num: '#047857', suffix: '#10b981' },
    ok: { bg: '#fffbeb', ring: 'rgba(217,119,6,.20)', num: '#b45309', suffix: '#f59e0b' },
    low: { bg: '#fff1f2', ring: 'rgba(225,29,72,.18)', num: '#be123c', suffix: '#fb7185' },
};

function scoreBlock({ score, scoreTier }) {
    if (!score) {
        return `<div style="display:flex;align-items:center;justify-content:center;min-width:120px;padding:18px 26px;border-radius:20px;background:#f1f5f9;box-shadow:inset 0 0 0 1px #e2e8f0"><span style="font-size:26px;font-weight:600;color:#64748b">جديدة</span></div>`;
    }

    const tier = TIERS[scoreTier] ?? TIERS.ok;

    return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:150px;padding:22px 30px;border-radius:24px;background:${tier.bg};box-shadow:inset 0 0 0 1px ${tier.ring}">
        <span style="direction:ltr;font-size:72px;font-weight:700;line-height:1;color:${tier.num}">${escapeHtml(score)}</span>
        <span style="direction:ltr;margin-top:6px;font-size:18px;font-weight:600;letter-spacing:.5px;color:${tier.suffix}">/ 5</span>
    </div>`;
}

function chip(label) {
    return `<span style="display:flex;align-items:center;padding:11px 20px;border-radius:9999px;background:#ffffff;box-shadow:inset 0 0 0 1px #e2e8f0;font-size:22px;font-weight:600;color:#334155">${escapeHtml(label)}</span>`;
}

function buildHtml(payload) {
    const name = escapeHtml(payload.name ?? '');
    const meta = [payload.typeLabel, payload.countLabel].filter(Boolean).map(chip).join('');
    const host = payload.host
        ? `<span style="direction:ltr;font-size:24px;font-weight:600;color:#94a3b8">${escapeHtml(payload.host)}</span>`
        : '';

    // A single flex column, RTL. Header brand lockup, the company block in the
    // middle (name + score side by side), and a muted footer with the domain.
    return `<div style="width:${WIDTH}px;height:${HEIGHT}px;display:flex;flex-direction:column;direction:rtl;font-family:'IBM Plex Sans Arabic';background:radial-gradient(1100px 560px at 82% -12%, #dbeafe, transparent 70%), radial-gradient(900px 520px at -8% 120%, #e0e7ff, transparent 70%), linear-gradient(180deg,#f8fafc,#eef2f7)">
        <div style="height:8px;background:linear-gradient(90deg,#0ea5e9,#3b82f6 45%,#6366f1)"></div>
        <div style="display:flex;align-items:center;padding:48px 72px 0">
            <img src="${MARK_URI}" style="width:60px;height:60px" />
            <span style="margin-right:18px;font-size:30px;font-weight:700;color:#0f172a">تقييم التدريب</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex:1;padding:32px 72px;gap:48px">
            <div style="display:flex;flex-direction:column;flex:1">
                <span style="font-size:66px;font-weight:700;line-height:1.18;color:#0f172a">${name}</span>
                <div style="display:flex;align-items:center;gap:14px;margin-top:28px">${meta}</div>
            </div>
            ${scoreBlock(payload)}
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0 72px 44px">
            <span style="font-size:24px;font-weight:600;color:#64748b">جهات التدريب التعاوني والصيفي — بتقييمات متدربين حقيقيين</span>
            ${host}
        </div>
    </div>`;
}

const payload = readPayload();

const renderer = new Renderer();
const fontDir = resolveFontDir();
for (const weight of WEIGHTS) {
    for (const subset of SUBSETS) {
        await renderer.registerFont(fs.readFileSync(path.join(fontDir, `ibm-plex-sans-arabic-${subset}-${weight}-normal.woff2`)));
    }
}

const { node, stylesheets } = fromHtml(buildHtml(payload));
const png = await renderer.render(node, { width: WIDTH, height: HEIGHT, format: 'png', stylesheets, lang: 'ar' });

process.stdout.write(png);
