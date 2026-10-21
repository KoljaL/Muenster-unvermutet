// @ts-check
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
import { test, expect, IS_LIVE } from './fixtures.js';

const projectRoot = process.cwd();
const testUsername = 'playwright-test';
const testPassword = 'Playwright-Test-2026!';
const png = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=',
  'base64',
);

test.describe('Admin (lokale Testdatenbank)', () => {
  test.skip(IS_LIVE, 'Admin-Aktionen dürfen keine Live-Daten ändern');

  test.beforeAll(() => {
    const databasePath = resolve(projectRoot, '.playwright-data/game.sqlite');
    const seedAdmin = `
      $db = new PDO('sqlite:' . $argv[1]);
      $hash = password_hash('${testPassword}', PASSWORD_DEFAULT);
      $db->prepare('INSERT OR IGNORE INTO admins (username, password_hash, active, created_at) VALUES (?, ?, 1, ?)')
        ->execute(['${testUsername}', $hash, date(DATE_ATOM)]);
    `;
    execFileSync('php', ['-r', seedAdmin, databasePath], { cwd: projectRoot, stdio: 'pipe' });
  });

  test('Login, Stationen bearbeiten und archivieren, Vorschläge annehmen und ablehnen', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'Admin', 'Admin-Workflow läuft einmal gegen die gemeinsame Testdatenbank');
    const suffix = `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
    const stationTitle = `PW Station ${suffix}`;
    const editedStationTitle = `${stationTitle} bearbeitet`;
    const rejectedTitle = `PW Vorschlag abgelehnt ${suffix}`;
    const acceptedTitle = `PW Vorschlag angenommen ${suffix}`;

    await page.goto('admin/login.php');
    await page.locator('#username').fill(testUsername);
    await page.locator('#password').fill(testPassword);
    await page.getByRole('button', { name: 'Anmelden' }).click();
    await expect(page.locator('h1')).toHaveText('Stationen');

    await page.getByRole('link', { name: '+ Neue Station' }).click();
    await page.locator('#title').fill(stationTitle);
    await page.locator('#teaser_text').fill('Ein kurzer Anreißer für die Station.');
    await page.locator('#hint').fill('Playwright-Hinweis');
    await page.locator('#lat').fill('51.9622');
    await page.locator('#lon').fill('7.6278');
    await page.locator('[name="options[0]"]').fill('Richtige Antwort');
    await page.locator('[name="options[1]"]').fill('Falsche Antwort');
    await page.locator('#solution_title').fill('Playwright-Lösung');
    await page.locator('#info').fill('Playwright-Information');
    await page.locator('#task_image').setInputFiles({
      name: 'test.png',
      mimeType: 'image/png',
      buffer: png,
    });
    await page.locator('form[action="station-save.php"]').evaluate((form) => form.requestSubmit());

    let stationRow = page.locator('.station-item').filter({ hasText: stationTitle });
    await expect(stationRow).toBeVisible();
    const editHref = await stationRow.getByRole('link', { name: 'Bearbeiten' }).getAttribute('href');
    const editUrl = new URL(editHref, page.url()).toString();
    await page.goto(editUrl);
    await expect(page.locator('#teaser_text')).toHaveValue('Ein kurzer Anreißer für die Station.');
    await page.locator('#teaser_text').fill('Geänderter Anreißer mit <Klartext>.');
    await page.locator('#title').fill(editedStationTitle);
    await page.locator('form[action="station-save.php"]').evaluate((form) => form.requestSubmit());
    stationRow = page.locator('.station-item').filter({ hasText: editedStationTitle });
    await expect(stationRow).toBeVisible();

    const editLink = stationRow.getByRole('link', { name: 'Bearbeiten' });
    await page.goto(new URL(await editLink.getAttribute('href'), page.url()).toString());
    await expect(page.locator('#teaser_text')).toHaveValue('Geänderter Anreißer mit <Klartext>.');
    const stationId = await page.locator('[name="orig_id"]').inputValue();
    const csrf = await page.locator('main [name="csrf"]').inputValue();
    const archiveResponse = await page.request.post('admin/station-delete.php', {
      form: { id: stationId, status: 'archived', csrf },
    });
    expect(archiveResponse.ok()).toBeTruthy();
    await page.goto('admin/index.php');
    await expect(page.locator('.station-item').filter({ hasText: editedStationTitle })).toHaveClass(/station-item--archived/);

    for (const title of [rejectedTitle, acceptedTitle]) {
      const response = await page.request.post('api/submit_station.php', {
        data: { version: 1, name: title, description: 'Playwright-Testbeschreibung' },
      });
      expect(response.status()).toBe(201);
    }

    await page.goto('admin/suggestions.php');
    const rejectCard = page.locator('fieldset.task-card').filter({ hasText: rejectedTitle });
    page.on('dialog', (dialog) => dialog.accept());
    await rejectCard.getByRole('button', { name: 'Ablehnen' }).click();
    await expect(page.locator('fieldset.task-card').filter({ hasText: rejectedTitle })).toHaveCount(0);

    const acceptCard = page.locator('fieldset.task-card').filter({ hasText: acceptedTitle });
    const acceptHref = await acceptCard.getByRole('link', { name: /Prüfen & freigeben/ }).getAttribute('href');
    const acceptUrl = new URL(acceptHref, page.url()).toString();
    const acceptResponse = await page.goto(acceptUrl);
    expect(acceptResponse?.status()).toBe(200);
    await page.locator('#hint').fill('Playwright-Hinweis');
    await page.locator('#lat').fill('51.9622');
    await page.locator('#lon').fill('7.6278');
    await page.locator('[name="options[0]"]').fill('Richtige Antwort');
    await page.locator('[name="options[1]"]').fill('Falsche Antwort');
    await page.locator('#solution_title').fill(`${acceptedTitle} Lösung`);
    await page.locator('#task_image').setInputFiles({
      name: 'test.png',
      mimeType: 'image/png',
      buffer: png,
    });
    await page.locator('form[action="station-save.php"]').evaluate((form) => form.requestSubmit());

    await expect(page.locator('.station-item').filter({ hasText: acceptedTitle })).toBeVisible();
    await page.goto('admin/suggestions.php');
    await expect(page.locator('fieldset.task-card').filter({ hasText: acceptedTitle })).toHaveCount(0);

    await page.goto('admin/settings.php');
    const creditsText = '<p><strong>Mitwirkende</strong></p><p><a href="https://credits.example.invalid" target="_blank" rel="noopener noreferrer">Beispiel-Link</a></p>';
    const imprintText = '<p><strong>Beispielverein Münster e. V.</strong></p><p>Vertreten durch: Erika Beispiel</p><p><a href="https://impressum.example.invalid" target="_blank" rel="noopener noreferrer">Kontakt</a></p>';
    await page.locator('#public-credits').fill(creditsText);
    await page.locator('#public-imprint').fill(imprintText);
    const routeHintText = 'Eigener Erläuterungstext zur Navigation.';
    await page.locator('#public-route-hint').fill(routeHintText);
    await page.getByRole('button', { name: 'Einstellungen speichern' }).click();
    await expect(page.getByRole('status')).toHaveText('Einstellungen gespeichert.');
    const settingsResponse = await page.request.get('api/settings.php');
    expect(settingsResponse.ok()).toBeTruthy();
    const savedSettings = await settingsResponse.json();
    expect(savedSettings.data.publicText).toEqual({ credits: creditsText, imprint: imprintText, routeHint: routeHintText });

    await page.locator('#database-file').setInputFiles({
      name: 'invalid.sqlite',
      mimeType: 'application/vnd.sqlite3',
      buffer: Buffer.from('keine SQLite-Datenbank'),
    });
    await page.getByRole('button', { name: 'Datenbank importieren' }).click();
    await expect(page.getByRole('alert')).toHaveText('Die Sicherungsdatei ist ungültig oder die Datenbank konnte nicht ersetzt werden.');

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Datenbank exportieren' }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/^\d{8}-ms-unvermutet\.sqlite$/);
    const backupPath = await download.path();
    expect(backupPath).toBeTruthy();
    await page.locator('#database-file').setInputFiles(backupPath);
    await page.getByRole('button', { name: 'Datenbank importieren' }).click();
    await expect(page.getByRole('status')).toHaveText('Die Datenbank wurde erfolgreich importiert.');

    await page.goto('admin/index.php');
    await expect(page.locator('.station-item').filter({ hasText: editedStationTitle })).toBeVisible();

    await page.addInitScript(() => {
      localStorage.setItem('muenster-unvermutet:intro-state', 'closed');
      localStorage.setItem('muenster-unvermutet:stats-consent', 'denied');
    });
    await page.goto('index.html');
    await page.locator('.tab[data-t="more"]').tap();
    await expect(page.locator('#publicCredits')).toBeVisible();
    await expect(page.locator('#publicCreditsText strong')).toHaveText('Mitwirkende');
    await expect(page.locator('#publicCreditsText a')).toHaveAttribute('href', 'https://credits.example.invalid');
    await expect(page.locator('#publicCreditsText a')).toHaveAttribute('target', '_blank');
    await expect(page.locator('#publicImprintText strong')).toHaveText('Beispielverein Münster e. V.');
    await expect(page.locator('#publicImprintText a')).toHaveAttribute('href', 'https://impressum.example.invalid');
    await expect(page.locator('#publicImprintText a')).toHaveAttribute('target', '_blank');
    const sectionOrder = await page.locator('#p-more').evaluate((panel) => {
      const ids = ['publicCredits', 'publicImprint'].map((id) => Array.from(panel.children).indexOf(document.getElementById(id)));
      const attribution = Array.from(panel.children).findIndex((child) => child.textContent.includes('Kartendaten'));
      return { ids, attribution };
    });
    expect(sectionOrder.ids.every((index) => index >= 0 && index < sectionOrder.attribution)).toBe(true);
  });
});
