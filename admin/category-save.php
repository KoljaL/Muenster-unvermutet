<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();
require_post();
$pdo = db();

$mode = ($_POST['mode'] ?? '') === 'update' ? 'update' : 'create';
$origId = is_string($_POST['orig_id'] ?? null) ? $_POST['orig_id'] : '';
$back = $mode === 'update' ? 'category-edit.php?id=' . urlencode($origId) : 'category-edit.php';

function fail(array $errors, string $back): void
{
    $_SESSION['old_input'] = $_POST;
    flash('err', $errors);
    redirect($back);
}

$name = clean_text($_POST['name'] ?? '', 60);
$description = clean_text($_POST['description'] ?? '', 300, true);
$color = is_string($_POST['color'] ?? null) ? $_POST['color'] : '';
$errors = $name === '' ? ['Name ist erforderlich.'] : [];
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $errors[] = 'Bitte wähle eine gültige Farbe.';
}

$existing = null;
if ($mode === 'update') {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$origId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash('err', 'Kategorie nicht gefunden.');
        redirect('categories.php');
    }
    $id = $origId;
} else {
    $base = slugify($name);
    $id = $base;
    $check = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
    for ($n = 2; ; $n++) {
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            break;
        }
        $id = $base . '-' . $n;
    }
}
$dup = $pdo->prepare('SELECT 1 FROM categories WHERE name = ? COLLATE NOCASE AND id <> ?');
$dup->execute([$name, $id]);
if ($dup->fetchColumn()) {
    $errors[] = 'Eine Kategorie mit diesem Namen existiert bereits.';
}

$icon = $existing['icon'] ?? null;
$newFile = null;
$obsolete = null;
if (!$errors) {
    try {
        if (upload_provided($_FILES['icon'] ?? null)) {
            $newFile = store_upload($_FILES['icon']);
            $obsolete = $icon;
            $icon = $newFile;
        } elseif (!empty($_POST['remove_icon'])) {
            $obsolete = $icon;
            $icon = null;
        }
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}
if ($errors) {
    delete_upload($newFile);
    fail($errors, $back);
}

try {
    $ts = now();
    if ($mode === 'update') {
        $pdo->prepare('UPDATE categories SET name=?, description=?, icon=?, color=?, updated_at=? WHERE id=?')
            ->execute([$name, $description, $icon, $color, $ts, $id]);
    } else {
        $pdo->prepare('INSERT INTO categories (id, name, description, icon, color, created_at, updated_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([$id, $name, $description, $icon, $color, $ts, $ts]);
    }
} catch (Throwable $e) {
    delete_upload($newFile);
    error_log('[muenster-admin] ' . $e);
    fail(['Speichern fehlgeschlagen.'], $back);
}

delete_upload($obsolete);
flash('ok', 'Kategorie „' . $name . '“ gespeichert.');
redirect('categories.php');
