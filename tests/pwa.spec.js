// @ts-check
import { test, expect, openGame } from './fixtures.js';

const HINT_KEY = 'muenster-unvermutet:install-hint';

test.describe('PWA', () => {
  test('Manifest ist gültig und alle Symbole sind erreichbar', async ({ page, request }) => {
    await openGame(page);
    const href = await page.locator('link[rel="manifest"]').getAttribute('href');
    const url = new URL(/** @type {string} */ (href), page.url()).href;
    const res = await request.get(url);
    expect(res.ok()).toBe(true);
    const manifest = await res.json();
    expect(manifest.name).toBe('Münster unvermutet – Foto-Schnitzeljagd');
    expect(manifest.short_name).toBe('Münster unvermutet');
    expect(manifest.display).toBe('standalone');
    expect(manifest.lang).toBe('de');
    expect(new URL(manifest.start_url, url).pathname).toBe(new URL('index.html', url).pathname);
    expect(new URL(manifest.scope, url).pathname).toBe(new URL('./', url).pathname);
    expect(manifest.icons.some((i) => i.purpose === 'maskable' && i.sizes === '512x512')).toBe(true);
    for (const icon of manifest.icons) {
      const r = await request.get(new URL(icon.src, url).href);
      expect(r.ok(), icon.src).toBe(true);
      expect(r.headers()['content-type']).toContain(icon.type);
    }
  });

  test('Metadaten im Head und kein Service Worker', async ({ page }) => {
    await openGame(page);
    await expect(page.locator('link[rel="apple-touch-icon"]')).toHaveAttribute('href', /apple-touch-icon\.png$/);
    await expect(page.locator('meta[name="apple-mobile-web-app-capable"]')).toHaveAttribute('content', 'yes');
    await expect(page.locator('meta[name="theme-color"]').first()).toHaveAttribute('content', /^#/);
    const regs = await page.evaluate(async () => (await navigator.serviceWorker.getRegistrations()).length);
    expect(regs).toBe(0);
  });

  test('Installationshinweis erscheint auf iOS einmalig und ist schließbar', async ({ page }, testInfo) => {
    test.skip(!/Safari/.test(testInfo.project.name), 'nur iOS-Profil');
    await openGame(page);
    const hint = page.locator('#installHint');
    await expect(hint).toBeVisible({ timeout: 6000 });
    await expect(hint).toContainText('Zum Home-Bildschirm');
    await expect(hint).toContainText('Spielstand');
    await expect(hint).toContainText('Internetverbindung');
    await hint.getByRole('button', { name: 'Schließen' }).tap();
    await expect(hint).toBeHidden();
    expect(await page.evaluate((k) => localStorage.getItem(k), HINT_KEY)).toBe('dismissed');
    await page.reload();
    await expect(page.locator('#ringTxt')).not.toHaveText('–');
    await page.waitForTimeout(2200);
    await expect(hint).toBeHidden();
  });

  test('Ohne Installationsmöglichkeit erscheint kein Hinweis (Android ohne Prompt)', async ({ page }, testInfo) => {
    test.skip(!/Chrome/.test(testInfo.project.name), 'nur Android-Profil');
    await openGame(page);
    await page.waitForTimeout(2200);
    await expect(page.locator('#installHint')).toBeHidden();
  });
});
