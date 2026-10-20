<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/settings.php';
require_method('GET');

$hasPreview = array_key_exists('preview', $_GET);
if ($hasPreview) {
    header('Cache-Control: no-store');
} else {
    header('Cache-Control: public, max-age=60');
}

$preview = is_string($_GET['preview'] ?? null) ? $_GET['preview'] : null;
$resolved = resolve_ui_settings($preview);
$data = [];
foreach ($resolved as $key => $enabled) {
    $data[substr($key, 3)] = $enabled;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$location = location_settings();
echo json_encode([
    'ok' => true,
    'data' => [
        'ui' => $data,
        'location' => [
            'enabled' => $data['location_gate'] ?? false,
            'radius' => $location['location_radius_m'],
            'maxAccuracy' => $location['location_max_accuracy_m'],
        ],
        'publicText' => public_text_settings(),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
