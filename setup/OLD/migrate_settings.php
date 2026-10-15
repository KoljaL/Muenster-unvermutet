<?php
declare(strict_types=1);

// Einmalige CLI-Migration für bestehende Datenbanken:
// php setup/migrate_settings.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/settings.php';

$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL, updated_at TEXT NOT NULL)');
$statement = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value, updated_at) VALUES (?, ?, ?)');
$timestamp = now();
foreach (UI_REGISTRY as $key => $definition) {
    $statement->execute([$key, $definition['default'], $timestamp]);
}
foreach (LOCATION_SETTING_DEFAULTS as $key => $value) {
    $statement->execute([$key, (string) $value, $timestamp]);
}

echo "UI- und Standorteinstellungen erfolgreich angelegt und fehlende Defaults ergänzt.\n";
