<?php
declare(strict_types=1);

// Einmaliger Import der 14 Stationen. Nur per CLI: php setup/migrate_stations.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/uploads.php';

$seed = json_decode((string) file_get_contents(__DIR__ . '/stations_seed.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = db();
$root = config('root');

function find_photo(string $root, string $name): ?string
{
    foreach ([$root, $root . '/OLD', $root . '/setup/photos'] as $dir) {
        if (is_file($dir . '/' . $name)) {
            return $dir . '/' . $name;
        }
    }
    return null;
}

function import_photo(string $root, string $name): string
{
    $path = find_photo($root, $name);
    if ($path === null) {
        fwrite(STDERR, "  Hinweis: {$name} nicht gefunden – Bild bitte im Admin-Bereich hochladen.\n");
        return '';
    }
    try {
        return store_image_file($path, false);
    } catch (RuntimeException $e) {
        fwrite(STDERR, "  Hinweis: {$name}: {$e->getMessage()}\n");
        return '';
    }
}

$imported = 0;
$exists = $pdo->prepare('SELECT 1 FROM stations WHERE id = ?');
foreach ($seed as $s) {
    $exists->execute([$s['id']]);
    if ($exists->fetchColumn()) {
        echo "Übersprungen (vorhanden): {$s['id']}\n";
        continue;
    }
    echo "Importiere {$s['number']} {$s['id']}\n";
    $task = import_photo($root, 'Aufgabe ' . $s['number'] . '.jpg');
    $solution = import_photo($root, 'Loesung ' . $s['number'] . '.jpg');

    $pdo->beginTransaction();
    $ts = now();
    $pdo->prepare(
        "INSERT INTO stations (id, number, title, hint, lat, lon, correct_option_index, solution_title,
          solution_caption, info, task_image, solution_image, category, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', ?, ?)"
    )->execute([
        $s['id'], $s['number'], $s['title'], $s['hint'], $s['lat'], $s['lon'], $s['correctOptionIndex'],
        $s['solutionTitle'], $s['solutionCaption'] ?? null, $s['info'], $task, $solution !== '' ? $solution : null,
        $s['category'], $ts, $ts,
    ]);
    $opt = $pdo->prepare('INSERT INTO options (station_id, option_index, option_text) VALUES (?, ?, ?)');
    foreach ($s['options'] as $i => $text) {
        $opt->execute([$s['id'], $i, $text]);
    }
    $lnk = $pdo->prepare('INSERT INTO links (station_id, title, url) VALUES (?, ?, ?)');
    foreach ($s['links'] ?? [] as $l) {
        $lnk->execute([$s['id'], $l['title'], $l['url']]);
    }
    $pdo->commit();
    $imported++;
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM stations')->fetchColumn();
echo "Fertig: {$imported} importiert, {$total} Stationen in der Datenbank (erwartet: " . count($seed) . ").\n";
