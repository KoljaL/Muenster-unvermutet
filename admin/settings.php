<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/settings.php';
$admin = require_admin();
$states = ui_setting_states();
$locationSettings = location_settings();
$publicTexts = public_text_settings();
$groups = [];
foreach (UI_REGISTRY as $key => $definition) {
    $groups[$definition['group']][$key] = $definition;
}

admin_header('Einstellungen', $admin);
?>
<h1 class="admin-title">Feature-Einstellungen</h1>
<p class="task-intro">Hier legst du fest, welche Funktionen Besucher auf der Webseite sehen. Änderungen gelten sofort für alle. <strong>Aus</strong> = für niemanden sichtbar, <strong>An</strong> = für alle sichtbar, <strong>Test</strong> = nur für dich sichtbar (Adresse der Webseite mit <code>?preview=all</code> am Ende öffnen). Mehr dazu unter <a href="help.php">Hilfe</a>.</p>
<form method="post" action="settings-save.php">
  <?= csrf_field() ?>
  <?php foreach ($groups as $group => $options): ?>
    <fieldset class="setting-group">
      <legend><?= h($group) ?></legend>
      <?php foreach ($options as $key => $option): ?>
        <fieldset class="setting-option">
          <legend><?= h($option['label']) ?></legend>
          <p><?= h($option['description']) ?></p>
          <div class="setting-choices">
            <?php foreach (['off' => 'Aus', 'on' => 'An', 'test' => 'Test'] as $value => $label): ?>
              <label><input type="radio" name="settings[<?= h($key) ?>]" value="<?= h($value) ?>"<?= $states[$key] === $value ? ' checked' : '' ?> required> <?= h($label) ?></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <fieldset class="setting-group">
    <legend>Standortprüfung</legend>
    <fieldset class="setting-option">
      <legend>Prüfgrenzwerte</legend>
      <p>Wirkt nur, wenn „Standortabhängige Antwortfreigabe“ aktiv ist. Prüfungsradius: so nah (5–5000 m) müssen Besucher am Ziel sein. Max. GPS-Ungenauigkeit: so ungenau (1–500 m) darf die Handy-Ortung sein.</p>
      <label>Prüfungsradius
        <input type="number" name="location[location_radius_m]" min="5" max="5000" step="1" required value="<?= h((string) $locationSettings['location_radius_m']) ?>">
      </label>
      <label>Maximale GPS-Ungenauigkeit
        <input type="number" name="location[location_max_accuracy_m]" min="1" max="500" step="1" required value="<?= h((string) $locationSettings['location_max_accuracy_m']) ?>">
      </label>
    </fieldset>
  </fieldset>
  <fieldset class="setting-group">
    <legend>Credits und Impressum</legend>
    <p>Diese Texte erscheinen in der Ansicht „Mehr“ oberhalb des OpenStreetMap-Hinweises. HTML für Überschriften, Absätze, Listen und Links wird ausgeführt. Links können mit <code>target="_blank" rel="noopener noreferrer"</code> in einem neuen Tab geöffnet werden. Bearbeite die Inhalte nur als vertrauenswürdiger Administrator.</p>
    <label for="public-credits">Credits / Mitwirkende</label>
    <textarea id="public-credits" name="public_text[credits]" maxlength="<?= PUBLIC_TEXT_LIMIT ?>" rows="5"><?= h($publicTexts['credits']) ?></textarea>
    <label for="public-imprint">Impressum</label>
    <textarea id="public-imprint" name="public_text[imprint]" maxlength="<?= PUBLIC_TEXT_LIMIT ?>" rows="8"><?= h($publicTexts['imprint']) ?></textarea>
  </fieldset>
  <fieldset class="setting-group">
    <legend>Navigation zur Station</legend>
    <p>Dieser Text steht in der Aufgabenansicht unter den Buttons „Standort aktualisieren“ und „Route starten“ und erklärt den Unterschied zwischen interner und externer Navigation. Reiner Text, keine HTML-Auszeichnung.</p>
    <label for="public-route-hint">Erläuterungstext</label>
    <textarea id="public-route-hint" name="public_text[routeHint]" maxlength="<?= PUBLIC_TEXT_LIMIT ?>" rows="3"><?= h($publicTexts['routeHint']) ?></textarea>
  </fieldset>
  <div class="admin-toolbar"><button type="submit">Einstellungen speichern</button></div>
</form>
<fieldset class="setting-group">
  <legend>Datenbank sichern</legend>
  <p>Lade eine Sicherung der Datenbank herunter oder stelle eine zuvor exportierte SQLite-Datei wieder her. Der Export erhält den Dateinamen <code>yyyymmdd-ms-unvermutet.sqlite</code>.</p>
  <div class="admin-toolbar">
    <form method="post" action="database-transfer.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="export">
      <button type="submit">Datenbank exportieren</button>
    </form>
  </div>
  <form method="post" action="database-transfer.php" enctype="multipart/form-data" onsubmit="return confirm('Der Import ersetzt die aktuelle Datenbank vollständig. Möchtest du fortfahren?')">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="import">
    <label for="database-file">SQLite-Sicherungsdatei</label>
    <input id="database-file" type="file" name="database_file" accept=".sqlite,.sqlite3,application/vnd.sqlite3" required>
    <div class="admin-toolbar"><button type="submit">Datenbank importieren</button></div>
  </form>
</fieldset>
<?php admin_footer();
