<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/location.php';
require_method('POST');
require_same_origin();

const MAX_STORED_ERRORS = 1000;

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input) || !is_string($input['message'] ?? null) || trim($input['message']) === '') {
    json_error(400, 'invalid_json', 'Ungültige Anfrage.');
}

$playerId = $input['playerId'] ?? null;
if ($playerId !== null && (!is_string($playerId) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $playerId))) {
    $playerId = null;
}

// Die IP wird nur für das Limit genutzt (als Hash) und nicht im Protokoll gespeichert.
$limitKey = hash('sha256', client_ip());
if (rate_limit_exceeded('client_error', $limitKey, 10, 60)) {
    json_error(429, 'rate_limited', 'Zu viele Anfragen.');
}
rate_limit_hit('client_error', $limitKey);

$cut = static fn($value, int $max): ?string => is_string($value) && $value !== '' ? mb_substr($value, 0, $max) : null;

$pdo = db();
$pdo->prepare('INSERT INTO client_errors (player_id, message, source, stack, url, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
    ->execute([
        $playerId,
        $cut($input['message'], 500),
        $cut($input['source'] ?? null, 300),
        $cut($input['stack'] ?? null, 3000),
        $cut($input['url'] ?? null, 300),
        $cut($_SERVER['HTTP_USER_AGENT'] ?? null, 300),
        now(),
    ]);
$pdo->prepare('DELETE FROM client_errors WHERE id <= (SELECT MAX(id) FROM client_errors) - ?')->execute([MAX_STORED_ERRORS]);

json_ok(['recorded' => true], 201);
