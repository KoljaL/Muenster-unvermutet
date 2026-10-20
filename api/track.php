<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/location.php';
require_method('POST');
require_same_origin();

const STAT_EVENTS = ['opened', 'location_verified', 'hint_used', 'wrong_answer', 'solution_shown', 'solved', 'game_completed'];

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_error(400, 'invalid_json', 'Ungültige Anfrage.');
}

$playerId = $input['playerId'] ?? null;
$event = $input['event'] ?? null;
$stationId = $input['stationId'] ?? null;
$attempts = $input['attempts'] ?? null;

if (!is_string($playerId) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $playerId)) {
    json_error(422, 'invalid_player', 'Ungültige Anfrage.');
}
if (!is_string($event) || !in_array($event, STAT_EVENTS, true)) {
    json_error(422, 'invalid_event', 'Ungültiges Ereignis.');
}
if ($event === 'game_completed') {
    $stationId = null;
} elseif (!is_string($stationId) || !preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $stationId)) {
    json_error(422, 'invalid_station', 'Ungültige Station.');
}
if ($attempts !== null && (!is_int($attempts) || $attempts < 0 || $attempts > 1000)) {
    json_error(422, 'invalid_attempts', 'Ungültige Anfrage.');
}
if (!in_array($event, ['wrong_answer', 'solved'], true)) {
    $attempts = null;
}

// Begrenzung pro zufälliger Kennung, damit keine IP-Adresse verarbeitet werden muss.
if (rate_limit_exceeded('track', $playerId, 120, 60)) {
    json_error(429, 'rate_limited', 'Zu viele Anfragen.');
}
rate_limit_hit('track', $playerId);

if ($stationId !== null) {
    $statement = db()->prepare('SELECT 1 FROM stations WHERE id = ?');
    $statement->execute([$stationId]);
    if (!$statement->fetchColumn()) {
        json_error(404, 'station_not_found', 'Station nicht gefunden.');
    }
}

db()->prepare('INSERT OR IGNORE INTO stat_events (player_id, station_id, event, attempts, created_at) VALUES (?, ?, ?, ?, ?)')
    ->execute([$playerId, $stationId, $event, $attempts, now()]);

json_ok(['recorded' => true], 201);
