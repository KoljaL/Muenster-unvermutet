<?php
declare(strict_types=1);

/**
 * Zentrale Konfiguration. Alle Pfade werden relativ zum Dateisystem aufgelöst,
 * nicht relativ zur URL. Lokale Überschreibungen: includes/config.local.php
 * (wird nicht ausgeliefert und enthält z. B. einen anderen DB-Pfad).
 */
function config(string $key = null)
{
    static $config = null;
    if ($config === null) {
        $root = dirname(__DIR__);
        $config = [
            'root' => $root,
            'db_path' => $root . '/data/game.sqlite',
            'schema_path' => $root . '/setup/schema.sql',
            'upload_dir' => $root . '/uploads',
            'upload_url' => 'uploads', // relativ zur index.html
            'upload_max_bytes' => 8 * 1024 * 1024,
            'upload_max_edge' => 4000,
            'session_name' => 'muenster_admin',
            'session_timeout' => 300,
            'login_max_attempts' => 5,
            'login_window' => 900,
            'suggest_max_per_hour' => 5,
            'allow_http_links' => true,
            'timezone' => 'Europe/Berlin',
            'debug' => false, // in config.local.php auf true setzen, um Fehlerdetails in API-Antworten zu sehen
        ];
        $local = __DIR__ . '/config.local.php';
        if (is_file($local)) {
            $config = array_merge($config, (array) require $local);
        }
        $testDb = getenv('MS_DB_PATH');
        if (is_string($testDb) && $testDb !== '') {
            $config['db_path'] = $testDb;
        }
        date_default_timezone_set($config['timezone']);
    }
    return $key === null ? $config : $config[$key];
}
