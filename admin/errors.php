<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

$origins = ['' => 'Alle', 'client' => 'Spiel (Browser)', 'server' => 'Server'];
$origin = is_string($_GET['origin'] ?? null) && isset($origins[$_GET['origin']]) ? $_GET['origin'] : '';
$statement = db()->prepare('SELECT origin, created_at, message, source, stack, url, user_agent FROM client_errors WHERE (? = \'\' OR origin = ?) ORDER BY id DESC LIMIT 100');
$statement->execute([$origin, $origin]);
$errors = $statement->fetchAll();

admin_header('Fehlerprotokoll', $admin);
?>
<h1 class="admin-title">Fehlerprotokoll</h1>
<p class="task-intro">Die letzten 100 Fehler aus dem Spiel (JavaScript im Browser) und vom Server (PHP). Es werden keine IP-Adressen gespeichert; die Browser-Kennung ist nur mit Einwilligung zur Statistik enthalten.</p>
<nav class="admin-toolbar" aria-label="Herkunft">
  <?php foreach ($origins as $value => $label): ?>
    <a class="btn small<?= $value === $origin ? '' : ' secondary' ?>" href="errors.php<?= $value === '' ? '' : '?origin=' . $value ?>"<?= $value === $origin ? ' aria-current="true"' : '' ?>><?= h($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php if (!$errors): ?>
  <p>Keine Fehler protokolliert.</p>
<?php else: ?>
  <div class="stat-table-wrap"><table class="stat-table">
    <thead><tr><th>Zeit</th><th>Herkunft</th><th>Meldung</th><th>Ort</th><th>Seite</th><th>Browser</th></tr></thead>
    <tbody>
    <?php foreach ($errors as $e): ?>
      <tr>
        <td><?= h(date('d.m.Y H:i', (int) strtotime($e['created_at']))) ?></td>
        <td><?= $e['origin'] === 'server' ? 'Server' : 'Spiel' ?></td>
        <td><?= h($e['message']) ?><?php if ($e['stack']): ?><details><summary>Stack</summary><pre><?= h($e['stack']) ?></pre></details><?php endif; ?></td>
        <td><?= h((string) $e['source']) ?></td>
        <td><?= h((string) $e['url']) ?></td>
        <td><?= h((string) $e['user_agent']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
<?php admin_footer();
