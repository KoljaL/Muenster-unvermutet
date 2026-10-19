// @ts-check
import { test, expect, openGame, openListTab, fetchStations, KEYS, IS_LIVE } from './fixtures.js';

let stations;
test.beforeAll(async ({ playwright }, testInfo) => {
  const request = await playwright.request.newContext({ baseURL: testInfo.project.use.baseURL });
  stations = await fetchStations(request);
  await request.dispose();
});

test.describe('Start und Übersicht', () => {
  test('lädt Titel, Fortschritt und Stationsliste', async ({ page }) => {
    await openGame(page);
    await expect(page.locator('#pageTitle')).toHaveText('Münster unvermutet');
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);
    await openListTab(page);
    await expect(page.locator('#list .row')).toHaveCount(stations.length);
    await expect(page.locator('#sheetCount')).toHaveText(`0 von ${stations.length} gelöst`);
  });

  test('API-Fehler zeigt Hinweis mit Retry', async ({ page }) => {
    await page.route('**/api/map_pins.php', (route) => route.fulfill({ status: 500, json: { error: { message: 'kaputt' } } }));
    await page.addInitScript((k) => localStorage.setItem(k, 'closed'), KEYS.intro);
    await page.goto('index.html');
    await expect(page.locator('#loadError')).toBeVisible();
    await page.unroute('**/api/map_pins.php');
    await page.locator('#loadRetry').tap();
    await expect(page.locator('#loadError')).toBeHidden();
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);
  });

  test('Tab-Navigation wechselt die Bereiche', async ({ page }) => {
    await openGame(page);
    for (const [tab, panel] of [['progress', '#p-progress'], ['more', '#p-more']]) {
      await page.locator(`.tab[data-t="${tab}"]`).tap();
      await expect(page.locator(`.tab[data-t="${tab}"]`)).toHaveAttribute('aria-selected', 'true');
      await expect(page.locator(panel)).toBeVisible();
    }
  });

  test('Kategorienfilter reduziert die Liste und "Alle" hebt ihn auf', async ({ page }) => {
    await openGame(page);
    await openListTab(page);
    const chips = page.locator('#chipsSheet .chip:not([data-c=""])');
    await chips.first().tap();
    const filtered = await page.locator('#list .row').count();
    expect(filtered).toBeGreaterThan(0);
    expect(filtered).toBeLessThanOrEqual(stations.length);
    await page.locator('#chipsSheet .chip[data-c=""]').tap();
    await expect(page.locator('#list .row')).toHaveCount(stations.length);
  });

  test('Karten-Pin öffnet Station (wenn Karte geladen)', async ({ page }) => {
    await openGame(page);
    const pin = page.locator('#map .leaflet-marker-icon[aria-label^="Station 1,"]');
    test.skip(await page.locator('#mapFallback').isVisible(), 'Leaflet nicht erreichbar');
    await expect(pin).toBeVisible();
    // Pins liegen bei der Startansicht teilweise übereinander; das Click-Event wird daher direkt am Marker ausgelöst.
    await pin.dispatchEvent('click');
    await expect(page.locator('#station')).toHaveClass(/\bon\b/);
  });
});

test.describe('Station spielen', () => {
  const first = () => stations[0];
  const wrongIndex = () => (first().correctOptionIndex + 1) % first().options.length;

  test('Station aus Liste öffnen, URL und Zurück-Button', async ({ page }) => {
    await openGame(page);
    await openListTab(page);
    await page.locator(`#list [data-station-id="${first().id}"]`).tap();
    await expect(page.locator('#sNum')).toHaveText(`Station ${first().number}`);
    await expect(page).toHaveURL(new RegExp(`station=${first().id}`));
    await expect(page.locator('#sOptions .option')).toHaveCount(first().options.length);
    await page.locator('#back').tap();
    await expect(page).not.toHaveURL(/station=/);
  });

  test('Direktlink ?station=ID öffnet Station, ungültige ID bleibt in der Übersicht', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await expect(page.locator('#sOptions .option').first()).toBeVisible();
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);
  });

  test('ungültiger Stationslink zeigt die Übersicht', async ({ page }) => {
    await openGame(page, { path: 'index.html?station=gibt-es-nicht' });
    await expect(page.locator('#sOptions .option')).toHaveCount(0);
    await expect(page.locator('#pageTitle')).toBeVisible();
  });

  test('falsche Antwort zeigt Fehlermeldung, Fehlversuch wird gezählt', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${wrongIndex()}"]`).tap();
    await expect(page.locator('#errMsg')).toBeVisible();
    const progress = await page.evaluate((k) => JSON.parse(localStorage.getItem(k)), KEYS.progress);
    expect(progress.stations[first().id].attempts).toBe(1);
    expect(progress.stations[first().id].solved).toBe(false);
  });

  test('richtige Antwort löst die Station und persistiert nach Reload', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect(page.locator('#sTitle')).toHaveText(first().solutionTitle);
    await expect(page.locator('#sInfo')).not.toBeEmpty();
    await page.reload();
    await expect(page.locator('#ringTxt')).toHaveText(`1/${stations.length}`);
    await expect(page.locator('#station')).toHaveClass(/solved/);
  });

  test('Hinweis lässt sich ein- und ausklappen', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    const hint = page.locator('#action [data-act="hint"]');
    await hint.tap();
    await expect(hint).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#hintText')).toContainText(first().hint);
    await hint.tap();
    await expect(hint).toHaveAttribute('aria-expanded', 'false');
  });

  test('"Lösung anzeigen" löst die Station und zählt in der Statistik als aufgedeckt', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator('#action [data-act="reveal"]').tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    const progress = await page.evaluate((k) => JSON.parse(localStorage.getItem(k)), KEYS.progress);
    expect(progress.stations[first().id].revealed).toBe(true);
  });

  test('"Nächste Station" führt zu einer noch offenen Station', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    await page.locator('#action [data-act="next"]').tap();
    await expect(page).toHaveURL(/station=(?!.*$)|station=/);
    await expect(page).not.toHaveURL(new RegExp(`station=${first().id}`));
    await expect(page.locator('#sOptions .option').first()).toBeVisible();
  });

  test('Foto lässt sich vergrößern und per Schließen-Button beenden', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator('#hero').tap();
    await expect(page.locator('#imageViewer')).toHaveClass(/on/);
    await page.locator('#imageViewerClose').tap();
    await expect(page.locator('#imageViewer')).not.toHaveClass(/on/);
  });
});

test.describe('Fortschritt, Finale und Zurücksetzen', () => {
  const solvedProgress = (count) => ({
    version: 1,
    stations: Object.fromEntries(
      stations.slice(0, count).map((s) => [s.id, { solved: true, attempts: 0, hintUsed: false, revealed: false, selectedOptionIndex: s.correctOptionIndex }]),
    ),
  });

  test('vorhandener Fortschritt wird in Ring, Liste und Fortschrittsseite angezeigt', async ({ page }) => {
    await openGame(page, { progress: solvedProgress(3) });
    await expect(page.locator('#ringTxt')).toHaveText(`3/${stations.length}`);
    await page.locator('.tab[data-t="progress"]').tap();
    await expect(page.locator('#bigCount')).toHaveText(`3/${stations.length}`);
    await expect(page.locator('#badges .badge.got')).toHaveCount(3);
  });

  test('letzte Station lösen zeigt das Finale mit Statistik', async ({ page }) => {
    const last = stations[stations.length - 1];
    await openGame(page, { path: `index.html?station=${last.id}`, progress: solvedProgress(stations.length - 1) });
    await page.locator(`#sOptions .option[data-i="${last.correctOptionIndex}"]`).tap();
    await page.locator('#action [data-act="finale"]').tap();
    await expect(page.locator('#finale')).toHaveClass(/on/);
    await expect(page.locator('#finStats')).toContainText('selbst gelöst');
    await expect(page.locator('#finRank')).not.toBeEmpty();
    await page.locator('#finMap').tap();
    await expect(page.locator('#finale')).not.toHaveClass(/on/);
  });

  test('Zurücksetzen: Abbrechen behält, Bestätigen löscht den Fortschritt', async ({ page }) => {
    await openGame(page, { progress: solvedProgress(2) });
    await page.locator('.tab[data-t="more"]').tap();
    await page.locator('#openReset').tap();
    await expect(page.locator('#m-reset')).toHaveClass(/on/);
    await page.locator('#rsNo').tap();
    await expect(page.locator('#ringTxt')).toHaveText(`2/${stations.length}`);
    await page.locator('#openReset').tap();
    await page.locator('#rsYes').tap();
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);
  });
});

test.describe('Einstellungen und Hilfe', () => {
  test('Intro erscheint beim ersten Besuch und wird nach dem Durchklicken gemerkt', async ({ page }) => {
    await openGame(page, { skipIntro: false });
    await expect(page.locator('#m-help')).toHaveClass(/on/);
    for (let i = 0; i < 3; i++) await page.locator('#hlNext').tap();
    await expect(page.locator('#m-help')).not.toHaveClass(/on/);
    await page.reload();
    await expect(page.locator('#ringTxt')).not.toHaveText('–');
    await page.waitForTimeout(800);
    await expect(page.locator('#m-help')).not.toHaveClass(/on/);
  });

  test('Nacht-Look wird gespeichert', async ({ page }) => {
    await openGame(page);
    await page.locator('.tab[data-t="more"]').tap();
    await page.locator('#lookSeg [data-look="night"]').tap();
    await expect(page.locator('#lookSeg [data-look="night"]')).toHaveAttribute('aria-pressed', 'true');
    expect(await page.evaluate(() => localStorage.getItem('muenster-unvermutet:look'))).toBe('night');
  });

  test('Barrierefrei-Schalter wechselt den Zustand', async ({ page }) => {
    await openGame(page);
    await page.locator('.tab[data-t="more"]').tap();
    await page.locator('#setA11y').tap();
    await expect(page.locator('#setA11y')).toHaveAttribute('aria-checked', 'true');
  });
});

test.describe('Station vorschlagen', () => {
  async function openSuggest(page) {
    await page.locator('.tab[data-t="more"]').tap();
    await page.locator('#openSuggest').tap();
    await expect(page.locator('#m-suggest')).toHaveClass(/on/);
  }

  test('Pflichtfelder werden validiert', async ({ page }) => {
    await openGame(page);
    await openSuggest(page);
    await page.locator('#sgNext').tap();
    await expect(page.locator('#sgNameErr')).toBeVisible();
    await expect(page.locator('#sgDescErr')).toBeVisible();
    await expect(page.locator('#sgName')).toHaveAttribute('aria-invalid', 'true');
  });

  test('Abbrechen schließt den Dialog', async ({ page }) => {
    await openGame(page);
    await openSuggest(page);
    await page.locator('#sgBack').tap();
    await expect(page.locator('#m-suggest')).not.toHaveClass(/on/);
  });

  test('Serverfehler wird angezeigt und nicht als Erfolg gewertet', async ({ page }) => {
    await page.route('**/api/submit_station.php*', (route) => route.fulfill({ status: 500, json: { error: { message: 'kaputt' } } }));
    await openGame(page);
    await openSuggest(page);
    await page.locator('#sgName').fill('Testort');
    await page.locator('#sgDesc').fill('Beschreibung');
    await page.locator('#sgNext').tap();
    await expect(page.locator('#sgStatus')).toBeVisible();
    await expect(page.locator('#sgDone')).toBeHidden();
  });

  test('erfolgreicher Vorschlag sendet den erwarteten Payload', async ({ page }) => {
    // Gemockt, damit weder lokal noch live Datensätze entstehen.
    let payload;
    await page.route('**/api/submit_station.php*', async (route) => {
      payload = route.request().postDataJSON();
      await route.fulfill({ status: 201, json: { data: { received: true, status: 'pending' } } });
    });
    await openGame(page);
    await openSuggest(page);
    await page.locator('#sgName').fill('  Testort  ');
    await page.locator('#sgDesc').fill('Beschreibung');
    await page.locator('#sgNext').tap();
    await expect(page.locator('#sgDone')).toBeVisible();
    expect(payload).toMatchObject({ version: 1, name: 'Testort', description: 'Beschreibung' });
    await page.locator('#sgClose').tap();
    await expect(page.locator('#m-suggest')).not.toHaveClass(/on/);
  });

  test('echter Vorschlag landet im Backend (nur lokal)', async ({ page }) => {
    test.skip(IS_LIVE, 'Keine Testdaten auf der Live-Seite erzeugen');
    await openGame(page);
    await openSuggest(page);
    await page.locator('#sgName').fill('Playwright-Testort');
    await page.locator('#sgDesc').fill('Automatisch erzeugt');
    await page.locator('#sgNext').tap();
    await expect(page.locator('#sgDone')).toBeVisible();
  });
});

test.describe('Backend-API', () => {
  test('liefert Kategorien, Pins und Details', async ({ request }) => {
    const cats = await (await request.get('api/categories.php')).json();
    expect(cats.data.length).toBeGreaterThan(0);
    expect(stations.length).toBeGreaterThan(0);
    for (const s of stations) {
      expect(s.options.length).toBeGreaterThanOrEqual(2);
      expect(s.correctOptionIndex).toBeLessThan(s.options.length);
    }
  });

  test('ungültige und unbekannte Stations-IDs werden abgelehnt', async ({ request }) => {
    expect((await request.get('api/get_details.php?id=../x')).status()).toBe(400);
    expect((await request.get('api/get_details.php?id=gibt-es-nicht')).status()).toBe(404);
  });

  test('Standortprüfung verlangt gültige Daten', async ({ request }) => {
    const res = await request.post('api/verify_location.php', { data: {} });
    expect([400, 403, 422]).toContain(res.status());
  });
});

test.describe('Standortfreigabe (Dev-Button "Fake Position")', () => {
  test.skip(IS_LIVE, 'Der Dev-Button und die Gate-Vorschau existieren nur lokal');
  const gated = (id) => `index.html?preview=ui.location_gate&station=${id}`;

  test('Antworten sind zunächst gesperrt und erscheinen nach der Freigabe', async ({ page, request }) => {
    const station = stations[0];

    // Ohne Freigabe liefert die API keine geschützten Inhalte.
    const pub = await (await request.get(`api/get_details.php?id=${station.id}&preview=ui.location_gate`)).json();
    expect(pub.data.options).toBeUndefined();
    expect(pub.data.correctOptionIndex).toBeUndefined();
    expect((await request.get(`api/get_details.php?id=${station.id}&protected=1&preview=ui.location_gate`)).status()).toBe(403);

    await openGame(page, { path: gated(station.id) });
    await expect(page.locator('#sOptions [data-act="verify"]')).toBeVisible();
    await expect(page.locator('#sOptions .option')).toHaveCount(0);
    await expect(page.locator('#action [data-act="hint"]')).toHaveCount(0);
    await expect(page.locator('#action [data-act="reveal"]')).toHaveCount(0);

    await page.locator('#sOptions [data-act="fake"]').tap();

    await expect(page.locator('#sOptions .option')).toHaveCount(station.options.length);
    for (const [i, text] of station.options.entries()) {
      await expect(page.locator(`#sOptions .option[data-i="${i}"]`)).toContainText(text);
    }
    await expect(page.locator('#action [data-act="hint"]')).toBeVisible();
    await expect(page.locator('#action [data-act="reveal"]')).toBeVisible();

    await page.locator(`#sOptions .option[data-i="${station.correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect(page.locator('#sTitle')).toHaveText(station.solutionTitle);
  });

  test('Freigabe gilt nur für die jeweilige Station', async ({ page }) => {
    await openGame(page, { path: gated(stations[0].id) });
    await page.locator('#sOptions [data-act="fake"]').tap();
    await expect(page.locator('#sOptions .option').first()).toBeVisible();
    await page.locator('#back').tap();
    await page.goto(gated(stations[1].id));
    await expect(page.locator('#sOptions [data-act="verify"]')).toBeVisible();
    await expect(page.locator('#sOptions .option')).toHaveCount(0);
  });

  test('echte Standortprüfung: zu weit entfernt bleibt gesperrt, am Ziel wird freigegeben', async ({ page }) => {
    const station = stations[0];
    // Eigener Geolocation-Mock mit aktuellem Zeitstempel: WebKit liefert bei setGeolocation veraltete Zeitstempel.
    await page.addInitScript(() => {
      window.__fakePos = null;
      const getCurrentPosition = (ok) => ok({ coords: { ...window.__fakePos }, timestamp: Date.now() });
      Object.defineProperty(navigator, 'geolocation', { configurable: true, value: { getCurrentPosition } });
    });
    await openGame(page, { path: gated(station.id) });

    await page.evaluate((p) => { window.__fakePos = p; }, { latitude: station.lat + 0.01, longitude: station.lon, accuracy: 10 });
    await page.locator('#sOptions [data-act="verify"]').tap();
    await expect(page.locator('#sOptions .loc.fail')).toBeVisible();
    await expect(page.locator('#sOptions .loc')).not.toContainText('veraltet');
    await expect(page.locator('#sOptions .option')).toHaveCount(0);

    await page.evaluate((p) => { window.__fakePos = p; }, { latitude: station.lat, longitude: station.lon, accuracy: 10 });
    await page.locator('#sOptions [data-act="verify"]').tap();
    await expect(page.locator('#sOptions .option')).toHaveCount(station.options.length);
  });
});
