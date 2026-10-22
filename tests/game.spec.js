// @ts-check
import { test, expect, openGame, openListTab, fetchStations, KEYS, IS_LIVE } from './fixtures.js';

let stations;
test.beforeAll(async ({ playwright }, testInfo) => {
  const request = await playwright.request.newContext({ baseURL: testInfo.project.use.baseURL });
  stations = await fetchStations(request);
  await request.dispose();
});

test.describe('Start und Übersicht', () => {
  test('korrupter Speicher stürzt nicht ab und migriert alte Fortschrittsdaten', async ({ page }) => {
    await page.addInitScript(({ progressKey, introKey, consentKey }) => {
      if (sessionStorage.getItem('pw-corrupt-progress')) return;
      sessionStorage.setItem('pw-corrupt-progress', '1');
      localStorage.setItem(progressKey, '{kaputt');
      localStorage.setItem(introKey, 'closed');
      localStorage.setItem(consentKey, 'denied');
    }, { progressKey: KEYS.progress, introKey: KEYS.intro, consentKey: KEYS.consent });
    await page.goto('index.html');
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);

    await page.evaluate(({ progressKey, legacyKey, stationId }) => {
      localStorage.removeItem(progressKey);
      localStorage.setItem(legacyKey, JSON.stringify([stationId]));
    }, { progressKey: KEYS.progress, legacyKey: 'ms-suchspiel', stationId: stations[0].id });
    await page.reload();
    await expect(page.locator('#ringTxt')).toHaveText(`1/${stations.length}`);
    await page.locator('.tab[data-t="list"]').tap();
    await expect(page.locator(`#list [data-station-id="${stations[0].id}"]`)).toHaveClass(/solved/);
  });

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
    await page.addInitScript(({ intro, consent }) => { localStorage.setItem(intro, 'closed'); localStorage.setItem(consent, 'denied'); }, { intro: KEYS.intro, consent: KEYS.consent });
    await page.goto('index.html');
    await expect(page.locator('#loadError')).toBeVisible();
    await page.unroute('**/api/map_pins.php');
    await page.locator('#loadRetry').tap();
    await expect(page.locator('#loadError')).toBeHidden();
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);
  });

  test('Tab-Navigation wechselt die Bereiche', async ({ page }) => {
    await openGame(page);
    const expectTab = async (selected) => {
      await expect(page.locator('.tab[aria-selected="true"]')).toHaveCount(1);
      await expect(page.locator(`.tab[data-t="${selected}"]`)).toHaveAttribute('aria-selected', 'true');

      for (const [tab, panel] of [['progress', '#p-progress'], ['more', '#p-more']]) {
        const active = tab === selected;
        if (active) await expect(page.locator(panel)).toBeVisible();
        else await expect(page.locator(panel)).toBeHidden();
        await expect(page.locator(panel)).toHaveJSProperty('inert', !active);
      }

      const listActive = selected === 'list';
      await expect(page.locator('#sheet')).toHaveAttribute('data-snap', listActive ? 'full' : 'hidden');
      await expect(page.locator('#sheet')).toHaveJSProperty('inert', !listActive);
    };

    for (const [tab, nextTab] of [['list', 'progress'], ['progress', 'more'], ['more', 'list']]) {
      await page.locator(`.tab[data-t="${tab}"]`).tap();
      await expectTab(tab);
      await page.locator(`.tab[data-t="${nextTab}"]`).tap();
      await expectTab(nextTab);
      await page.locator(`.tab[data-t="${nextTab}"]`).tap();
      await expectTab('map');
    }

    await page.setViewportSize({ width: 320, height: 640 });
    const tabLayout = await page.evaluate(() => {
      const appRect = document.querySelector('#app').getBoundingClientRect();
      const tabs = document.querySelector('#tabs');
      return {
        width: tabs.clientWidth,
        scrollWidth: tabs.scrollWidth,
        buttons: Array.from(tabs.querySelectorAll('.tab')).map((button) => {
          const rect = button.getBoundingClientRect();
          return { left: rect.left, right: rect.right };
        }),
      };
    });
    expect(tabLayout.scrollWidth).toBeLessThanOrEqual(tabLayout.width);
    expect(tabLayout.buttons.every((button) => button.left >= 0 && button.right <= 320)).toBe(true);

    for (const tab of ['list', 'progress', 'more']) {
      await page.locator(`.tab[data-t="${tab}"]`).tap();
      await expectTab(tab);
      const surface = tab === 'list' ? '#list' : `#p-${tab}`;
      const horizontalOverflow = await page.locator(surface).evaluate((element) => element.scrollWidth > element.clientWidth);
      expect(horizontalOverflow).toBe(false);
    }

    const sheetMotion = await page.locator('#sheet').evaluate((element) => ({
      reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
      durations: getComputedStyle(element).transitionDuration,
    }));
    expect(sheetMotion.reduced).toBe(true);
    expect(Math.max(...sheetMotion.durations.split(',').map((duration) => Number.parseFloat(duration) * 1000))).toBeLessThanOrEqual(1);
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

  test('Textfarbe der Kategorie ist auf hellen und dunklen Hintergründen lesbar', async ({ page }) => {
    const lightStation = stations[0];
    const darkStation = stations.find((station) => station.category !== lightStation.category);
    expect(darkStation).toBeTruthy();
    await page.route('**/api/categories.php', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      body.data = body.data.map((category) => ({
        ...category,
        color: category.id === lightStation.category ? '#ffffff'
          : category.id === darkStation.category ? '#000000'
            : category.color,
      }));
      await route.fulfill({ response, json: body });
    });

    await openGame(page);
    await openListTab(page);
    await expect(page.locator(`#list [data-station-id="${lightStation.id}"] .thumb`)).toHaveCSS('color', 'rgb(28, 25, 23)');
    await expect(page.locator(`#list [data-station-id="${darkStation.id}"] .thumb`)).toHaveCSS('color', 'rgb(255, 255, 255)');
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
    await expect(page.locator('#sTeaser')).toBeHidden();
    await expect(page.locator('#sOptions .option')).toHaveCount(first().options.length);
    await page.locator('#back').tap();
    await expect(page).not.toHaveURL(/station=/);
    await expect(page.locator('#station')).not.toHaveClass(/\bon\b/);
    await expect(page.locator('.tab[data-t="list"]')).toHaveAttribute('aria-selected', 'true');
    await expect(page.locator('#sheet')).toHaveAttribute('data-snap', 'full');
  });

  test('Anreißer bleibt bei falschen Antworten stehen und ist nach dem Lösen dauerhaft ausgeklappt', async ({ page }) => {
    const station = stations[0];
    const teaser = '<b>Ein langer Anreißer</b> mit genügend Inhalt, damit die Vorschau über mehrere Zeilen läuft. '.repeat(8);
    await page.route('**/api/get_details.php*', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      body.data.teaserText = teaser;
      await route.fulfill({ response, json: body });
    });
    await openGame(page, { path: `index.html?station=${station.id}` });

    await expect(page.locator('#sTeaser')).toBeVisible();
    await expect(page.locator('#sTitle')).toHaveText(station.title);
    await expect(page.locator('#sTeaserText')).toHaveText(teaser);
    expect(await page.locator('#sTeaser').evaluate((teaserNode) => (
      teaserNode.previousElementSibling?.id === 'sTitle' && teaserNode.nextElementSibling?.id === 'sMain'
    ))).toBe(true);
    await expect(page.locator('#sTeaserText b')).toHaveCount(0);
    await expect(page.locator('#sTeaser [data-act="teaser"]')).toHaveAttribute('aria-expanded', 'false');
    await page.locator('#sTeaser [data-act="teaser"]').tap();
    await expect(page.locator('#sTeaser [data-act="teaser"]')).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#sTeaser [data-act="teaser"]')).toHaveText('Weniger anzeigen');
    await page.locator('#sTeaser [data-act="teaser"]').tap();
    await expect(page.locator('#sTeaser [data-act="teaser"]')).toHaveAttribute('aria-expanded', 'false');

    await page.locator(`#sOptions .option[data-i="${wrongIndex()}"]`).tap();
    await expect(page.locator('#errMsg')).toBeVisible();
    await expect(page.locator('#sTeaser')).toBeVisible();
    expect(await page.locator('#sTeaser').evaluate((teaserNode) => (
      teaserNode.previousElementSibling?.id === 'sTitle' && teaserNode.nextElementSibling?.id === 'sMain'
    ))).toBe(true);

    await page.locator(`#sOptions .option[data-i="${station.correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect(page.locator('#sTeaser')).toBeVisible();
    expect(await page.locator('#sTeaser').evaluate((teaserNode) => (
      teaserNode.previousElementSibling?.id === 'sSolved' && teaserNode.nextElementSibling?.id === 'orient'
    ))).toBe(true);
    await expect(page.locator('#sTeaser')).toHaveClass(/expanded/);
    await expect(page.locator('#sTeaser')).toHaveCSS('margin-top', '20px');
    await expect(page.locator('#sTeaserText')).toHaveCSS('display', 'block');
    await expect(page.locator('#sTeaser [data-act="teaser"]')).toBeHidden();
    await expect(page.locator('#sTeaser [data-act="teaser"]')).toHaveAttribute('aria-expanded', 'true');
  });

  test('Browser-Zurück und -Vor wechseln zwischen Stationen', async ({ page }) => {
    await openGame(page);
    await openListTab(page);
    await page.locator(`#list [data-station-id="${first().id}"]`).tap();
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    const next = page.locator('#action [data-act="next"]');
    const nextId = await next.getAttribute('data-next-id');
    expect(nextId).toBeTruthy();
    await next.tap();
    await expect(page).toHaveURL(new RegExp(`station=${nextId}`));

    await page.goBack();
    await expect(page).toHaveURL(new RegExp(`station=${first().id}`));
    await expect(page.locator('#sNum')).toHaveText(`Station ${first().number}`);
    await page.goForward();
    await expect(page).toHaveURL(new RegExp(`station=${nextId}`));
    await expect(page.locator('#sOptions .option').first()).toBeVisible();
  });

  test('Direktlink ?station=ID öffnet Station, ungültige ID bleibt in der Übersicht', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await expect(page.locator('#sOptions .option').first()).toBeVisible();
    await expect(page.locator('#ringTxt')).toHaveText(`0/${stations.length}`);
  });

  test('Bild-Fallback erscheint bei einem 404', async ({ page }) => {
    await page.route('**/api/get_details.php**', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      if (body.data.id === first().id) body.data.taskImage = '/uploads/pw-missing-image.jpg';
      await route.fulfill({ response, json: body });
    });
    await page.route('**/uploads/pw-missing-image.jpg', (route) => route.fulfill({ status: 404 }));

    await openGame(page, { path: `index.html?station=${first().id}` });
    await expect(page.locator('#heroFb')).toContainText('Das Foto konnte nicht geladen werden.');
    await expect(page.locator('#imgTask')).toBeHidden();
  });

  test('ungültiger Stationslink zeigt die Übersicht', async ({ page }) => {
    await openGame(page, { path: 'index.html?station=gibt-es-nicht' });
    await expect(page.locator('#sOptions .option')).toHaveCount(0);
    await expect(page.locator('#pageTitle')).toBeVisible();
  });

  test('Distanzberechnung stimmt für bekannte Koordinaten', async ({ page }) => {
    await openGame(page);
    const distance = await page.evaluate(() => distanceBetween(0, 0, 0, 1));
    expect(distance).toBeCloseTo(111194.9, 1);
  });

  test('falsche Antwort zeigt Fehlermeldung, Fehlversuch wird gezählt', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${wrongIndex()}"]`).tap();
    await expect(page.locator('#errMsg')).toBeVisible();
    const progress = await page.evaluate((k) => JSON.parse(localStorage.getItem(k)), KEYS.progress);
    expect(progress.stations[first().id].attempts).toBe(1);
    expect(progress.stations[first().id].solved).toBe(false);
  });

  test('Orientierungskarte bleibt bei der Frage offen, klappt nach richtiger Antwort ein und öffnet an der nächsten Station', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    const orientation = page.locator('#orient');
    await expect(orientation).toBeVisible();
    await expect(orientation).toHaveJSProperty('open', true);

    await page.locator(`#sOptions .option[data-i="${wrongIndex()}"]`).tap();
    await expect(page.locator('#errMsg')).toBeVisible();
    await expect(orientation).toHaveJSProperty('open', true);

    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect(orientation).toHaveJSProperty('open', false);

    const nextId = await page.locator('#action [data-act="next"]').getAttribute('data-next-id');
    expect(nextId).toBeTruthy();
    await page.locator('#action [data-act="next"]').tap();
    await expect(page.locator('#station')).not.toHaveClass(/solved/);
    await expect(page.locator('#orient')).toBeVisible();
    await expect(page.locator('#orient')).toHaveJSProperty('open', true);
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

  test('gelöste Ansicht zeigt Bildunterschrift und optionale Orientierungskarte ohne Kartenlinks', async ({ page }) => {
    const caption = 'Ein Test für die Bildunterschrift';
    await page.route('**/api/get_details.php*', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      body.data.solutionCaption = caption;
      body.data.solutionImage = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
      await route.fulfill({ response, json: body });
    });
    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();

    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect(page.locator('#imgSol')).toBeVisible();
    await expect(page.locator('#sCaption')).toHaveText(caption);
    await expect(page.locator('#orient')).toHaveJSProperty('open', false);
    expect(await page.locator('#sCaption').evaluate((captionNode) => (
      captionNode.previousElementSibling?.id === 'hero' && captionNode.nextElementSibling?.classList.contains('body')
    ))).toBe(true);
    await expect(page.locator('#sCaption')).not.toContainText(/^Foto:/);
    await expect(page.locator('#station .coords')).toHaveCount(0);
    await expect(page.locator('#sSolved a[href*="google.com/maps"], #sSolved a[href*="openstreetmap.org"]')).toHaveCount(0);

    const orientation = page.locator('#orient');
    const summary = orientation.locator('summary');
    await expect(summary).toHaveText('OpenStreetMap-Orientierungskarte');
    await expect(orientation).toHaveJSProperty('open', false);
    await summary.focus();
    await page.keyboard.press('Enter');
    await expect(orientation).toHaveJSProperty('open', true);
    await expect(page.locator('#orientMap')).toBeVisible();
    const attribution = page.locator('#orientMap .leaflet-control-attribution');
    if (await attribution.count()) await expect(attribution).toContainText('OSM');
    await page.keyboard.press('Enter');
    await expect(orientation).toHaveJSProperty('open', false);
    await expect(summary).toBeFocused();
    await page.keyboard.press('Space');
    await expect(orientation).toHaveJSProperty('open', true);
    expect(await page.locator('#sScroll').evaluate((scroll) => scroll.scrollWidth <= scroll.clientWidth)).toBe(true);

    const transitionDuration = await page.locator('#imgSol').evaluate((image) => getComputedStyle(image).transitionDuration);
    expect(transitionDuration.split(',').every((duration) => Number.parseFloat(duration) <= 0.001)).toBe(true);
  });

  test('Lösungsfoto ohne Beschreibung zeigt weder Ersatztext noch technischen Dateinamen', async ({ page }) => {
    await page.route('**/api/get_details.php*', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      body.data.solutionCaption = '';
      body.data.solutionImage = '/uploads/pw-private-solution-filename.jpg';
      await route.fulfill({ response, json: body });
    });
    await page.route('**/uploads/pw-private-solution-filename.jpg', (route) => route.fulfill({
      status: 200,
      contentType: 'image/svg+xml',
      body: '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>',
    }));

    await openGame(page, { path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();

    await expect(page.locator('#imgSol')).toBeVisible();
    await expect(page.locator('#sCaption')).toBeHidden();
    await expect(page.locator('#sCaption')).toHaveText('');
    await expect(page.locator('#station')).not.toContainText('pw-private-solution-filename.jpg');
    await expect(page.locator('#station')).not.toContainText('Lösungsfoto');
  });

  test('Antwort- und Stationswechselanimationen sind abgestimmt und respektieren reduzierte Bewegung', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    await openGame(page, { path: `index.html?station=${first().id}` });

    const standardDurations = await page.evaluate(() => [
      getComputedStyle(document.querySelector('#imgSol')).transitionDuration,
      getComputedStyle(document.querySelector('#sSolved')).transitionDuration.split(',')[0],
      getComputedStyle(document.querySelector('#sScroll')).transitionDuration,
    ]);
    expect(standardDurations).toEqual(['0.22s', '0.22s', '0.22s']);

    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await page.locator('#action [data-act="next"]').tap();
    await expect(page.locator('#station')).toHaveClass(/switching/);
    await expect(page.locator('#sNum')).not.toHaveText(`Station ${first().number}`);

    await page.emulateMedia({ reducedMotion: 'reduce' });
    const reducedDurations = await page.locator('#sScroll').evaluate((element) => (
      getComputedStyle(element).transitionDuration.split(',').map((duration) => Number.parseFloat(duration) * 1000)
    ));
    expect(reducedDurations.every((duration) => duration <= 1)).toBe(true);
  });

  test('Route starten steht neben Standort aktualisieren mit Erläuterung', async ({ page }) => {
    await page.route('**/api/settings.php*', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      body.data.publicText = { ...body.data.publicText, routeHint: 'Erläuterung Navigation' };
      await route.fulfill({ response, json: body });
    });
    await openGame(page, { path: `index.html?preview=ui.location_gate&station=${first().id}` });
    await expect(page.locator('#orient')).toHaveJSProperty('open', true);
    const route = page.locator('#orientLoc .loc-actions a.route-btn');
    await expect(route).toHaveText('Route');
    await expect(route).toBeVisible();
    await expect(route).toHaveAttribute('target', '_blank');
    await expect(route).toHaveAttribute('rel', /noopener/);
    await expect(route).toHaveAttribute('href', /destination=|daddr=/);
    await expect(page.locator('#orientLoc .loc-actions [data-act="verify"]')).toHaveText('Eigener Standort');
    await expect(page.locator('#orientLoc .route-hint')).toHaveText('Erläuterung Navigation');
  });

  test('Hinweis lässt sich ein- und ausklappen', async ({ page }) => {
    await openGame(page, { path: `index.html?station=${first().id}` });
    await expect(page.locator('#sCoords')).toHaveCount(0);
    await expect(page.locator('#sUnsolved a[href*="google.com/maps"]')).toHaveCount(0);
    const order = await page.evaluate(() => {
      const ids = ['sTitle', 'sTeaser', 'hintBox', 'sIntro', 'sOptions'];
      return ids.map((id) => {
        const node = document.getElementById(id);
        return ids.filter((otherId) => {
          const other = document.getElementById(otherId);
          return Boolean(node && other && (node.compareDocumentPosition(other) & Node.DOCUMENT_POSITION_FOLLOWING));
        }).length;
      });
    });
    expect(order).toEqual([4, 3, 2, 1, 0]);

    const hint = page.locator('#action [data-act="hint"]');
    await hint.tap();
    await expect(hint).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#hintText')).toContainText(first().hint);
    await expect(page.locator('#sOptions .option')).toHaveCount(first().options.length);
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

  test('zeigt alle drei Rangtexte abhängig von Fehlversuchen und aufgedeckten Lösungen', async ({ page }) => {
    await openGame(page);
    const cases = [
      [{ attempts: 0, revealed: false }, '🏅 Münster-Meister:in'],
      [{ attempts: 1, revealed: false }, '🥈 Spürnase'],
      [{ attempts: 0, revealed: true }, '🧭 Stadtentdecker:in'],
    ];
    for (const [stats, rank] of cases) {
      const progress = solvedProgress(stations.length);
      Object.assign(progress.stations[stations[0].id], stats);
      await page.evaluate(({ key, value }) => localStorage.setItem(key, JSON.stringify(value)), { key: KEYS.progress, value: progress });
      await page.reload();
      await expect(page.locator('#ringTxt')).toHaveText(`${stations.length}/${stations.length}`);
      await page.locator('.tab[data-t="progress"]').tap();
      await page.locator('#openFinale').tap();
      await expect(page.locator('#finRank')).toHaveText(rank);
      await page.locator('#finMap').tap();
    }
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
  test('Hilfe-Dialog blättert durch alle drei Seiten', async ({ page }) => {
    await openGame(page);
    await page.locator('.tab[data-t="more"]').tap();
    await page.locator('#openHelp').tap();
    await expect(page.locator('#hlBody')).toContainText('1. Pin antippen');
    await page.locator('#hlNext').tap();
    await expect(page.locator('#hlBody')).toContainText('2. Ort finden');
    await page.locator('#hlNext').tap();
    await expect(page.locator('#hlBody')).toContainText('3. Frage beantworten');
  });

  test('Escape schließt den Hilfe-Dialog und gibt den Fokus zurück', async ({ page }) => {
    await openGame(page);
    await page.locator('.tab[data-t="more"]').tap();
    const trigger = page.locator('#openHelp');
    await trigger.tap();
    await expect(page.locator('#m-help')).toHaveClass(/on/);
    await page.keyboard.press('Escape');
    await expect(page.locator('#m-help')).not.toHaveClass(/on/);
    await expect(trigger).toBeFocused();
  });

  test('Toast und Screenreader-Ansage werden bei ungültigem Stationslink gesetzt', async ({ page }) => {
    await openGame(page, { path: 'index.html?station=gibt-es-nicht' });
    await expect(page.locator('#toast')).toHaveClass(/on/);
    await expect(page.locator('#toast')).toHaveText('Der Link verweist auf eine unbekannte Station.');
    await expect(page.locator('#statusMessage')).toHaveText('Der Link verweist auf eine unbekannte Station.');
  });

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

test.describe('Anonyme Statistik', () => {
  const first = () => stations[0];
  const wrongIndex = () => (first().correctOptionIndex + 1) % first().options.length;

  async function recordTracking(page) {
    const sent = [];
    await page.route('**/api/track.php', (route) => {
      sent.push(route.request().postDataJSON());
      return route.fulfill({ status: 201, json: { data: { recorded: true } } });
    });
    return sent;
  }

  test('Ohne Zustimmung wird nichts gesendet und keine Kennung angelegt', async ({ page }) => {
    const sent = await recordTracking(page);
    await openGame(page, { skipIntro: false, consent: null, path: `index.html?station=${first().id}` });
    await expect(page.locator('#m-consent')).toHaveClass(/on/);
    await page.locator('#coNo').tap();
    await expect(page.locator('#m-consent')).not.toHaveClass(/on/);
    await page.locator(`#sOptions .option[data-i="${wrongIndex()}"]`).tap();
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    expect(sent).toEqual([]);
    expect(await page.evaluate((k) => [localStorage.getItem(k.consent), localStorage.getItem(k.player)], KEYS)).toEqual(['denied', null]);
  });

  test('Mit Zustimmung werden Ereignisse mit stabiler Kennung gesendet', async ({ page }) => {
    const sent = await recordTracking(page);
    await openGame(page, { consent: 'granted', path: `index.html?station=${first().id}` });
    await page.locator(`#sOptions .option[data-i="${wrongIndex()}"]`).tap();
    await page.locator('[data-act="hint"]').tap();
    await page.locator(`#sOptions .option[data-i="${first().correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect.poll(() => sent.map((e) => e.event)).toEqual(['opened', 'wrong_answer', 'hint_used', 'solved']);
    expect(sent.map((e) => e.stationId)).toEqual(Array(4).fill(first().id));
    expect(sent[1].attempts).toBe(1);
    expect(sent[3].attempts).toBe(1);
    expect(new Set(sent.map((e) => e.playerId)).size).toBe(1);
    expect(sent[0].playerId).toMatch(/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/);
    expect(JSON.stringify(sent)).not.toMatch(/lat|lon/i);
  });

  test('Einstellung widerruft die Zustimmung und entfernt die Kennung', async ({ page }) => {
    const sent = await recordTracking(page);
    await openGame(page, { consent: 'granted' });
    await page.locator('.tab[data-t="more"]').tap();
    await expect(page.locator('#setStats')).toHaveAttribute('aria-checked', 'true');
    await page.locator('#setStats').tap();
    await expect(page.locator('#setStats')).toHaveAttribute('aria-checked', 'false');
    expect(await page.evaluate((k) => [localStorage.getItem(k.consent), localStorage.getItem(k.player)], KEYS)).toEqual(['denied', null]);
    expect(sent).toEqual([]);
  });

  test('API nimmt gültige Ereignisse an und lehnt ungültige ab', async ({ request, baseURL }) => {
    const headers = { Origin: new URL(baseURL).origin };
    const playerId = crypto.randomUUID();
    const ok = await request.post('api/track.php', { headers, data: { playerId, event: 'opened', stationId: first().id } });
    expect(ok.status()).toBe(201);
    const badEvent = await request.post('api/track.php', { headers, data: { playerId, event: 'nope', stationId: first().id } });
    expect(badEvent.status()).toBe(422);
    const badPlayer = await request.post('api/track.php', { headers, data: { playerId: 'x', event: 'opened', stationId: first().id } });
    expect(badPlayer.status()).toBe(422);
    const unknownStation = await request.post('api/track.php', { headers, data: { playerId, event: 'opened', stationId: 'gibt-es-nicht' } });
    expect(unknownStation.status()).toBe(404);
    const noOrigin = await request.post('api/track.php', { data: { playerId, event: 'opened', stationId: first().id } });
    expect(noOrigin.status()).toBe(403);
  });
});

test.describe('Backend-API', () => {
  const fullSuggestion = {
    title: 'Playwright Upload-Test',
    hint: 'Testhinweis',
    lat: '51.9622',
    lon: '7.6278',
    question: 'Testfrage?',
    options: ['Antwort A', 'Antwort B'],
    correct: '0',
    info: 'Testbeschreibung',
    category: 'detail',
  };

  test('liefert Kategorien, Pins und Details', async ({ request }) => {
    const cats = await (await request.get('api/categories.php')).json();
    expect(cats.data.length).toBeGreaterThan(0);
    expect(stations.length).toBeGreaterThan(0);
    for (const s of stations) {
      expect(s.options.length).toBeGreaterThanOrEqual(2);
      expect(s.correctOptionIndex).toBeLessThan(s.options.length);
      expect(typeof s.teaserText).toBe('string');
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

  test('Vorschlags-API lehnt ungültiges JSON und unvollständige Daten ab', async ({ request }) => {
    const malformed = await request.post('api/submit_station.php', {
      data: '{',
      headers: { 'Content-Type': 'application/json' },
    });
    expect(malformed.status()).toBe(400);

    const invalid = await request.post('api/submit_station.php', {
      data: { version: 1, name: '', description: '' },
    });
    expect(invalid.status()).toBe(422);
    expect((await invalid.json()).error.code).toBe('validation_failed');
  });

  test('Vorschlags-API lehnt ungültige und zu große Fotos ab', async ({ request }) => {
    const multipartSuggestion = (fileName, buffer, mimeType) => {
      const form = new FormData();
      for (const [key, value] of Object.entries(fullSuggestion)) {
        if (key === 'options') value.forEach((option, index) => form.append(`options[${index}]`, option));
        else form.append(key, value);
      }
      form.append('task_image', new Blob([buffer], { type: mimeType }), fileName);
      return form;
    };
    const invalidImage = await request.post('api/submit_station.php', {
      multipart: multipartSuggestion('not-an-image.jpg', Buffer.from('kein Bild'), 'image/jpeg'),
    });
    expect(invalidImage.status()).toBe(422);
    expect((await invalidImage.json()).error.code).toBe('suggestion_not_saved');

    const oversizedImage = await request.post('api/submit_station.php', {
      multipart: multipartSuggestion('too-large.jpg', Buffer.alloc(3 * 1024 * 1024), 'image/jpeg'),
    });
    expect(oversizedImage.status()).toBe(422);
    expect((await oversizedImage.json()).error.code).toBe('suggestion_not_saved');
  });

  test('liefert ohne Standortfreigabe keine geschützten Stationsdetails', async ({ request }) => {
    test.skip(IS_LIVE, 'Die Standortfreigabe-Vorschau existiert nur lokal');
    const station = stations[0];
    const response = await request.get(`api/get_details.php?id=${station.id}&preview=ui.location_gate`);
    expect(response.ok()).toBeTruthy();
    const { data } = await response.json();
    expect(typeof data.teaserText).toBe('string');
    for (const field of ['hint', 'options', 'correctOptionIndex', 'solutionTitle', 'solutionCaption', 'info', 'solutionImage', 'links']) {
      expect(data).not.toHaveProperty(field);
    }
    expect((await request.get(`api/get_details.php?id=${station.id}&protected=1&preview=ui.location_gate`)).status()).toBe(403);
  });
});

test.describe('Standortfreigabe (Dev-Button "Fake Position")', () => {
  test.skip(IS_LIVE, 'Der Dev-Button und die Gate-Vorschau existieren nur lokal');
  const gated = (id) => `index.html?preview=ui.location_gate&station=${id}`;

  test('Antworten sind zunächst gesperrt und erscheinen nach der Freigabe', async ({ page, request }) => {
    const station = stations[0];
    const teaser = 'Ein öffentlicher Anreißer, während Antworten noch geschützt sind.';
    await page.route('**/api/get_details.php*', async (route) => {
      const response = await route.fetch();
      const body = await response.json();
      body.data.teaserText = teaser;
      await route.fulfill({ response, json: body });
    });

    await openGame(page, { path: gated(station.id) });
    await expect(page.locator('#sTeaserText')).toHaveText(teaser);
    await expect(page.locator('#sTeaser')).toBeVisible();
    await expect(page.locator('#orient [data-act="verify"]')).toBeVisible();
    await expect(page.locator('#sOptions .option')).toHaveCount(0);
    await expect(page.locator('#action [data-act="hint"]')).toHaveCount(0);
    await expect(page.locator('#action [data-act="reveal"]')).toHaveCount(0);
    await expect(page.locator('#sCoords')).toHaveCount(0);
    await expect(page.locator('#sUnsolved a[href*="google.com/maps"]:not(.route-btn)')).toHaveCount(0);

    await page.locator('#orient [data-act="fake"]').tap();

    await expect(page.locator('#sOptions .option')).toHaveCount(station.options.length);
    expect(await page.locator('#sTeaser').evaluate((teaserNode) => (
      teaserNode.previousElementSibling?.id === 'sTitle' && teaserNode.nextElementSibling?.id === 'sMain'
    ))).toBe(true);
    for (const [i, text] of station.options.entries()) {
      await expect(page.locator(`#sOptions .option[data-i="${i}"]`)).toContainText(text);
    }
    await expect(page.locator('#action [data-act="hint"]')).toBeVisible();
    await expect(page.locator('#action [data-act="reveal"]')).toBeVisible();
    await page.locator('#action [data-act="hint"]').tap();
    await expect(page.locator('#hintText')).toContainText(station.hint);

    await page.locator(`#sOptions .option[data-i="${station.correctOptionIndex}"]`).tap();
    await expect(page.locator('#station')).toHaveClass(/solved/);
    await expect(page.locator('#sTitle')).toHaveText(station.solutionTitle);
    expect(await page.locator('#sTeaser').evaluate((teaserNode) => (
      teaserNode.previousElementSibling?.id === 'sSolved' && teaserNode.nextElementSibling?.id === 'orient'
    ))).toBe(true);
  });

  test('Freigabe gilt nur für die jeweilige Station', async ({ page }) => {
    await openGame(page, { path: gated(stations[0].id) });
    await page.locator('#orient [data-act="fake"]').tap();
    await expect(page.locator('#sOptions .option').first()).toBeVisible();
    await page.locator('#back').tap();
    await page.goto(gated(stations[1].id));
    await expect(page.locator('#orient [data-act="verify"]')).toBeVisible();
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
    await page.locator('#orient [data-act="verify"]').tap();
    await expect(page.locator('#orient .loc.fail')).toBeVisible();
    await expect(page.locator('#orient .loc')).not.toContainText('veraltet');
    await expect(page.locator('#sOptions .option')).toHaveCount(0);

    await page.evaluate((p) => { window.__fakePos = p; }, { latitude: station.lat, longitude: station.lon, accuracy: 10 });
    await page.locator('#orient [data-act="verify"]').tap();
    await expect(page.locator('#sOptions .option')).toHaveCount(station.options.length);
  });

  test('GPS-Fehler und ungenaue Position zeigen Hinweise und lassen Antworten gesperrt', async ({ page }) => {
    const station = stations[0];
    await page.addInitScript(() => {
      window.__geoErrorCode = 1;
      Object.defineProperty(navigator, 'geolocation', {
        configurable: true,
        value: {
          getCurrentPosition(success, failure) {
            if (window.__geoErrorCode) failure({ code: window.__geoErrorCode });
            else success({
              coords: { latitude: window.__stationLat, longitude: window.__stationLon, accuracy: 10 },
              timestamp: Date.now(),
            });
          },
        },
      });
    });
    await page.route('**/api/verify_location.php*', (route) => route.fulfill({
      contentType: 'application/json',
      json: { data: { success: false, reason: 'poor_accuracy' } },
    }));
    await openGame(page, { path: gated(station.id) });

    const verify = page.locator('#orient [data-act="verify"]');
    const message = page.locator('#orient .loc p');
    for (const [code, expectedMessage] of [
      [1, 'Der Zugriff auf den Standort wurde verweigert.'],
      [3, 'Die Standortabfrage dauerte zu lange. Bitte erneut versuchen.'],
    ]) {
      await page.evaluate((errorCode) => { window.__geoErrorCode = errorCode; }, code);
      await verify.tap();
      await expect(message).toHaveText(expectedMessage);
      await expect(page.locator('#sOptions .option')).toHaveCount(0);
    }

    await page.evaluate(({ lat, lon }) => {
      window.__geoErrorCode = 0;
      window.__stationLat = lat;
      window.__stationLon = lon;
    }, { lat: station.lat, lon: station.lon });
    await verify.tap();
    await expect(message).toContainText('Standortgenauigkeit ist derzeit zu gering');
    await expect(page.locator('#sOptions .option')).toHaveCount(0);
  });
});
