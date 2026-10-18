<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if (current_admin() === null) {
    http_response_code(403);
    exit("Admin-Anmeldung erforderlich. Bitte zuerst im Admin-Bereich anmelden.\n");
}

$databasePath = (string) config('db_path');
$databaseDirectory = dirname($databasePath);
$uploadDirectory = (string) config('upload_dir');
$configuredUploadTempDirectory = (string) ini_get('upload_tmp_dir');
$uploadTempDirectory = $configuredUploadTempDirectory !== ''
    ? $configuredUploadTempDirectory
    : sys_get_temp_dir();
$uploadMaxFilesize = (string) ini_get('upload_max_filesize');
$postMaxSize = (string) ini_get('post_max_size');

echo "Serverdiagnose Münster unvermutet\n";
echo "Nur für eingeloggte Admins; keine Zugangsdaten oder absoluten Pfade werden ausgegeben.\n\n";
echo 'PHP-Version: ', PHP_VERSION, "\n";
echo 'file_uploads: ', ini_get('file_uploads') === '1' ? 'aktiviert' : 'deaktiviert', "\n";
echo 'upload_max_filesize: ', $uploadMaxFilesize, "\n";
echo 'post_max_size: ', $postMaxSize, "\n";
echo 'upload_tmp_dir: ', $configuredUploadTempDirectory !== '' ? 'konfiguriert' : 'PHP-Standardverzeichnis', "\n";
echo 'Temporäres Upload-Verzeichnis beschreibbar: ', is_dir($uploadTempDirectory) && is_writable($uploadTempDirectory) ? 'ja' : 'nein', "\n";
echo 'Anwendungs-Limit pro Bild: ', (int) (config('upload_max_bytes') / 1048576), " MB\n";
echo 'Datenbankverzeichnis beschreibbar: ', is_dir($databaseDirectory) && is_writable($databaseDirectory) ? 'ja' : 'nein', "\n";
echo 'Datenbankdatei vorhanden: ', is_file($databasePath) ? 'ja' : 'nein', "\n";
echo 'Datenbankdatei beschreibbar: ', is_file($databasePath) && is_writable($databasePath) ? 'ja' : 'nein', "\n";
echo 'Upload-Verzeichnis vorhanden: ', is_dir($uploadDirectory) ? 'ja' : 'nein', "\n";
echo 'Upload-Verzeichnis beschreibbar: ', is_dir($uploadDirectory) && is_writable($uploadDirectory) ? 'ja' : 'nein', "\n";
