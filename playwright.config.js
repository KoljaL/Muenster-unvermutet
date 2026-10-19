// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * Konfiguration für Umgebung:
 * Setze TEST_ENV=live (z.B. im Terminal), um die echte Online-URL zu testen.
 * Standardmäßig wird lokal getestet und der PHP-Server automatisch gestartet.
 */
const IS_LIVE = process.env.TEST_ENV === 'live';
// Der abschließende Slash ist nötig, damit relative Pfade (z. B. 'index.html') unter dem Unterordner aufgelöst werden.
const LIVE_URL = 'https://stina-ms.de/muenster-unvermutet/';
const LOCAL_PORT = 8123;
const LOCAL_URL = `http://127.0.0.1:${LOCAL_PORT}/`;
// Die Standortfreigabe steht in der Test-DB auf 'test' und wird nur mit ?preview=ui.location_gate aktiv.
// Tests laufen gegen eine Kopie der Datenbank, damit data/game.sqlite unverändert bleibt.
const TEST_DB_DIR = '.playwright-data';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// import path from 'path';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [['list'], ['html', { open: 'never' }]],
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('')`. */
    baseURL: IS_LIVE ? LIVE_URL : LOCAL_URL,

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    // Das Spiel respektiert prefers-reduced-motion; das macht Tests stabil (keine Konfetti-/Übergangsanimationen).
    reducedMotion: 'reduce',
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
  },

  /* Configure projects for major browsers */
  /* Das Spiel wird überwiegend auf Smartphones gespielt: iOS (WebKit) und Android (Chromium). */
  projects: [
    { name: 'Mobile Safari', testIgnore: /admin\.spec/, use: { ...devices['iPhone 13'] } },
    { name: 'Mobile Chrome', testIgnore: /admin\.spec/, use: { ...devices['Pixel 7'] } },
    // Der Admin-Test verändert die Test-DB und läuft deshalb erst nach den Spieltests.
    {
      name: 'Admin',
      testMatch: /admin\.spec/,
      dependencies: ['Mobile Safari', 'Mobile Chrome'],
      use: { ...devices['iPhone 13'] },
    },
    // Optional für Desktop-Stichproben:
    // { name: 'Desktop Chrome', use: { ...devices['Desktop Chrome'] } },
  ],

  /* Starte den lokalen PHP Dev Server NUR, wenn nicht im Live-Modus getestet wird */
  webServer: IS_LIVE
    ? undefined
    : {
        command: `mkdir -p ${TEST_DB_DIR} && rm -f ${TEST_DB_DIR}/game.sqlite* && (cp data/game.sqlite ${TEST_DB_DIR}/game.sqlite 2>/dev/null || true) && sqlite3 ${TEST_DB_DIR}/game.sqlite "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES ('ui.location_gate', 'test', datetime('now'))" && MS_DB_PATH="$PWD/${TEST_DB_DIR}/game.sqlite" php -S 127.0.0.1:${LOCAL_PORT}`,
        url: `${LOCAL_URL}api/categories.php`,
        reuseExistingServer: false,
        stdout: 'ignore',
        stderr: 'ignore',
        timeout: 60 * 1000,
      },
});
