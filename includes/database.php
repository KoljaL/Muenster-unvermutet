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
        $schema = is_readable(config('schema_path')) ? trim((string) file_get_contents(config('schema_path'))) : '';
        if ($schema !== '') {
            $pdo->exec($schema);
        } elseif ($isNew) {
            throw new RuntimeException('Schema-Datei fehlt oder ist leer: ' . config('schema_path'));
        }
        $stationColumns = $pdo->query('PRAGMA table_info(stations)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('teaser_text', $stationColumns, true)) {
            $pdo->exec('ALTER TABLE stations ADD COLUMN teaser_text TEXT');
        }
        $errorColumns = $pdo->query('PRAGMA table_info(client_errors)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('origin', $errorColumns, true)) {
            $pdo->exec("ALTER TABLE client_errors ADD COLUMN origin TEXT NOT NULL DEFAULT 'client'");
        }
        $adminColumns = $pdo->query('PRAGMA table_info(admins)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('must_change_password', $adminColumns, true)) {
            $pdo->exec('ALTER TABLE admins ADD COLUMN must_change_password INTEGER NOT NULL DEFAULT 0');
        }
    }
    return $pdo;
}

function now(): string
{
    return date('c');
}

/** Protokolliert einen Serverfehler in error_log und in der Tabelle client_errors (Admin: Fehlerprotokoll). */
function log_server_error(string $context, $error): void
{
    $isThrowable = $error instanceof Throwable;
    error_log('[' . $context . '] ' . ($isThrowable ? (string) $error : (string) $error));
    try {
        $message = $isThrowable ? get_class($error) . ': ' . $error->getMessage() : (string) $error;
        $source = $isThrowable ? basename($error->getFile()) . ':' . $error->getLine() : null;
        $stack = $isThrowable ? $error->getTraceAsString() : null;
        $url = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        db()->prepare("INSERT INTO client_errors (origin, message, source, stack, url, user_agent, created_at) VALUES ('server', ?, ?, ?, ?, NULL, ?)")
            ->execute([
                mb_substr($context . ' – ' . $message, 0, 500),
                $source,
                $stack === null ? null : mb_substr($stack, 0, 3000),
                is_string($url) ? mb_substr($url, 0, 300) : null,
                now(),
            ]);
        db()->exec('DELETE FROM client_errors WHERE id <= (SELECT MAX(id) FROM client_errors) - 1000');
    } catch (Throwable $ignored) {
        // Datenbank selbst defekt: error_log oben ist der Fallback.
    }
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
