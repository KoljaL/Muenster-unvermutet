<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function clean_text($value, int $max, bool $multiline = false): string
{
    $value = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    if (!$multiline) {
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    }
    return mb_substr($value, 0, $max);
}

function valid_url(string $url): bool
{
    if (mb_strlen($url) > 500 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $scheme === 'https' || ($scheme === 'http' && config('allow_http_links'));
}

function valid_coordinates($lat, $lon): bool
{
    return is_numeric($lat) && is_numeric($lon)
        && (float) $lat >= -90 && (float) $lat <= 90
        && (float) $lon >= -180 && (float) $lon <= 180;
}

function parse_float($value): ?float
{
    $value = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
    return is_numeric($value) ? (float) $value : null;
}

/**
 * Normalisiert und validiert Optionen. $correct bezieht sich auf die Position im
 * Rohformular; leere Optionen werden entfernt und der Index entsprechend umgerechnet.
 * @return array{0: array, 1: ?int, 2: string[]}
 */
function clean_options($rawOptions, $correct): array
{
    $errors = [];
    $options = [];
    $newCorrect = null;
    foreach ((array) $rawOptions as $i => $text) {
        $text = clean_text($text, 500);
        if ($text === '') {
            continue;
        }
        if ((string) $i === (string) $correct) {
            $newCorrect = count($options);
        }
        $options[] = $text;
    }
    if (count($options) < 2 || count($options) > 4) {
        $errors[] = 'Es werden zwei bis vier Antwortoptionen benötigt.';
    }
    if ($newCorrect === null) {
        $errors[] = 'Die richtige Antwort muss auf eine ausgefüllte Option zeigen.';
    }
    return [$options, $newCorrect, $errors];
}

function clean_links($titles, $urls): array
{
    $links = [];
    $errors = [];
    foreach ((array) $urls as $i => $url) {
        $url = is_string($url) ? trim($url) : '';
        $title = clean_text($titles[$i] ?? '', 200);
        if ($url === '' && $title === '') {
            continue;
        }
        if (!valid_url($url)) {
            $errors[] = 'Ungültige Link-URL (nur http/https): ' . mb_substr($url, 0, 60);
            continue;
        }
        $links[] = ['title' => $title !== '' ? $title : $url, 'url' => $url];
    }
    return [$links, $errors];
}

/**
 * Validiert Stammdaten einer Station aus einem POST-Array.
 * @return array{0: array, 1: string[]}
 */
function validate_station_input(array $in): array
{
    $errors = [];
    $s = [
        'title' => clean_text($in['title'] ?? '', 200),
        'hint' => clean_text($in['hint'] ?? '', 1000, true),
        'solution_title' => clean_text($in['solution_title'] ?? '', 300),
        'solution_caption' => clean_text($in['solution_caption'] ?? '', 300),
        'info' => clean_text($in['info'] ?? '', 8000, true),
        'category' => (string) ($in['category'] ?? ''),
        'status' => ($in['status'] ?? 'published') === 'archived' ? 'archived' : 'published',
    ];
    foreach (['title' => 'Titel', 'hint' => 'Hinweis', 'solution_title' => 'Lösungstitel', 'info' => 'Info-Text'] as $key => $label) {
        if ($s[$key] === '') {
            $errors[] = $label . ' ist erforderlich.';
        }
    }
    if (!in_array($s['category'], config('categories'), true)) {
        $errors[] = 'Ungültige Kategorie.';
    }
    $lat = parse_float($in['lat'] ?? '');
    $lon = parse_float($in['lon'] ?? '');
    if ($lat === null || $lon === null || !valid_coordinates($lat, $lon)) {
        $errors[] = 'Ungültige Koordinaten.';
    }
    $s['lat'] = $lat;
    $s['lon'] = $lon;

    $number = $in['number'] ?? '';
    $s['number'] = ctype_digit((string) $number) && (int) $number > 0 ? (int) $number : null;

    [$s['options'], $s['correct'], $optErrors] = clean_options($in['options'] ?? [], $in['correct'] ?? null);
    [$s['links'], $linkErrors] = clean_links($in['link_title'] ?? [], $in['link_url'] ?? []);
    return [$s, array_merge($errors, $optErrors, $linkErrors)];
}

function slugify(string $text): string
{
    $text = strtr(mb_strtolower($text), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $text = trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');
    return substr($text !== '' ? $text : 'station', 0, 50);
}
