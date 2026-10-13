<?php
declare(strict_types=1);

// Stationen werden nicht physisch gelöscht, sondern archiviert bzw. wiederhergestellt.
require __DIR__ . '/_bootstrap.php';
require_admin();
require_post();

$id = (string) ($_POST['id'] ?? '');
$status = ($_POST['status'] ?? '') === 'archived' ? 'archived' : 'published';
$stmt = db()->prepare('UPDATE stations SET status = ?, updated_at = ? WHERE id = ?');
$stmt->execute([$status, now(), $id]);
flash($stmt->rowCount() ? 'ok' : 'err', $stmt->rowCount()
    ? ($status === 'archived' ? 'Station archiviert.' : 'Station wiederhergestellt.')
    : 'Station nicht gefunden.');
redirect('index.php');
