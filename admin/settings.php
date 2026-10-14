<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/settings.php';
$admin = require_admin();
$states = ui_setting_states();
$groups = [];
foreach (UI_REGISTRY as $key => $definition) {
    $groups[$definition['group']][$key] = $definition;
}

admin_header('Einstellungen', $admin);
?>
<h1 class="admin-title">Feature-Einstellungen</h1>
<p class="task-intro">Diese Einstellungen gelten global für alle Besucher. Features im Testmodus sind nur mit passendem Vorschau-Parameter sichtbar, zum Beispiel <code>?preview=all</code>.</p>
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
  <div class="admin-toolbar"><button type="submit">Einstellungen speichern</button></div>
</form>
<?php admin_footer();
