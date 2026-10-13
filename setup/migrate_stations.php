<?php
declare(strict_types=1);

// Import der Stationen und Beispielvorschläge. Nur per CLI: php setup/migrate_stations.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/uploads.php';

$seed = json_decode((string) file_get_contents(__DIR__ . '/stations_seed.json'), true, 512, JSON_THROW_ON_ERROR);
$suggestionSeed = json_decode((string) file_get_contents(__DIR__ . '/suggestions_seed.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = db();
$root = config('root');

function find_photo(string $root, string $name): ?string
{
    foreach ([$root, $root . '/OLD', $root . '/setup/images', $root . '/setup/photos'] as $dir) {
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
$exists = $pdo->prepare('SELECT task_image, solution_image FROM stations WHERE id = ?');
foreach ($seed as $s) {
    $exists->execute([$s['id']]);
    $row = $exists->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) {
        // Bereits vorhandene Stationen: fehlende Bilder nachtragen
        $fix = [];
        if (($row['task_image'] ?? '') === '') {
            $p = import_photo($root, 'Aufgabe ' . $s['number'] . '.jpg');
            if ($p !== '') {
                $fix['task_image'] = $p;
            }
        }
        if (($row['solution_image'] ?? '') === '') {
            $p = import_photo($root, 'Loesung ' . $s['number'] . '.jpg');
            if ($p !== '') {
                $fix['solution_image'] = $p;
            }
        }
        foreach ($fix as $col => $val) {
            $pdo->prepare("UPDATE stations SET {$col} = ?, updated_at = ? WHERE id = ?")->execute([$val, now(), $s['id']]);
        }
        echo ($fix ? 'Bilder nachgetragen: ' : 'Übersprungen (vorhanden): ') . "{$s['id']}\n";
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

$suggestionExists = $pdo->prepare('SELECT 1 FROM station_suggestions WHERE title = ? LIMIT 1');
$suggestionInsert = $pdo->prepare(
    'INSERT INTO station_suggestions
        (title, hint, lat, lon, question, options_json, correct_option_index, info,
         suggested_by_name, suggested_by_email, task_image, solution_image, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)'
);
$suggestionsImported = 0;
foreach ($suggestionSeed as $suggestion) {
    $suggestionExists->execute([$suggestion['title']]);
    if ($suggestionExists->fetchColumn()) {
        echo "Vorschlag übersprungen (vorhanden): {$suggestion['title']}\n";
        continue;
    }

    $taskImage = import_photo($root, $suggestion['taskImage']);
    $suggestionInsert->execute([
        $suggestion['title'],
        $suggestion['hint'],
        $suggestion['lat'],
        $suggestion['lon'],
        $suggestion['question'],
        json_encode($suggestion['options'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        $suggestion['correctOptionIndex'],
        $suggestion['info'],
        $suggestion['suggestedByName'],
        $suggestion['suggestedByEmail'],
        $taskImage !== '' ? $taskImage : null,
        'pending',
        now(),
    ]);
    $suggestionsImported++;
    echo "Beispielvorschlag angelegt: {$suggestion['title']}\n";
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM stations')->fetchColumn();
$pendingSuggestions = (int) $pdo->query("SELECT COUNT(*) FROM station_suggestions WHERE status = 'pending'")->fetchColumn();
echo "Fertig: {$imported} importiert, {$total} Stationen in der Datenbank (erwartet: " . count($seed) . ").\n";
echo "Vorschläge: {$suggestionsImported} neu angelegt, {$pendingSuggestions} offene Vorschläge insgesamt.\n";
