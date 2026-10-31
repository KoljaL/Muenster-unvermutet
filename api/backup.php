<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup.php';
require_method('GET');

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    json_error(403, 'forbidden', 'Zugriff verweigert.');
}

$configuredToken = config('backup_token');
$providedToken = $_GET['token'] ?? null;
if (!is_string($configuredToken) || $configuredToken === ''
    || !is_string($providedToken) || !hash_equals($configuredToken, $providedToken)) {
    json_error(403, 'forbidden', 'Zugriff verweigert.');
}

try {
    create_database_backup((string) config('db_path'), config('root') . '/data/backups');
} catch (Throwable $error) {
    log_server_error('database-backup', $error);
    json_error(500, 'backup_failed', 'Die Datenbanksicherung konnte nicht erstellt werden.');
}

json_send(200, ['ok' => true]);
