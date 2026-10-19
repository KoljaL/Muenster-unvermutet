// @ts-check
import { test as base, expect } from '@playwright/test';

export const KEYS = {
  progress: 'muenster-unvermutet:progress',
  intro: 'muenster-unvermutet:intro-state',
};

export const IS_LIVE = process.env.TEST_ENV === 'live';

export const test = base.extend({
  // Kartenkacheln sind für die Logik irrelevant und würden Tests von externen Servern abhängig machen.
  page: async ({ page }, use) => {
    await page.route(/tile\.openstreetmap\.(de|org)/, (route) => route.abort());
    await use(page);
  },
});

/** Öffnet das Spiel mit übersprungenem Intro und optional vorbelegtem Fortschritt. */
export async function openGame(page, { path = 'index.html', skipIntro = true, progress = null } = {}) {
  await page.addInitScript(
    ({ keys, skipIntro, progress }) => {
      if (sessionStorage.getItem('pw-init')) return;
      sessionStorage.setItem('pw-init', '1');
      if (skipIntro) localStorage.setItem(keys.intro, 'closed');
      if (progress) localStorage.setItem(keys.progress, JSON.stringify(progress));
    },
    { keys: KEYS, skipIntro, progress },
  );
  await page.goto(path);
  await expect(page.locator('#ringTxt')).not.toHaveText('–');
}

export async function openListTab(page) {
  await page.locator('.tab[data-t="list"]').tap();
  await expect(page.locator('#list .row').first()).toBeVisible();
}

/** Lädt Stationsdaten (inkl. Antworten) über die API; nur ohne Standortfreigabe verfügbar. */
export async function fetchStations(request) {
  const pins = await (await request.get('api/map_pins.php')).json();
  const details = await Promise.all(
    pins.data.map(async (pin) => (await (await request.get(`api/get_details.php?id=${pin.id}`)).json()).data),
  );
  return details;
}

export { expect };
