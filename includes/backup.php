<?php
declare(strict_types=1);

function create_database_backup(string $databasePath, string $backupDirectory): string
{
    if (!is_file($databasePath) || !is_readable($databasePath)) {
        throw new RuntimeException('Die Datenbankdatei ist nicht lesbar.');
    }
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
        throw new RuntimeException('Das Backup-Verzeichnis konnte nicht erstellt werden.');
    }
    if (!chmod($backupDirectory, 0700) || !is_writable($backupDirectory)) {
        throw new RuntimeException('Das Backup-Verzeichnis ist nicht sicher beschreibbar.');
    }

    $temporaryPath = tempnam($backupDirectory, '.game-backup-');
    if ($temporaryPath === false) {
        throw new RuntimeException('Temporäre Backup-Datei konnte nicht erstellt werden.');
    }

    $source = null;
    $destination = null;
    $publishedPath = null;
    try {
        if (!chmod($temporaryPath, 0600)) {
            throw new RuntimeException('Die Zugriffsrechte der temporären Backup-Datei konnten nicht gesetzt werden.');
        }

        $source = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $source->enableExceptions(true);
        $source->busyTimeout(5000);
        $destination = new SQLite3($temporaryPath);
        $destination->enableExceptions(true);
        $destination->busyTimeout(5000);
        if (!$source->backup($destination)) {
            throw new RuntimeException('SQLite konnte die Datenbank nicht sichern: ' . $source->lastErrorMsg());
        }
        $destination->close();
        $destination = null;
        $source->close();
        $source = null;

        $verification = new SQLite3($temporaryPath, SQLITE3_OPEN_READONLY);
        $verification->enableExceptions(true);
        try {
            $verification->busyTimeout(5000);
            if ($verification->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new RuntimeException('Die Integritätsprüfung der Sicherung ist fehlgeschlagen.');
            }
        } finally {
            $verification->close();
        }

        $timestamp = date('Ymd-His');
        for ($suffix = 0; $suffix < 10000; $suffix++) {
            $suffixText = $suffix === 0 ? '' : '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
            $filename = 'game-' . $timestamp . $suffixText . '.sqlite';
            $candidatePath = $backupDirectory . DIRECTORY_SEPARATOR . $filename;
            if (@link($temporaryPath, $candidatePath)) {
                $publishedPath = $candidatePath;
                break;
            }
            if (!file_exists($candidatePath)) {
                throw new RuntimeException('Die geprüfte Sicherung konnte nicht veröffentlicht werden.');
            }
        }
        if ($publishedPath === null) {
            throw new RuntimeException('Es konnte kein eindeutiger Sicherungsname erstellt werden.');
        }
        if (!unlink($temporaryPath)) {
            @unlink($publishedPath);
            throw new RuntimeException('Die temporäre Sicherungsdatei konnte nicht entfernt werden.');
        }
        $temporaryPath = '';

        return basename($publishedPath);
    } finally {
        if ($destination instanceof SQLite3) {
            $destination->close();
        }
        if ($source instanceof SQLite3) {
            $source->close();
        }
        if ($temporaryPath !== '' && is_file($temporaryPath)) {
            unlink($temporaryPath);
        }
    }
}
