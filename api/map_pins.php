<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_method('GET');

$rows = db()->query(
    "SELECT id, number, title, lat, lon, category FROM stations WHERE status = 'published' ORDER BY number"
)->fetchAll();

json_ok(array_map(static fn(array $r): array => [
    'id' => $r['id'],
    'number' => (int) $r['number'],
    'title' => $r['title'],
    'lat' => (float) $r['lat'],
    'lon' => (float) $r['lon'],
    'category' => $r['category'],
], $rows));
