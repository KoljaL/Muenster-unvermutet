<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

$categories = db()->query(
    'SELECT c.id, c.name, c.description, c.icon, c.color, COUNT(s.id) AS stations
     FROM categories c LEFT JOIN stations s ON s.category = c.id
     GROUP BY c.id ORDER BY c.name COLLATE NOCASE'
)->fetchAll();

admin_header('Kategorien', $admin);
?>
<h1 class="admin-title">Kategorien</h1>
<p class="task-intro">Kategorien gruppieren Stationen und erscheinen mit Bild und Farbe in Karte und Legende.</p>
<div class="admin-toolbar"><a class="btn" href="category-edit.php">+ Neue Kategorie</a></div>
<ul class="station-list">
  <?php foreach ($categories as $c): ?>
  <li class="station-item">
    <span class="ico" style="border-color: <?= h($c['color']) ?>"><?= category_ico($c) ?></span>
    <a class="admin-main" href="category-edit.php?id=<?= h(urlencode($c['id'])) ?>"><?= h($c['name']) ?>
      <small><?= h($c['description']) ?></small></a>
    <span class="badge"><?= (int) $c['stations'] ?> Stationen</span>
    <span class="admin-actions">
      <a class="btn secondary small" href="category-edit.php?id=<?= h(urlencode($c['id'])) ?>">Bearbeiten</a>
      <?php if ((int) $c['stations'] === 0): ?>
      <form method="post" action="category-delete.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= h($c['id']) ?>">
        <button class="danger small" onclick="return confirm('Kategorie wirklich löschen?')">Löschen</button>
      </form>
      <?php endif; ?>
    </span>
  </li>
  <?php endforeach; ?>
</ul>
<?php admin_footer();
