<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();
require_post();

$id = (int) ($_POST['id'] ?? 0);
if ($id === $admin['id']) {
    flash('err', 'Das eigene Passwort ändere bitte unter „Eigenes Passwort ändern“.');
    redirect('admins.php');
}
$temporary = generate_temporary_password();
$stmt = db()->prepare('UPDATE admins SET password_hash = ?, must_change_password = 1 WHERE id = ? AND active = 1');
$stmt->execute([password_hash($temporary, PASSWORD_DEFAULT), $id]);
if ($stmt->rowCount()) {
    $name = db()->prepare('SELECT username FROM admins WHERE id = ?');
    $name->execute([$id]);
    flash('ok', 'Temporärpasswort für ' . $name->fetchColumn() . ': ' . $temporary . ' (wird nur jetzt angezeigt)');
} else {
    flash('err', 'Admin nicht gefunden oder deaktiviert.');
}
redirect('admins.php');
