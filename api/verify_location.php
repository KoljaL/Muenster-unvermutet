<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/location.php';
require_method('POST');

$preview = is_string($_GET['preview'] ?? null) ? $_GET['preview'] : null;
if (!location_gate_enabled($preview)) {
    json_error(403, 'location_gate_disabled', 'Die Standortprüfung ist nicht aktiviert.');
}

require_same_origin();
$ip = client_ip();
if (rate_limit_exceeded('location', $ip, 30, 60)) {
    json_error(429, 'rate_limited', 'Zu viele Standortprüfungen. Bitte später erneut versuchen.');
}
rate_limit_hit('location', $ip);

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_error(400, 'invalid_json', 'Ungültige Anfrage.');
}

$id = $input['id'] ?? null;
$lat = $input['lat'] ?? null;
$lon = $input['lon'] ?? null;
$accuracy = $input['accuracy'] ?? null;
$timestamp = $input['timestamp'] ?? null;
if (
    !is_string($id)
    || !preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $id)
    || (!is_int($lat) && !is_float($lat))
    || (!is_int($lon) && !is_float($lon))
    || (!is_int($accuracy) && !is_float($accuracy))
    || (!is_int($timestamp) && !is_float($timestamp))
    || !is_finite((float) $lat)
    || !is_finite((float) $lon)
    || !is_finite((float) $accuracy)
    || !is_finite((float) $timestamp)
    || !valid_coordinates((float) $lat, (float) $lon)
    || (float) $accuracy <= 0
) {
    json_error(422, 'invalid_location', 'Ungültige Standortdaten.');
}

$nowMilliseconds = (int) floor(microtime(true) * 1000);
if ((float) $timestamp < $nowMilliseconds - 120000 || (float) $timestamp > $nowMilliseconds + 30000) {
    json_error(422, 'stale_location', 'Der Standort ist nicht mehr aktuell. Bitte erneut prüfen.');
}

$statement = db()->prepare("SELECT lat, lon FROM stations WHERE id = ? AND status = 'published'");
$statement->execute([$id]);
$station = $statement->fetch();
if (!$station) {
    json_error(404, 'station_not_found', 'Station nicht gefunden.');
}

$settings = location_settings();
$distance = location_distance_m((float) $lat, (float) $lon, (float) $station['lat'], (float) $station['lon']);
$result = [
    'success' => false,
    'distance' => (int) ceil($distance),
    'radius' => $settings['location_radius_m'],
    'reason' => 'too_far',
];
if ((float) $accuracy > $settings['location_max_accuracy_m']) {
    $result['reason'] = 'poor_accuracy';
} elseif ($distance <= $settings['location_radius_m']) {
    $result['success'] = true;
    $result['reason'] = 'verified';
    start_game_session();
    session_regenerate_id(true);
    $_SESSION['verified_stations'][$id] = true;
}

json_ok($result);
