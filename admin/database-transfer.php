<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();
require_post();

$action = $_POST['action'] ?? '';
$databasePath = (string) config('db_path');

if ($action === 'export') {
    $temporaryPath = tempnam(sys_get_temp_dir(), 'ms-database-export-');
    if ($temporaryPath === false) {
        throw new RuntimeException('Temporäre Exportdatei konnte nicht erstellt werden.');
    }

    $exportFailed = false;
    $source = null;
    $destination = null;
    try {
        $source = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $destination = new SQLite3($temporaryPath);
        if (!$source->backup($destination)) {
            throw new RuntimeException('SQLite-Export fehlgeschlagen: ' . $source->lastErrorMsg());
        }
    } catch (Throwable $error) {
        error_log('[muenster-admin] Database export failed: ' . $error);
        $exportFailed = true;
    } finally {
        if ($destination instanceof SQLite3) {
            $destination->close();
        }
        if ($source instanceof SQLite3) {
            $source->close();
        }
    }

    if (!$exportFailed) {
        try {
            $filename = date('Ymd') . '-ms-unvermutet.sqlite';
            header('Content-Type: application/vnd.sqlite3');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . (string) filesize($temporaryPath));
            if (readfile($temporaryPath) === false) {
                throw new RuntimeException('SQLite-Exportdatei konnte nicht ausgeliefert werden.');
            }
        } catch (Throwable $error) {
            error_log('[muenster-admin] Database export response failed: ' . $error);
            $exportFailed = true;
        }
    }

    if (is_file($temporaryPath)) {
        unlink($temporaryPath);
    }
    if ($exportFailed) {
        flash('err', 'Die Datenbank konnte nicht exportiert werden.');
        redirect('settings.php');
    }
    exit;
}

if ($action !== 'import') {
    http_response_code(400);
    exit('Ungültige Aktion.');
}

$upload = $_FILES['database_file'] ?? null;
if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
    flash('err', 'Bitte wähle eine gültige SQLite-Sicherungsdatei aus.');
    redirect('settings.php');
}

$temporaryPath = tempnam(dirname($databasePath), '.database-import-');
if ($temporaryPath === false) {
    error_log('[muenster-admin] Database import failed: temporary file could not be created.');
    flash('err', 'Die Datenbank konnte nicht importiert werden.');
    redirect('settings.php');
}

$importFailed = false;
try {
    if (!copy((string) $upload['tmp_name'], $temporaryPath)) {
        throw new RuntimeException('Sicherungsdatei konnte nicht kopiert werden.');
    }

    $database = new SQLite3($temporaryPath, SQLITE3_OPEN_READONLY);
    try {
        $database->busyTimeout(5000);
        if ($database->querySingle('PRAGMA integrity_check') !== 'ok') {
            throw new RuntimeException('Die Sicherungsdatei hat die Integritätsprüfung nicht bestanden.');
        }

        $requiredColumns = [
            'stations' => ['id', 'number', 'title', 'hint', 'lat', 'lon', 'correct_option_index', 'solution_title', 'info', 'task_image', 'category', 'status', 'created_at', 'updated_at'],
            'options' => ['id', 'station_id', 'option_index', 'option_text'],
            'links' => ['id', 'station_id', 'title', 'url'],
            'admins' => ['id', 'username', 'password_hash', 'active', 'created_at'],
            'station_suggestions' => ['id', 'title', 'info', 'category', 'status', 'created_at'],
            'rate_limits' => ['id', 'bucket', 'key', 'created_at'],
            'settings' => ['key', 'value', 'updated_at'],
            'categories' => ['id', 'name', 'description', 'icon', 'color', 'created_at', 'updated_at'],
            'stat_events' => ['id', 'player_id', 'station_id', 'event', 'attempts', 'created_at'],
        ];
        foreach ($requiredColumns as $table => $columns) {
            $availableColumns = $database->query('PRAGMA table_info("' . $table . '")');
            $columnNames = [];
            while ($column = $availableColumns->fetchArray(SQLITE3_ASSOC)) {
                $columnNames[] = $column['name'];
            }
            if (array_diff($columns, $columnNames) !== []) {
                throw new RuntimeException('Die Sicherungsdatei enthält keine kompatible ' . $table . '-Tabelle.');
            }
        }
    } finally {
        $database->close();
    }

    if (is_file($databasePath)) {
        $currentDatabase = new SQLite3($databasePath);
        try {
            $currentDatabase->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        } finally {
            $currentDatabase->close();
        }
    }

    if (!chmod($temporaryPath, 0660) || !rename($temporaryPath, $databasePath)) {
        throw new RuntimeException('Die neue Datenbankdatei konnte nicht eingesetzt werden.');
    }
} catch (Throwable $error) {
    error_log('[muenster-admin] Database import failed: ' . $error);
    $importFailed = true;
} finally {
    if (is_file($temporaryPath)) {
        unlink($temporaryPath);
    }
}

if ($importFailed) {
    flash('err', 'Die Sicherungsdatei ist ungültig oder die Datenbank konnte nicht ersetzt werden.');
    redirect('settings.php');
}

flash('ok', 'Die Datenbank wurde erfolgreich importiert.');
redirect('settings.php');
