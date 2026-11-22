// Browser check of the Themes editor in a real panel (checks.yml, job panel-smoke on Pelican rc.4): logs in, opens
// admin › Themes, clicks every tab, makes a change and saves it with Save, and opens the overview and My theme with a
// ready-made theme as the default. On a desktop (1440) and a phone (390), light and dark. Writes a screenshot per
// screen to --out. The last line says "All steps passed" or lists the failures.
// node tests/panel/browser.cjs --base http://127.0.0.1:8001 --out result [--core]
const fs = require('fs'), path = require('path');
const { chromium } = require('playwright');
const arg = (n, d) => { const i = process.argv.indexOf('--' + n); return i > 0 ? process.argv[i + 1] : d; };
const base = arg('base', 'http://127.0.0.1:8001'), out = arg('out', 'result');
const core = process.argv.includes('--core');
const EMAIL = 'browser-themes@example.com', PASSWORD = 'BrowserThemes123!'; // created by checks.yml
const TABS = core ? ['themes', 'colours', 'background', 'fonts', 'shapes'] : ['themes', 'colours', 'background', 'fonts'];
fs.mkdirSync(out, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const failures = [];
let page, stand;

const say = (step, ok, detail) => {
  console.log(`${ok ? 'PASS' : 'FAIL'} ${stand} ${step}${detail ? ' ' + JSON.stringify(detail) : ''}`);
  if (!ok) failures.push(`${stand} ${step}`);
};
const settle = async (ms = 600) => { await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {}); await sleep(ms); };
const goto = async (url) => { await page.goto(base + url, { waitUntil: 'domcontentloaded' }); await settle(); };
const shot = (name) => page.screenshot({ path: path.join(out, `${stand}-${name}.png`) }).catch(() => {});
const scrolls = () => page.evaluate(() => document.documentElement.scrollHeight - window.innerHeight);
// P-9: under the middle of a button lies the button itself, nothing on top of it.
const onTop = (locator) => locator.evaluate((el) => {
  const r = el.getBoundingClientRect();
  const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
  return !!hit && (hit === el || el.contains(hit));
}).catch(() => false);

async function login() {
  await goto('/login');
  await page.fill('#form\\.login', EMAIL); await page.fill('#form\\.password', PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForURL((u) => !u.pathname.includes('/login'), { timeout: 30000 }).catch(() => {});
  await settle();
  if (page.url().includes('/login')) throw new Error('login failed');
}

/** Whether a canvas has drawn something: any pixel that is not empty. */
async function drawn(selector) {
  return page.evaluate((sel) => {
    const c = document.querySelector(sel);
    if (!c || !c.width) return false;
    const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data;
    for (let i = 3; i < d.length; i += 16) if (d[i] > 0) return true;
    return false;
  }, selector).catch(() => false);
}

async function run(browser, viewport, scheme, saves) {
  stand = `${viewport.name}-${scheme}${core ? '' : '-pelican'}`;
  const phone = viewport.name === 'phone390';
  const context = await browser.newContext({ viewport, colorScheme: scheme, deviceScaleFactor: 1 });
  // Filament keeps light or dark in localStorage.
  await context.addInitScript((mode) => { try { localStorage.setItem('theme', mode); } catch (e) {} }, scheme);
  page = await context.newPage();
  page.on('pageerror', (e) => say('page error', false, { message: String(e.message).slice(0, 200) }));
  try {
    await login();
    await goto('/admin/simplify-themes');
    say('the editor opens', await page.locator('form.st').count() === 1, { url: page.url() });
    if (core && !phone) say('the page never scrolls (P-7)', await scrolls() <= 1, { overflow: await scrolls() });
    // TH-B3: the editor opens on the Themes tab, where a theme is picked.
    say('the editor opens on the Themes tab', (await page.locator('.st-tab').first().getAttribute('aria-selected').catch(() => null)) === 'true');
    // TH-B1: every tile shows its mini preview across the whole tile, not as a strip on its left.
    await page.waitForSelector('.st-tile', { timeout: 10000 }).catch(() => {});
    const tile = await page.evaluate(() => { const el = document.querySelector('.st-tile'); const mini = el && el.querySelector('.st-mini'); return el && mini ? [el.getBoundingClientRect().width, mini.getBoundingClientRect().width] : [0, 0]; }).catch((e) => [0, 0, String(e.message).slice(0, 160)]);
    say('a theme tile shows its preview across the tile', tile[0] > 0 && tile[1] >= tile[0] - 4, { tile: tile[0], preview: tile[1], error: tile[2] });
    // TH-B2: on a phone the preview is a sheet that stays out of sight until Live preview opens it.
    if (phone) say('the preview sheet is hidden until opened', !(await page.locator('.st-side').isVisible()));
    for (const tab of TABS) {
      const button = page.locator('.st-tab').nth(TABS.indexOf(tab));
      await button.scrollIntoViewIfNeeded().catch(() => {});
      say(`tab ${tab} is clickable`, await onTop(button));
      await button.click();
      await sleep(300);
      say(`tab ${tab} opens`, (await button.getAttribute('aria-selected')) === 'true');
      await shot(`editor-${tab}`);
    }
    if (phone) {
      await page.locator('.st-peek').click();
      await sleep(400);
      say('the preview opens as a sheet', await page.locator('.st-side.is-open').count() === 1);
      await shot('editor-preview');
      await page.locator('.st-close').click();
      await sleep(300);
    }
    const save = page.getByRole('button', { name: 'Save', exact: true }).first();
    say('Save is in the title bar and clickable', await save.count() === 1 && await onTop(save));
    if (saves) {
      // Nothing saves by itself (V57): a change shows "Changes made", Save keeps it, a reload shows it.
      await page.locator('.st-tab').first().click();
      const name = `Harbour ${Date.now() % 10000}`;
      await page.fill('#st-name', name);
      await sleep(400);
      say('a change shows "Changes made"', await page.locator(':text("Changes made"):visible').first().waitFor({ timeout: 5000 }).then(() => true).catch(() => false));
      await shot('editor-changed');
      await save.click();
      await page.locator(':text("Saved"):visible').first().waitFor({ timeout: 10000 }).catch(() => {});
      await goto('/admin/simplify-themes');
      say('Save keeps the change', (await page.inputValue('#st-name').catch(() => '')) === name);
      // The moving background: picking an effect moves the live preview, counts as a change and reaches the panel after Save.
      await page.locator('.st-tab').nth(TABS.indexOf('background')).click();
      // The gradient editor: a click on the bar adds a colour, Repeat makes stripes, the preview and the panel follow.
      await page.locator('[data-bg="gradient"]').click();
      await sleep(300);
      const before = await page.locator('.st-gstop').count();
      const bar = await page.locator('.st-gbar').boundingBox();
      if (bar) await page.mouse.click(bar.x + bar.width * .3, bar.y + bar.height / 2);
      await sleep(300);
      say('a click on the gradient bar adds a colour', await page.locator('.st-gstop').count() === before + 1, { before, after: await page.locator('.st-gstop').count() });
      await page.locator('.st-repeat').click();
      await sleep(300);
      say('a repeating gradient reaches the preview', (await page.locator('.st-pv').getAttribute('style') || '').includes('repeating-'));
      await shot('editor-gradient');
      await page.locator('.st-fxs .st-choice').nth(1).click();
      await sleep(800);
      say('an effect moves in the live preview', await drawn('.st-pv-fx'));
      say('an effect counts as a change', await page.locator(':text("Changes made"):visible').first().waitFor({ timeout: 5000 }).then(() => true).catch(() => false));
      await shot('editor-effect');
      await save.click();
      await page.locator(':text("Saved"):visible').first().waitFor({ timeout: 10000 }).catch(() => {});
    }
    // The ready-made default (checks.yml) on the pages everyone sees.
    await goto('/');
    say('the overview opens with the default theme', await page.locator('style#simplify-themes').count() === 1);
    await sleep(800);
    say('the effect moves behind the overview', await drawn('#simplify-themes-fx'));
    say('the panel wears the saved gradient', await page.evaluate(() => getComputedStyle(document.documentElement).backgroundImage.includes('gradient')));
    // Behind everything: the middle of the page is never the effect's canvas, so every click still lands.
    say('the effect never covers what people click', await page.evaluate(() => document.elementFromPoint(innerWidth / 2, innerHeight / 2)?.id !== 'simplify-themes-fx'));
    await shot('overview');
    await goto('/my-theme');
    say('My theme opens', await page.locator('input[type="radio"]').count() >= 2);
    await shot('my-theme');
  } catch (e) {
    say('run', false, { message: String(e.message).slice(0, 300) });
    await shot('error');
  }
  await context.close();
}

(async () => {
  const browser = await chromium.launch();
  let first = true;
  for (const viewport of [{ name: 'd1440', width: 1440, height: 900 }, { name: 'phone390', width: 390, height: 844 }]) {
    for (const scheme of ['dark', 'light']) {
      await run(browser, viewport, scheme, first);
      first = false;
    }
  }
  await browser.close();
  console.log(failures.length ? `Failed: ${failures.join(', ')}` : 'All steps passed');
  process.exit(failures.length ? 1 : 0);
})();
