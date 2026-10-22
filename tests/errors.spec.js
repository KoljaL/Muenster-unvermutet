// @ts-check
import { test, expect, openGame, IS_LIVE } from './fixtures.js';

const origin = (baseURL) => new URL(baseURL).origin;

test.describe('Fehlerprotokoll', () => {
  test.skip(IS_LIVE, 'Schreibt in die Datenbank');

  test('API speichert einen Fehler', async ({ request, baseURL }) => {
    const res = await request.post('api/log_error.php', {
      headers: { Origin: origin(String(baseURL)) },
      data: { message: 'PW Testfehler', source: 'app.js:1:1', stack: 'Error: x', url: '/index.html' },
    });
    expect(res.status()).toBe(201);
    expect((await res.json()).data.recorded).toBe(true);
  });

  test('API lehnt ungültige Anfragen ab', async ({ request, baseURL }) => {
    const headers = { Origin: origin(String(baseURL)) };
    expect((await request.post('api/log_error.php', { headers, data: { foo: 1 } })).status()).toBe(400);
    expect((await request.post('api/log_error.php', { headers, data: { message: '   ' } })).status()).toBe(400);
    expect((await request.post('api/log_error.php', { data: { message: 'x' } })).status()).toBe(403);
    expect((await request.get('api/log_error.php')).status()).toBe(405);
  });

  test('API kürzt zu lange Felder und ignoriert ungültige Kennungen', async ({ request, baseURL }) => {
    const res = await request.post('api/log_error.php', {
      headers: { Origin: origin(String(baseURL)) },
      data: { message: 'a'.repeat(5000), stack: 'b'.repeat(10000), playerId: 'kein-uuid' },
    });
    expect(res.status()).toBe(201);
  });

  test('Spiel meldet unbehandelte Fehler ohne Kennung bei fehlender Einwilligung', async ({ page }) => {
    await openGame(page, { consent: 'denied' });
    const requestPromise = page.waitForRequest('**/api/log_error.php');
    await page.evaluate(() => { setTimeout(() => { throw new Error('PW unbehandelt'); }, 0); });
    const body = (await requestPromise).postDataJSON();
    expect(body.message).toContain('PW unbehandelt');
    expect(body.playerId).toBeUndefined();
  });

  test('Spiel meldet abgelehnte Promises mit Kennung nach Einwilligung', async ({ page }) => {
    await openGame(page, { consent: 'granted' });
    const requestPromise = page.waitForRequest('**/api/log_error.php');
    await page.evaluate(() => { Promise.reject(new Error('PW promise')); });
    const body = (await requestPromise).postDataJSON();
    expect(body.message).toContain('PW promise');
    expect(body.playerId).toMatch(/^[0-9a-f-]{36}$/);
  });

  test('Spiel sendet höchstens 5 Fehler pro Sitzung', async ({ page }) => {
    await openGame(page);
    let count = 0;
    await page.route('**/api/log_error.php', (route) => { count++; return route.fulfill({ status: 201, json: { data: { recorded: true } } }); });
    await page.evaluate(() => { for (let i = 0; i < 10; i++) setTimeout(() => { throw new Error('PW flood ' + i); }, 0); });
    await expect.poll(() => count).toBe(5);
    await page.waitForTimeout(300);
    expect(count).toBe(5);
  });
});
