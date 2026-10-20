<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/settings.php';
require_admin();
require_post();

$values = $_POST['settings'] ?? null;
$locationValues = $_POST['location'] ?? null;
if (!is_array($values) || !is_array($locationValues) || !is_array($_POST['public_text'] ?? null)) {
    flash('err', 'Bitte wähle für alle Optionen einen gültigen Wert.');
    redirect('settings.php');
}

try {
    $validatedLocationSettings = validate_location_settings($locationValues);
    $validatedPublicTexts = validate_public_text_settings($_POST['public_text']);
    save_ui_settings($values);
    save_location_settings($validatedLocationSettings);
    save_public_text_settings($validatedPublicTexts);
} catch (InvalidArgumentException $error) {
    flash('err', 'Ungültige oder unvollständige Einstellungen. Bitte prüfe das Formular.');
    redirect('settings.php');
} catch (Throwable $error) {
    error_log('[muenster-admin] UI settings save failed: ' . $error);
    flash('err', 'Die Einstellungen konnten nicht gespeichert werden.');
    redirect('settings.php');
}

flash('ok', 'Einstellungen gespeichert.');
redirect('settings.php');
