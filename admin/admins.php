<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();
$admins = db()->query('SELECT id, username, active, must_change_password FROM admins ORDER BY username')->fetchAll();

admin_header('Admins', $admin);
?>
<h1 class="admin-title">Admins</h1>
<p class="task-intro">Hat jemand sein Passwort vergessen, kannst du es hier zurücksetzen. Du erhältst ein einmaliges Temporärpasswort, das du der Person weitergibst. Beim nächsten Login muss sie ein neues Passwort festlegen. <a href="password.php">Eigenes Passwort ändern</a></p>
<table class="admin-table">
  <thead><tr><th>Benutzer</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($admins as $a): ?>
    <tr>
      <td><?= h($a['username']) ?><?= (int) $a['id'] === $admin['id'] ? ' (du)' : '' ?></td>
      <td><?= $a['must_change_password'] ? 'Neues Passwort ausstehend' : ($a['active'] ? 'Aktiv' : 'Deaktiviert') ?></td>
      <td>
        <?php if ((int) $a['id'] !== $admin['id']): ?>
        <form method="post" action="admin-reset.php" onsubmit="return confirm('Passwort von <?= h($a['username']) ?> zurücksetzen?')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <button class="secondary small">Passwort zurücksetzen</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php admin_footer();
