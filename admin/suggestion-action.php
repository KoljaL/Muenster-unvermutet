<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();
require_post();

$id = (int) ($_POST['id'] ?? 0);
if (($_POST['action'] ?? '') === 'reject') {
    $stmt = db()->prepare("SELECT task_image, solution_image FROM station_suggestions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        db()->prepare("UPDATE station_suggestions SET status = 'rejected', reviewed_at = ? WHERE id = ?")->execute([now(), $id]);
        delete_upload($row['task_image']);
        delete_upload($row['solution_image']);
        flash('ok', 'Vorschlag abgelehnt.');
    } else {
        flash('err', 'Vorschlag nicht gefunden.');
    }
}
redirect('suggestions.php');
