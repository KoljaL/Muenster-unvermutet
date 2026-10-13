<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_method('POST');

// Honeypot: Bots füllen das versteckte Feld aus. Stillschweigend "erfolgreich" antworten.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    json_ok(['received' => true], 201);
}

$ip = client_ip();
if (rate_limit_exceeded('suggest', $ip, (int) config('suggest_max_per_hour'), 3600)) {
    json_error(429, 'rate_limited', 'Zu viele Vorschläge. Bitte später erneut versuchen.');
}

$errors = [];
$title = clean_text($_POST['title'] ?? '', 200);
$hint = clean_text($_POST['hint'] ?? '', 1000, true);
$question = clean_text($_POST['question'] ?? '', 500);
$info = clean_text($_POST['info'] ?? '', 8000, true);
$name = clean_text($_POST['name'] ?? '', 100);
$email = clean_text($_POST['email'] ?? '', 200);
$lat = parse_float($_POST['lat'] ?? '');
$lon = parse_float($_POST['lon'] ?? '');

foreach (['Titel' => $title, 'Hinweis' => $hint, 'Frage' => $question, 'Info' => $info] as $label => $v) {
    if ($v === '') {
        $errors[] = $label . ' ist erforderlich.';
    }
}
if ($lat === null || $lon === null || !valid_coordinates($lat, $lon)) {
    $errors[] = 'Ungültige Koordinaten.';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ungültige E-Mail-Adresse.';
}
[$options, $correct, $optErrors] = clean_options($_POST['options'] ?? [], $_POST['correct'] ?? null);
$errors = array_merge($errors, $optErrors);

if ($errors) {
    json_error(422, 'validation_failed', implode(' ', $errors));
}

$stored = [];
try {
    foreach (['task_image', 'solution_image'] as $field) {
        $stored[$field] = upload_provided($_FILES[$field] ?? null) ? store_upload($_FILES[$field]) : null;
    }
    db()->prepare(
        'INSERT INTO station_suggestions
         (title, hint, lat, lon, question, options_json, correct_option_index, info,
          suggested_by_name, suggested_by_email, task_image, solution_image, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\', ?)'
    )->execute([
        $title, $hint, $lat, $lon, $question,
        json_encode($options, JSON_UNESCAPED_UNICODE), $correct, $info,
        $name ?: null, $email ?: null, $stored['task_image'], $stored['solution_image'], now(),
    ]);
} catch (RuntimeException $e) {
    foreach ($stored as $path) {
        delete_upload($path);
    }
    json_error(422, 'upload_failed', $e->getMessage());
}

rate_limit_hit('suggest', $ip);
json_ok(['received' => true], 201);
