<?php
declare(strict_types=1);

function start_game_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.gc_maxlifetime', (string) (30 * 24 * 60 * 60));
    session_name('muenster_game');
    session_set_cookie_params([
        'lifetime' => 30 * 24 * 60 * 60,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function location_distance_m(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371000;
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;
    return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function require_same_origin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!is_string($origin) || $origin === '' || $host === '') {
        json_error(403, 'invalid_origin', 'Die Anfrage konnte nicht geprüft werden.');
    }
    $originHost = parse_url($origin, PHP_URL_HOST);
    $originPort = parse_url($origin, PHP_URL_PORT);
    $requestHost = parse_url('//' . $host, PHP_URL_HOST);
    $requestPort = parse_url('//' . $host, PHP_URL_PORT);
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $originScheme = parse_url($origin, PHP_URL_SCHEME);
    if (
        !is_string($originHost)
        || !is_string($requestHost)
        || strcasecmp($originHost, $requestHost) !== 0
        || $originPort !== $requestPort
        || $originScheme !== ($secure ? 'https' : 'http')
    ) {
        json_error(403, 'invalid_origin', 'Die Anfrage konnte nicht geprüft werden.');
    }
}
