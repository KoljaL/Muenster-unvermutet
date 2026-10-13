<?php
declare(strict_types=1);

// Nur Kategorien ohne zugeordnete Stationen dürfen gelöscht werden.
require __DIR__ . '/_bootstrap.php';
require_admin();
require_post();

$id = (string) ($_POST['id'] ?? '');
$pdo = db();
$used = $pdo->prepare('SELECT COUNT(*) FROM stations WHERE category = ?');
$used->execute([$id]);
if ((int) $used->fetchColumn() > 0) {
    flash('err', 'Kategorie wird noch von Stationen verwendet.');
    redirect('categories.php');
}
$stmt = $pdo->prepare('SELECT icon FROM categories WHERE id = ?');
$stmt->execute([$id]);
$icon = $stmt->fetchColumn();
$del = $pdo->prepare('DELETE FROM categories WHERE id = ?');
$del->execute([$id]);
if ($del->rowCount()) {
    delete_upload($icon ?: null);
    flash('ok', 'Kategorie gelöscht.');
} else {
    flash('err', 'Kategorie nicht gefunden.');
}
redirect('categories.php');
