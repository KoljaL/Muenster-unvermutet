<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin(true);
$forced = !empty($_SESSION['must_change_password']);

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $current = is_string($_POST['current'] ?? null) ? $_POST['current'] : '';
    $new = is_string($_POST['new'] ?? null) ? $_POST['new'] : '';
    $repeat = is_string($_POST['repeat'] ?? null) ? $_POST['repeat'] : '';
    $ip = client_ip();
    $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $hash = (string) $stmt->fetchColumn();

    if (rate_limit_exceeded('password', $ip, 10, 900)) {
        $error = 'Zu viele Versuche. Bitte später erneut versuchen.';
    } elseif (!password_verify($current, $hash)) {
        rate_limit_hit('password', $ip);
        $error = 'Das aktuelle Passwort ist falsch.';
    } elseif (mb_strlen($new) < 10) {
        $error = 'Das neue Passwort muss mindestens 10 Zeichen lang sein.';
    } elseif ($new !== $repeat) {
        $error = 'Die beiden neuen Passwörter stimmen nicht überein.';
    } elseif ($new === $current) {
        $error = 'Das neue Passwort muss sich vom alten unterscheiden.';
    } else {
        db()->prepare('UPDATE admins SET password_hash = ?, must_change_password = 0 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        $_SESSION['must_change_password'] = 0;
        session_regenerate_id(true);
        flash('ok', 'Passwort geändert.');
        redirect('index.php');
    }
}

admin_header('Passwort ändern', $forced ? null : $admin);
?>
<h1 class="admin-title">Passwort ändern</h1>
<?php if ($forced): ?><p class="task-intro">Dein Passwort wurde zurückgesetzt. Bitte lege ein neues fest. Als aktuelles Passwort gibst du das Temporärpasswort ein.</p><?php endif; ?>
<?php if ($error): ?><div class="error-message" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" autocomplete="off" style="max-width:360px">
  <?= csrf_field() ?>
  <label for="current">Aktuelles Passwort</label>
  <input type="password" id="current" name="current" required autofocus>
  <label for="new">Neues Passwort (mind. 10 Zeichen)</label>
  <input type="password" id="new" name="new" required minlength="10">
  <label for="repeat">Neues Passwort wiederholen</label>
  <input type="password" id="repeat" name="repeat" required minlength="10">
  <p><button class="next-button">Speichern</button></p>
</form>
<?php if ($forced): ?>
<form method="post" action="logout.php"><?= csrf_field() ?><button class="secondary small">Abmelden</button></form>
<?php endif; ?>
<?php admin_footer();
