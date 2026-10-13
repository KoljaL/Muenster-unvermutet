<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

$stations = db()->query('SELECT id, number, title, category, status, task_image FROM stations ORDER BY number')->fetchAll();
$pending = (int) db()->query("SELECT COUNT(*) FROM station_suggestions WHERE status = 'pending'")->fetchColumn();

admin_header('Stationen', $admin);
?>
<h1>Stationen</h1>
<p><a class="btn" href="station-edit.php">+ Neue Station</a>
   <a class="btn secondary" href="suggestions.php">Vorschläge (<?= $pending ?> offen)</a></p>
<table>
  <tr><th>Nr.</th><th>Titel</th><th>Kategorie</th><th>Status</th><th>Aktionen</th></tr>
  <?php foreach ($stations as $s): ?>
  <tr>
    <td><?= (int) $s['number'] ?></td>
    <td><?= h($s['title']) ?><br><span class="muted"><?= h($s['id']) ?><?= $s['task_image'] === '' ? ' – ⚠ Aufgabenbild fehlt' : '' ?></span></td>
    <td><?= h($s['category']) ?></td>
    <td><?= $s['status'] === 'published' ? 'veröffentlicht' : 'archiviert' ?></td>
    <td>
      <a href="station-edit.php?id=<?= h(urlencode($s['id'])) ?>">Bearbeiten</a>
      <form method="post" action="station-delete.php" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= h($s['id']) ?>">
        <input type="hidden" name="status" value="<?= $s['status'] === 'published' ? 'archived' : 'published' ?>">
        <button class="secondary"><?= $s['status'] === 'published' ? 'Archivieren' : 'Wiederherstellen' ?></button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php admin_footer();
