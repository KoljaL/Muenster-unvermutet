<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/location.php';
require_method('GET');

$id = $_GET['id'] ?? '';
if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $id)) {
    json_error(400, 'invalid_id', 'Ungültige Stations-ID.');
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) {
    json_error(404, 'station_not_found', 'Station nicht gefunden.');
}

$preview = is_string($_GET['preview'] ?? null) ? $_GET['preview'] : null;
$locationGate = location_gate_enabled($preview);
$protected = !$locationGate;
if ($locationGate && ($_GET['protected'] ?? '') === '1') {
    start_game_session();
    $protected = !empty($_SESSION['verified_stations'][$id]);
    if (!$protected) {
        json_error(403, 'location_not_verified', 'Der Standort wurde für diese Station noch nicht bestätigt.');
    }
}

$data = [
    'id' => $s['id'],
    'number' => (int) $s['number'],
    'title' => $s['title'],
    'lat' => (float) $s['lat'],
    'lon' => (float) $s['lon'],
    'category' => $s['category'],
    'hint' => $s['hint'],
    'taskImage' => $s['task_image'],
];
if (!$locationGate || $protected) {
    $stmt = $pdo->prepare('SELECT option_text FROM options WHERE station_id = ? ORDER BY option_index');
    $stmt->execute([$id]);
    $options = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare('SELECT title, url FROM links WHERE station_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $links = $stmt->fetchAll();

    $data = array_merge($data, [
        'options' => $options,
        'correctOptionIndex' => (int) $s['correct_option_index'],
        'solutionTitle' => $s['solution_title'],
        'solutionCaption' => $s['solution_caption'],
        'info' => $s['info'],
        'solutionImage' => $s['solution_image'],
        'links' => $links,
    ]);
}

json_ok($data);
