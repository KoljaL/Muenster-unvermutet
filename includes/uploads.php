<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const UPLOAD_ERRORS = [
    UPLOAD_ERR_INI_SIZE => 'Datei überschreitet das Server-Limit.',
    UPLOAD_ERR_FORM_SIZE => 'Datei ist zu groß.',
    UPLOAD_ERR_PARTIAL => 'Upload unvollständig.',
    UPLOAD_ERR_NO_TMP_DIR => 'Temporärer Ordner fehlt.',
    UPLOAD_ERR_CANT_WRITE => 'Datei konnte nicht geschrieben werden.',
    UPLOAD_ERR_EXTENSION => 'Upload durch Erweiterung gestoppt.',
];

function upload_provided(?array $file): bool
{
    return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * Validiert und speichert ein hochgeladenes Bild (nur JPEG/PNG).
 * @return string kontrollierter relativer Pfad, z. B. "uploads/ab12.jpg"
 * @throws RuntimeException mit nutzerfreundlicher Meldung
 */
function store_upload(array $file): string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(UPLOAD_ERRORS[$error] ?? 'Upload fehlgeschlagen.');
    }
    $tmp = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Ungültiger Upload.');
    }
    return store_image_file($tmp, true);
}

/** Gemeinsame Prüfung/Speicherung; $move=false kopiert (für den Import). */
function store_image_file(string $path, bool $move): string
{
    $size = filesize($path);
    if ($size === false || $size <= 0 || $size > config('upload_max_bytes')) {
        throw new RuntimeException('Bild ist leer oder größer als ' . (int) (config('upload_max_bytes') / 1048576) . ' MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Nur JPEG- und PNG-Bilder sind erlaubt.');
    }
    $info = @getimagesize($path);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        throw new RuntimeException('Datei ist kein gültiges Bild.');
    }
    $max = config('upload_max_edge');
    if ($info[0] > $max || $info[1] > $max || $info[0] < 1 || $info[1] < 1) {
        throw new RuntimeException('Bild darf höchstens ' . $max . ' Pixel Kantenlänge haben.');
    }

    $dir = config('upload_dir');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Upload-Ordner nicht verfügbar.');
    }
    do {
        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $target = $dir . '/' . $name;
    } while (file_exists($target));

    $ok = $move ? move_uploaded_file($path, $target) : copy($path, $target);
    if (!$ok) {
        throw new RuntimeException('Bild konnte nicht gespeichert werden.');
    }
    @chmod($target, 0644);
    return config('upload_url') . '/' . $name;
}

/** Löscht nur Dateien mit kontrolliertem Namensmuster im Upload-Ordner. */
function delete_upload(?string $relativePath): void
{
    if (!$relativePath || !preg_match('#^' . preg_quote(config('upload_url'), '#') . '/([a-f0-9]{32}\.(?:jpg|png))$#', $relativePath, $m)) {
        return;
    }
    $file = config('upload_dir') . '/' . $m[1];
    if (is_file($file)) {
        @unlink($file);
    }
}

/** Prüft, ob ein gespeicherter Pfad dem kontrollierten Muster entspricht. */
function is_managed_upload(?string $path): bool
{
    return (bool) $path && (bool) preg_match('#^' . preg_quote(config('upload_url'), '#') . '/[a-f0-9]{32}\.(?:jpg|png)$#', $path);
}
