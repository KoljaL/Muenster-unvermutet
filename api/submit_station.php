<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_method('POST');
require_once __DIR__ . '/../includes/settings.php';

$preview = is_string($_GET['preview'] ?? null) ? $_GET['preview'] : null;
if (!ui_option_enabled('ui.suggest', $preview)) {
    json_error(403, 'suggestions_disabled', 'Vorschläge sind derzeit nicht verfügbar.');
}

// Honeypot: Bots füllen das versteckte Feld aus. Stillschweigend "erfolgreich" antworten.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    json_ok(['received' => true], 201);
}

$ip = client_ip();
if (rate_limit_exceeded('suggest', $ip, (int) config('suggest_max_per_hour'), 3600)) {
    json_error(429, 'rate_limited', 'Zu viele Vorschläge. Bitte später erneut versuchen.');
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$isJsonRequest = str_contains($contentType, 'application/json');
$input = $_POST;
if ($isJsonRequest) {
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        json_error(400, 'invalid_json', 'Ungültiges JSON.');
    }
    $input = $decoded;
}

if ($isJsonRequest && array_key_exists('name', $input)) {
    [$suggestion, $errors] = validate_basic_suggestion($input);
    if ($errors) {
        json_error(422, 'validation_failed', implode(' ', $errors));
    }
    store_suggestion($suggestion, $ip);
    json_ok(['received' => true, 'status' => 'pending'], 201);
}

// Bestehendes vollständiges Formulardatenformat bleibt rückwärtskompatibel.
[$suggestion, $errors] = validate_full_suggestion($input);
if ($errors) {
    json_error(422, 'validation_failed', implode(' ', $errors));
}
store_suggestion($suggestion, $ip);
json_ok(['received' => true, 'status' => 'pending'], 201);

/**
 * @return array{0: array, 1: string[]}
 */
function validate_basic_suggestion(array $input): array
{
    $errors = [];
    $version = $input['version'] ?? null;
    if ($version !== 1 && $version !== '1') {
        $errors[] = 'Nicht unterstützte Vorschlagsversion.';
    }

    $titleInput = clean_text($input['name'] ?? '', 8000);
    $infoInput = clean_text($input['description'] ?? '', 8000, true);
    $title = mb_substr($titleInput, 0, 100);
    $info = mb_substr($infoInput, 0, 1000);
    $category = !array_key_exists('categoryId', $input)
        ? 'detail'
        : (is_string($input['categoryId']) ? trim($input['categoryId']) : '');
    if ($title === '') {
        $errors[] = 'Ort ist erforderlich.';
    } elseif (mb_strlen($titleInput) > 100) {
        $errors[] = 'Der Ort darf höchstens 100 Zeichen lang sein.';
    }
    if ($info === '') {
        $errors[] = 'Kurze Beschreibung ist erforderlich.';
    } elseif (mb_strlen($infoInput) > 1000) {
        $errors[] = 'Die kurze Beschreibung darf höchstens 1000 Zeichen lang sein.';
    }
    if (!category_exists($category)) {
        $errors[] = 'Ungültige Kategorie.';
    }

    $location = $input['location'] ?? null;
    $lat = null;
    $lon = null;
    if ($location !== null) {
        if (!is_array($location)) {
            $errors[] = 'Der Standort ist ungültig.';
        } else {
            $lat = parse_float($location['latitude'] ?? '');
            $lon = parse_float($location['longitude'] ?? '');
            if ($lat === null || $lon === null || !valid_coordinates($lat, $lon)) {
                $errors[] = 'Ungültige Koordinaten.';
            }
        }
    }

    $editorial = $input['editorial'] ?? [];
    if (!is_array($editorial)) {
        $errors[] = 'Die Zusatzinformationen sind ungültig.';
        $editorial = [];
    }
    [$hint, $question, $options, $correct, $editorialErrors] = clean_editorial_suggestion($editorial);
    $errors = array_merge($errors, $editorialErrors);
    $editorialInfo = clean_text($editorial['info'] ?? '', 8000, true);

    return [[
        'title' => $title,
        'hint' => $hint,
        'lat' => $lat,
        'lon' => $lon,
        'question' => $question,
        'options_json' => $options === null ? null : json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'correct' => $correct,
        'info' => clean_text($info . ($editorialInfo !== '' ? "\n\n" . $editorialInfo : ''), 8000, true),
        'category' => $category,
        'name' => null,
        'email' => null,
        'task_image' => null,
        'solution_image' => null,
        'upload_files' => false,
    ], $errors];
}

/**
 * @return array{0: array, 1: string[]}
 */
function validate_full_suggestion(array $input): array
{
    $errors = [];
    $title = clean_text($input['title'] ?? '', 200);
    $hint = clean_text($input['hint'] ?? '', 1000, true);
    $question = clean_text($input['question'] ?? '', 500);
    $info = clean_text($input['info'] ?? '', 8000, true);
    $category = clean_text($input['category'] ?? 'detail', 100);
    $name = clean_text($input['name'] ?? '', 100);
    $email = clean_text($input['email'] ?? '', 200);
    $lat = parse_float($input['lat'] ?? '');
    $lon = parse_float($input['lon'] ?? '');

    foreach (['Titel' => $title, 'Hinweis' => $hint, 'Frage' => $question, 'Info' => $info] as $label => $value) {
        if ($value === '') {
            $errors[] = $label . ' ist erforderlich.';
        }
    }
    if (!category_exists($category)) {
        $errors[] = 'Ungültige Kategorie.';
    }
    if ($lat === null || $lon === null || !valid_coordinates($lat, $lon)) {
        $errors[] = 'Ungültige Koordinaten.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ungültige E-Mail-Adresse.';
    }
    [$options, $correct, $optionErrors] = clean_options($input['options'] ?? [], $input['correct'] ?? null);
    $errors = array_merge($errors, $optionErrors);

    return [[
        'title' => $title,
        'hint' => $hint,
        'lat' => $lat,
        'lon' => $lon,
        'question' => $question,
        'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'correct' => $correct,
        'info' => $info,
        'category' => $category,
        'name' => $name !== '' ? $name : null,
        'email' => $email !== '' ? $email : null,
        'task_image' => null,
        'solution_image' => null,
        'upload_files' => true,
    ], $errors];
}

/**
 * @return array{0: ?string, 1: ?string, 2: ?array, 3: ?int, 4: string[]}
 */
function clean_editorial_suggestion(array $editorial): array
{
    $errors = [];
    $hint = clean_text($editorial['hint'] ?? '', 1000, true);
    $question = clean_text($editorial['question'] ?? '', 500);
    $info = clean_text($editorial['info'] ?? '', 8000, true);
    $rawOptions = $editorial['options'] ?? [];
    $hasQuestionBlock = $question !== '' || $rawOptions !== [] || array_key_exists('correctOptionIndex', $editorial);
    $options = null;
    $correct = null;

    if ($hasQuestionBlock) {
        if ($question === '') {
            $errors[] = 'Eine Frage ist erforderlich, wenn Antwortoptionen ergänzt werden.';
        }
        if (!is_array($rawOptions)) {
            $errors[] = 'Die Antwortoptionen sind ungültig.';
        } else {
            [$options, $correct, $optionErrors] = clean_options($rawOptions, $editorial['correctOptionIndex'] ?? null);
            $errors = array_merge($errors, $optionErrors);
        }
    }

    return [$hint !== '' ? $hint : null, $question !== '' ? $question : null, $options, $correct, $errors];
}

function store_suggestion(array $suggestion, string $ip): void
{
    $stored = [];
    try {
        if ($suggestion['upload_files'] ?? false) {
            foreach (['task_image', 'solution_image'] as $field) {
                if (upload_provided($_FILES[$field] ?? null)) {
                    $stored[$field] = store_upload($_FILES[$field]);
                }
            }
        }
        db()->prepare(
            'INSERT INTO station_suggestions
             (title, hint, lat, lon, question, options_json, correct_option_index, info, category,
              suggested_by_name, suggested_by_email, task_image, solution_image, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\', ?)'
        )->execute([
            $suggestion['title'], $suggestion['hint'], $suggestion['lat'], $suggestion['lon'],
            $suggestion['question'], $suggestion['options_json'], $suggestion['correct'],
            $suggestion['info'], $suggestion['category'], $suggestion['name'], $suggestion['email'],
            $stored['task_image'] ?? null, $stored['solution_image'] ?? null, now(),
        ]);
    } catch (Throwable $error) {
        foreach ($stored as $path) {
            delete_upload($path);
        }
        log_server_error('muenster-api suggestion insert', $error);
        json_error(422, 'suggestion_not_saved', 'Der Vorschlag konnte nicht gespeichert werden.');
    }
    rate_limit_hit('suggest', $ip);
}
