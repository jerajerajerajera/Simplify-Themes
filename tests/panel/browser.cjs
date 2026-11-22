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

// The box that scrolls around the editor (with Simplify: the Admin page's card), scrolled to its end.
const scrollEditorToEnd = () => page.evaluate(() => {
  let el = document.querySelector('form.st');
  while (el && el !== document.documentElement && !(el.scrollHeight > el.clientHeight + 1 && /auto|scroll/.test(getComputedStyle(el).overflowY))) el = el.parentElement;
  if (el) el.scrollTop = el.scrollHeight;
  return el ? el.className : null;
});
// What paints right behind a piece of text: the first box around it with a colour or picture of its own.
const behind = (selector) => page.evaluate((sel) => {
  for (let el = document.querySelector(sel); el; el = el.parentElement) {
    const cs = getComputedStyle(el), a = cs.backgroundColor.match(/[\d.]+/g);
    if (cs.backgroundImage !== 'none' || (a && (a.length < 4 || +a[3] > .5))) return { tag: el.tagName, cls: String(el.className).slice(0, 60), bg: cs.backgroundColor, image: cs.backgroundImage !== 'none' };
  }
  return null;
}, selector).catch(() => null);
const stops = () => page.evaluate(() => [...document.querySelectorAll('.st-gstop')].map((el) => parseFloat(el.style.left)));

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
    const tile = await page.evaluate(() => { const el = document.querySelector('.st-tile'); const mini = el && [...el.querySelectorAll('.st-mini')].find((m) => m.offsetParent !== null); return el && mini ? [el.getBoundingClientRect().width, mini.getBoundingClientRect().width] : [0, 0]; }).catch((e) => [0, 0, String(e.message).slice(0, 160)]);
    say('a theme tile shows its preview across the tile', tile[0] > 0 && tile[1] >= tile[0] - 4, { tile: tile[0], preview: tile[1], error: tile[2] });
    // TH-B10: the tiles show the theme in the panel's own mode: dark tiles in dark mode.
    const mini = await page.evaluate(() => { const m = [...document.querySelectorAll('.st-tile .st-mini')].find((el) => el.offsetParent !== null); const c = m ? getComputedStyle(m).backgroundColor.match(/\d+/g).map(Number) : [255, 255, 255]; return (c[0] * .3 + c[1] * .59 + c[2] * .11) / 255; });
    say('a theme tile shows the theme in the panel\'s mode', scheme === 'dark' ? mini < .5 : mini >= .5, { lightness: Math.round(mini * 100) / 100 });
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
      // Light and dark each keep their own background: plain in the other mode leaves this one's gradient.
      const here = await page.locator('[data-bgmode][aria-pressed="true"]').getAttribute('data-bgmode');
      const there = here === 'dark' ? 'light' : 'dark';
      await page.locator(`[data-bgmode="${there}"]`).click();
      await page.locator('[data-bg="plain"]').click();
      await page.locator(`[data-bgmode="${here}"]`).click();
      await sleep(200);
      say('light and dark keep their own background', (await page.locator('[data-bg="gradient"]').getAttribute('aria-pressed')) === 'true');
      await page.locator(`[data-bgmode="${there}"]`).click();
      await page.locator('[data-bg="gradient"]').click();
      await page.locator(`[data-bgmode="${here}"]`).click();
      await sleep(200);
      // TH-B9: Repeat on and off again before anything else changed puts the colours back where they were.
      const placed = await stops();
      await page.locator('.st-repeat').click();
      await sleep(200);
      await page.locator('.st-repeat').click();
      await sleep(200);
      say('Repeat off puts the colours back', JSON.stringify(await stops()) === JSON.stringify(placed), { before: placed, after: await stops() });
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
      // TH-B13: uploading a picture keeps the editor where it was: same tab, and the change still to be saved.
      await settle();
      await page.evaluate(() => { window.stBefore = document.querySelector('form.st'); });
      const png = await page.screenshot({ clip: { x: 0, y: 0, width: 96, height: 64 } });
      await page.locator('.st-upload-image, [wire\\:click*="uploadImage"]').first().click();
      await page.locator('.fi-modal input[type="file"]').first().waitFor({ state: 'attached', timeout: 8000 }).catch(() => {});
      await page.locator('.fi-modal input[type="file"]').first().setInputFiles({ name: 'harbour.png', mimeType: 'image/png', buffer: png }).catch(() => {});
      await settle(1500);
      await page.locator('.fi-modal-footer-actions button[type="submit"]:visible, .fi-modal-footer-actions button:visible').first().click().catch(() => {});
      await settle(1000);
      const after = await page.evaluate(() => ({ sameForm: window.stBefore === document.querySelector('form.st'), pictures: document.querySelectorAll('.st-file .st-thumb').length }));
      say('a picture upload stays on the Background tab', (await page.locator('.st-tab').nth(TABS.indexOf('background')).getAttribute('aria-selected')) === 'true', after);
      say('a picture upload counts as a change', await page.locator(':text("Changes made"):visible').first().waitFor({ timeout: 5000 }).then(() => true).catch(() => false), after);
      // TH-B15: and it becomes the background of the mode being changed, here the panel's own mode.
      say('the uploaded picture is the background of the mode being changed', (await page.locator('[data-bg="image"]').getAttribute('aria-pressed')) === 'true', after);
      await shot('editor-picture');
    }
    // TH-B8: the ⋯ menu in the title bar says what each item does, not only an icon.
    const more = page.locator('.fi-header .fi-dropdown-trigger').first();
    if (await more.count()) {
      // After Save, Livewire draws the page again; open the menu once that has settled.
      await settle();
      await more.click();
      await page.locator('.fi-dropdown-list-item').first().waitFor({ state: 'visible', timeout: 3000 }).catch(() => {});
      const words = await page.evaluate(() => [...document.querySelectorAll('.fi-dropdown-panel .fi-dropdown-list-item')].filter((el) => el.getClientRects().length > 0).map((el) => el.innerText.trim()));
      say('the ⋯ menu shows its words', words.length > 2 && words.every((w) => w.length > 1), { words, items: await page.locator('.fi-dropdown-list-item').count() });
      await shot('editor-more');
      await page.keyboard.press('Escape');
      await sleep(200);
    } else say('the ⋯ menu shows its words', false, { error: 'no ⋯ menu' });
    if (core && !phone) {
      // With the saved gradient behind the panel (the default theme), the editor stays readable and in reach.
      await goto('/admin/simplify-themes');
      await page.locator('.st-tab').nth(TABS.indexOf('background')).click();
      await sleep(300);
      // TH-B6: the editor's text sits on the page's card, not straight on the gradient or picture.
      const under = await behind('.st-sec h3');
      say('the editor sits on a card over a gradient', !!under && !['HTML', 'BODY'].includes(under.tag) && !under.image, { under });
      // TH-B7: the title bar over the card is the card's own colour, not a block of the page's plain background.
      const band = await behind('.fi-main .fi-header .fi-header-actions-ctn');
      say('the title bar has the card\'s colour', !!under && !!band && band.bg === under.bg, { band, card: under && under.bg });
      // TH-B14: the editor keeps the same inset from the card's edge as every other page, not 1px.
      const inset = await page.evaluate(() => { const card = document.querySelector('.fi-main-ctn'), h = [...document.querySelectorAll('.st-sec h3')].find((el) => el.offsetParent !== null); return card && h ? Math.round(h.getBoundingClientRect().left - card.getBoundingClientRect().left) : -1; });
      say('the editor keeps an inset from the card\'s edge', inset >= 12, { inset });
      // TH-B6: scrolled to the end, the tabs and the preview are still in sight under the title bar.
      await scrollEditorToEnd();
      await sleep(300);
      say('the tabs stay in sight when the editor scrolls', await onTop(page.locator('.st-tab').first()));
      say('the preview stays in sight when the editor scrolls', await onTop(page.locator('.st-side-head').first()));
      await shot('editor-scrolled');
    }
    // The ready-made default (checks.yml) on the pages everyone sees.
    await goto('/');
    say('the overview opens with the default theme', await page.locator('style#simplify-themes').count() === 1);
    await sleep(800);
    say('the effect moves behind the overview', await drawn('#simplify-themes-fx'));
    // TH-B11: Filament's greys (helper text, table rows, menus) stay greys in the theme's tint, not an accent blue.
    const grey = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--gray-400').trim());
    const chroma = parseFloat((grey.match(/^oklch\(\s*[\d.]+%?\s+([\d.]+)/) || [])[1]);
    say('the panel\'s greys stay grey', !(chroma > .085), { grey });
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
