<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();
require_post();
$pdo = db();

$mode = ($_POST['mode'] ?? '') === 'update' ? 'update' : 'create';
$origId = is_string($_POST['orig_id'] ?? null) ? $_POST['orig_id'] : '';
$suggestionId = ctype_digit((string) ($_POST['suggestion_id'] ?? '')) && $_POST['suggestion_id'] !== '' ? (int) $_POST['suggestion_id'] : null;

$back = $mode === 'update' ? 'station-edit.php?id=' . urlencode($origId)
    : 'station-edit.php' . ($suggestionId ? '?suggestion=' . $suggestionId : '');

function fail(array $errors, string $back): void
{
    $_SESSION['old_input'] = $_POST;
    flash('err', $errors);
    redirect($back);
}

[$s, $errors] = validate_station_input($_POST);

$existing = null;
if ($mode === 'update') {
    $stmt = $pdo->prepare('SELECT * FROM stations WHERE id = ?');
    $stmt->execute([$origId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash('err', 'Station nicht gefunden.');
        redirect('index.php');
    }
}
$suggestion = null;
if ($mode === 'create' && $suggestionId !== null) {
    $stmt = $pdo->prepare("SELECT * FROM station_suggestions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$suggestionId]);
    $suggestion = $stmt->fetch() ?: null;
    if (!$suggestion) {
        flash('err', 'Vorschlag nicht gefunden oder bereits bearbeitet.');
        redirect('suggestions.php');
    }
}

// ID und Nummer bestimmen
if ($mode === 'update') {
    $id = $origId;
} else {
    $base = slugify($s['title']);
    $id = $base;
    $check = $pdo->prepare('SELECT 1 FROM stations WHERE id = ?');
    for ($n = 2;; $n++) {
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            break;
        }
        $id = $base . '-' . $n;
    }
}
$number = $s['number'] ?? ($mode === 'update' ? (int) $existing['number'] : (int) $pdo->query('SELECT COALESCE(MAX(number), 0) + 1 FROM stations')->fetchColumn());
$check = $pdo->prepare('SELECT 1 FROM stations WHERE number = ? AND id <> ?');
$check->execute([$number, $id]);
if ($check->fetchColumn()) {
    $errors[] = 'Die Nummer ' . $number . ' ist bereits vergeben.';
}

// Bilder
$current = [
    'task_image' => (string) ($existing['task_image'] ?? $suggestion['task_image'] ?? ''),
    'solution_image' => (string) ($existing['solution_image'] ?? $suggestion['solution_image'] ?? ''),
];
$final = $current;
$newFiles = [];
$obsolete = [];
if (!$errors) {
    try {
        foreach (['task_image', 'solution_image'] as $field) {
            if (upload_provided($_FILES[$field] ?? null)) {
                $path = store_upload($_FILES[$field]);
                $newFiles[] = $path;
                $obsolete[] = $current[$field];
                $final[$field] = $path;
            }
        }
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
    if (!$errors && $final['task_image'] === '') {
        $errors[] = 'Ein Aufgabenfoto ist erforderlich.';
    }
}
if ($errors) {
    array_map('delete_upload', $newFiles);
    fail($errors, $back);
}

try {
    $pdo->beginTransaction();
    $ts = now();
    $values = [$number, $s['title'], $s['hint'], $s['lat'], $s['lon'], $s['correct'], $s['solution_title'],
        $s['solution_caption'] ?: null, $s['info'], $final['task_image'], $final['solution_image'] ?: null,
        $s['category'], $s['status']];
    if ($mode === 'update') {
        $pdo->prepare(
            'UPDATE stations SET number=?, title=?, hint=?, lat=?, lon=?, correct_option_index=?, solution_title=?, solution_caption=?,
             info=?, task_image=?, solution_image=?, category=?, status=?, updated_at=? WHERE id=?'
        )->execute(array_merge($values, [$ts, $id]));
        $pdo->prepare('DELETE FROM options WHERE station_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM links WHERE station_id = ?')->execute([$id]);
    } else {
        $pdo->prepare(
            'INSERT INTO stations (number, title, hint, lat, lon, correct_option_index, solution_title, solution_caption,
             info, task_image, solution_image, category, status, id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute(array_merge($values, [$id, $ts, $ts]));
    }
    $opt = $pdo->prepare('INSERT INTO options (station_id, option_index, option_text) VALUES (?, ?, ?)');
    foreach ($s['options'] as $i => $text) {
        $opt->execute([$id, $i, $text]);
    }
    $lnk = $pdo->prepare('INSERT INTO links (station_id, title, url) VALUES (?, ?, ?)');
    foreach ($s['links'] as $l) {
        $lnk->execute([$id, $l['title'], $l['url']]);
    }
    if ($suggestion) {
        $pdo->prepare("UPDATE station_suggestions SET status = 'approved', reviewed_at = ? WHERE id = ?")->execute([$ts, $suggestionId]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    array_map('delete_upload', $newFiles);
    error_log('[muenster-admin] ' . $e);
    fail(['Speichern fehlgeschlagen.'], $back);
}

// Ersetzte Bilder erst nach erfolgreichem Speichern entfernen.
array_map('delete_upload', array_filter($obsolete));
flash('ok', 'Station „' . $s['title'] . '“ gespeichert.');
redirect('index.php');
