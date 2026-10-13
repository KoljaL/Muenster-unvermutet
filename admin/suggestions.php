<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

$rows = db()->query("SELECT * FROM station_suggestions WHERE status = 'pending' ORDER BY created_at")->fetchAll();

admin_header('Vorschläge', $admin);
?>
<h1 class="admin-title">Offene Vorschläge</h1>
<?php if (!$rows): ?><p>Keine offenen Vorschläge.</p><?php endif; ?>
<?php foreach ($rows as $r): ?>
  <fieldset class="task-card">
    <legend><a href="station-edit.php?suggestion=<?= (int) $r['id'] ?>"><?= h($r['title']) ?></a> <span class="muted">(<?= h($r['created_at']) ?>)</span></legend>
    <p><?= nl2br(h($r['info'])) ?></p>
    <p class="muted">Frage: <?= h($r['question']) ?> · Koordinaten: <?= h($r['lat']) ?>, <?= h($r['lon']) ?><br>
       Von: <?= h($r['suggested_by_name'] ?: 'anonym') ?> <?= h($r['suggested_by_email']) ?></p>
    <?php foreach (['task_image', 'solution_image'] as $f): if ($r[$f] && is_managed_upload($r[$f])): ?>
      <img class="thumb" src="../<?= h($r[$f]) ?>" alt="<?= h($f) ?>">
    <?php endif; endforeach; ?>
    <a class="btn small" href="station-edit.php?suggestion=<?= (int) $r['id'] ?>">Prüfen &amp; freigeben</a>
    <form method="post" action="suggestion-action.php" style="display:inline">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
      <input type="hidden" name="action" value="reject">
      <button class="danger small" onclick="return confirm('Vorschlag ablehnen?')">Ablehnen</button>
    </form>
  </fieldset>
<?php endforeach; ?>
<?php admin_footer();
