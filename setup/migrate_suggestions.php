<?php
declare(strict_types=1);

// Einmalige CLI-Migration für bestehende Datenbanken:
// php setup/migrate_suggestions.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$pdo = db();
$columns = $pdo->query('PRAGMA table_info(station_suggestions)')->fetchAll(PDO::FETCH_ASSOC);
if (!$columns) {
    echo "Keine station_suggestions-Tabelle vorhanden; schema.sql legt sie beim nächsten Zugriff korrekt an.\n";
    exit(0);
}

$hasCategory = false;
foreach ($columns as $column) {
    if ($column['name'] === 'category') {
        $hasCategory = true;
        break;
    }
}

// Die Migration ist idempotent. Eine bereits aktuelle Tabelle bleibt unverändert.
$needsMigration = !$hasCategory;
foreach (['hint', 'lat', 'lon', 'question', 'options_json', 'correct_option_index'] as $name) {
    foreach ($columns as $column) {
        if ($column['name'] === $name && (int) $column['notnull'] === 1) {
            $needsMigration = true;
        }
    }
}
if (!$needsMigration) {
    echo "station_suggestions ist bereits aktuell.\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    $categoryExpression = $hasCategory ? 'category' : "'detail'";
    $pdo->exec('DROP INDEX IF EXISTS suggestions_status_index');
    $pdo->exec('ALTER TABLE station_suggestions RENAME TO station_suggestions_legacy');
    $pdo->exec(<<<'SQL'
        CREATE TABLE station_suggestions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            hint TEXT,
            lat NUMERIC,
            lon NUMERIC,
            question TEXT,
            options_json TEXT,
            correct_option_index INTEGER,
            info TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'detail',
            suggested_by_name TEXT,
            suggested_by_email TEXT,
            task_image TEXT,
            solution_image TEXT,
            status TEXT NOT NULL DEFAULT 'pending'
                CHECK (status IN ('pending', 'approved', 'rejected')),
            created_at TEXT NOT NULL,
            reviewed_at TEXT
        )
    SQL);
    $pdo->exec(<<<SQL
        INSERT INTO station_suggestions
            (id, title, hint, lat, lon, question, options_json, correct_option_index,
             info, category, suggested_by_name, suggested_by_email, task_image,
             solution_image, status, created_at, reviewed_at)
        SELECT id, title, hint, lat, lon, question, options_json, correct_option_index,
               info, {$categoryExpression}, suggested_by_name, suggested_by_email, task_image,
               solution_image, status, created_at, reviewed_at
        FROM station_suggestions_legacy
    SQL);
    $pdo->exec('DROP TABLE station_suggestions_legacy');
    $pdo->exec('CREATE INDEX suggestions_status_index ON station_suggestions(status)');
    $pdo->commit();
    echo "station_suggestions erfolgreich migriert. Bestehende Vorschläge wurden erhalten; alte Kategorien erhalten 'detail'.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Migration fehlgeschlagen: " . $error->getMessage() . "\n");
    exit(1);
}
