<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $path = config('db_path');
        $isNew = !is_file($path);
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        if ($isNew) {
            @chmod($path, 0660);
        }
        $pdo->exec((string) file_get_contents(config('schema_path')));
        $stationColumns = $pdo->query('PRAGMA table_info(stations)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('teaser_text', $stationColumns, true)) {
            $pdo->exec('ALTER TABLE stations ADD COLUMN teaser_text TEXT');
        }
    }
    return $pdo;
}

function now(): string
{
    return date('c');
}

/** Zählt Ereignisse im Zeitfenster und prüft das Limit. */
function rate_limit_exceeded(string $bucket, string $key, int $max, int $window): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND key = ? AND created_at > ?');
    $stmt->execute([$bucket, $key, time() - $window]);
    return (int) $stmt->fetchColumn() >= $max;
}

function rate_limit_hit(string $bucket, string $key): void
{
    db()->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([time() - 86400]);
    db()->prepare('INSERT INTO rate_limits (bucket, key, created_at) VALUES (?, ?, ?)')
        ->execute([$bucket, $key, time()]);
}

function rate_limit_clear(string $bucket, string $key): void
{
    db()->prepare('DELETE FROM rate_limits WHERE bucket = ? AND key = ?')->execute([$bucket, $key]);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
