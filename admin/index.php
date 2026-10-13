<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

$stations = db()->query('SELECT id, title, category, status, task_image FROM stations ORDER BY number')->fetchAll();
$categories = array_column(all_categories(), null, 'id');
$pending = (int) db()->query("SELECT COUNT(*) FROM station_suggestions WHERE status = 'pending'")->fetchColumn();

admin_header('Stationen', $admin);
?>
<h1 class="admin-title">Stationen</h1>
<p class="page-subtitle task-intro"><?= count($stations) ?> Stationen · <?= $pending ?> offene Vorschläge</p>
<div class="admin-toolbar">
  <a class="btn" href="station-edit.php">+ Neue Station</a>
  <a class="btn secondary" href="suggestions.php">Vorschläge (<?= $pending ?>)</a>
</div>
<?php if (!$stations): ?><p>Noch keine Stationen vorhanden.</p><?php endif; ?>
<ul class="station-list">
  <?php foreach ($stations as $s): $c = $categories[$s['category']] ?? null; $archived = $s['status'] !== 'published'; ?>
  <li class="station-item<?= $archived ? ' station-item--archived' : '' ?>">
    <span class="ico" aria-hidden="true"><?= category_ico($c) ?></span>
    <a class="admin-main" href="station-edit.php?id=<?= h(urlencode($s['id'])) ?>"><?= h($s['title']) ?>
      <small><?= h($c['name'] ?? $s['category']) ?><?= $s['task_image'] === '' ? ' · ⚠ Aufgabenbild fehlt' : '' ?></small></a>
    <span class="badge<?= $archived ? ' badge--archived' : '' ?>"><?= $archived ? 'archiviert' : 'veröffentlicht' ?></span>
    <span class="admin-actions">
      <a class="btn secondary small" href="station-edit.php?id=<?= h(urlencode($s['id'])) ?>">Bearbeiten</a>
    </span>
  </li>
  <?php endforeach; ?>
</ul>
<?php admin_footer();
