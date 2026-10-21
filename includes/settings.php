<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

const UI_REGISTRY = [
    'ui.suggest' => [
        'label' => 'Station vorschlagen',
        'group' => 'Karte',
        'description' => 'Besucher können über „+ Station vorschlagen“ unter der Karte selbst neue Orte einsenden.',
        'default' => 'on',
        'requires' => [],
        'server_enforced' => true,
    ],
    'ui.legend' => [
        'label' => 'Kategorien-Legende',
        'group' => 'Karte',
        'description' => 'Farbige Kategorien-Liste unter der Karte; ein Klick darauf blendet Stationen aus oder ein.',
        'default' => 'on',
        'requires' => [],
    ],
    'ui.station_list' => [
        'label' => 'Stationsliste',
        'group' => 'Karte',
        'description' => 'Liste aller Stationen unter der Karte, z. B. für Besucher, die die Karte schlecht bedienen können.',
        'default' => 'on',
        'requires' => [],
    ],
    'ui.hint_button' => [
        'label' => 'Hinweis-Schaltfläche',
        'group' => 'Aufgaben',
        'description' => 'Zeigt in der Aufgabe den Knopf „Hinweis anzeigen“.',
        'default' => 'on',
        'requires' => [],
    ],
    'ui.reveal_button' => [
        'label' => 'Lösungs-Schaltfläche',
        'group' => 'Aufgaben',
        'description' => 'Zeigt in der Aufgabe den Knopf „Lösung anzeigen“, mit dem Besucher aufgeben können.',
        'default' => 'on',
        'requires' => [],
    ],
    'ui.location_gate' => [
        'label' => 'Standortabhängige Antwortfreigabe',
        'group' => 'Aufgaben',
        'description' => 'Besucher sehen Antworten und Lösung erst, wenn ihr Handy bestätigt, dass sie vor Ort sind. Siehe auch „Standortprüfung“ unten.',
        'default' => 'on',
        'requires' => [],
        'server_enforced' => true,
    ],
    'ui.station_map' => [
        'label' => 'OSM-Orientierungskarte',
        'group' => 'Karte',
        'description' => 'Kleine Karte mit Ziel und eigener Position innerhalb der Aufgabe („OpenStreetMap-Orientierungskarte“).',
        'default' => 'on',
        'requires' => [],
    ],
    'ui.completion' => [
        'label' => 'Abschluss-Screen',
        'group' => 'Aufgaben',
        'description' => 'Glückwunsch-Seite mit Pokal und Statistik, sobald ein Besucher alle Stationen gelöst hat.',
        'default' => 'on',
        'requires' => [],
    ],
];

const UI_SETTING_STATES = ['off', 'on', 'test'];
const LOCATION_SETTING_DEFAULTS = ['location_radius_m' => 50, 'location_max_accuracy_m' => 50];
const LOCATION_SETTING_LIMITS = ['location_radius_m' => [5, 5000], 'location_max_accuracy_m' => [1, 500]];
const PUBLIC_TEXT_LIMIT = 10000;

const PUBLIC_TEXT_KEYS = ['credits' => 'public_credits', 'imprint' => 'public_imprint', 'routeHint' => 'public_route_hint'];
const PUBLIC_TEXT_DEFAULTS = [
    'credits' => '',
    'imprint' => '',
    'routeHint' => 'Du findest den Ort selbst? Dann nutze „Standort aktualisieren“. Lieber mit Navigation? „Route starten“ öffnet deine Karten-App mit dem Ziel.',
];

/** @return array{credits: string, imprint: string, routeHint: string} */
function public_text_settings(): array
{
    $settings = PUBLIC_TEXT_DEFAULTS;
    $rows = db()->query("SELECT key, value FROM settings WHERE key IN ('public_credits', 'public_imprint', 'public_route_hint')")->fetchAll();
    $byDbKey = array_flip(PUBLIC_TEXT_KEYS);
    foreach ($rows as $row) {
        if (isset($byDbKey[$row['key']])) {
            $settings[$byDbKey[$row['key']]] = (string) $row['value'];
        }
    }
    return $settings;
}

/** @param array<string, mixed> $values
 *  @return array{credits: string, imprint: string, routeHint: string}
 */
function validate_public_text_settings(array $values): array
{
    if (array_diff(array_keys($values), array_keys(PUBLIC_TEXT_KEYS)) !== []) {
        throw new InvalidArgumentException('Ungültiger öffentlicher Text.');
    }

    $validated = [];
    foreach (array_keys(PUBLIC_TEXT_KEYS) as $key) {
        $value = $values[$key] ?? null;
        if (!is_string($value) || mb_strlen($value) > PUBLIC_TEXT_LIMIT) {
            throw new InvalidArgumentException('Öffentlicher Text ist ungültig oder zu lang.');
        }
        $validated[$key] = str_replace(["\r\n", "\r"], "\n", $value);
    }
    return $validated;
}

/** @param array{credits: string, imprint: string, routeHint: string} $values */
function save_public_text_settings(array $values): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'INSERT INTO settings (key, value, updated_at) VALUES (?, ?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at'
        );
        $timestamp = now();
        foreach (PUBLIC_TEXT_KEYS as $key => $dbKey) {
            $statement->execute([$dbKey, $values[$key], $timestamp]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

/** @return array{location_radius_m: int, location_max_accuracy_m: int} */
function location_settings(): array
{
    $settings = LOCATION_SETTING_DEFAULTS;
    $rows = db()->query("SELECT key, value FROM settings WHERE key IN ('location_radius_m', 'location_max_accuracy_m')")->fetchAll();
    foreach ($rows as $row) {
        $key = $row['key'];
        if (!isset(LOCATION_SETTING_LIMITS[$key]) || !is_numeric($row['value'])) {
            continue;
        }
        $value = filter_var($row['value'], FILTER_VALIDATE_INT);
        [$minimum, $maximum] = LOCATION_SETTING_LIMITS[$key];
        if ($value !== false && $value >= $minimum && $value <= $maximum) {
            $settings[$key] = $value;
        }
    }
    return $settings;
}

/** @param array<string, mixed> $values
 *  @return array{location_radius_m: int, location_max_accuracy_m: int}
 */
function validate_location_settings(array $values): array
{
    $validated = [];
    foreach (array_keys($values) as $key) {
        if (!isset(LOCATION_SETTING_LIMITS[$key])) {
            throw new InvalidArgumentException('Ungültige Standortgrenze.');
        }
    }
    foreach (LOCATION_SETTING_LIMITS as $key => [$minimum, $maximum]) {
        $value = $values[$key] ?? null;
        if (!is_string($value) || !preg_match('/^[0-9]+$/', $value)) {
            throw new InvalidArgumentException('Ungültige Standortgrenze.');
        }
        $number = (int) $value;
        if ($number < $minimum || $number > $maximum) {
            throw new InvalidArgumentException('Ungültige Standortgrenze.');
        }
        $validated[$key] = $number;
    }
    return $validated;
}

/** @param array{location_radius_m: int, location_max_accuracy_m: int} $values */
function save_location_settings(array $values): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'INSERT INTO settings (key, value, updated_at) VALUES (?, ?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at'
        );
        foreach ($values as $key => $value) {
            $statement->execute([$key, (string) $value, now()]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function location_gate_enabled(string|array|null $preview = null): bool
{
    return ui_option_enabled('ui.location_gate', $preview);
}

/** @return array<string, string> */
function ui_setting_states(): array
{
    $states = [];
    foreach (UI_REGISTRY as $key => $definition) {
        $states[$key] = $definition['default'];
    }

    $rows = db()->query('SELECT key, value FROM settings')->fetchAll();
    foreach ($rows as $row) {
        $key = $row['key'];
        $value = $row['value'];
        if (isset(UI_REGISTRY[$key]) && in_array($value, UI_SETTING_STATES, true)) {
            $states[$key] = $value;
        }
    }
    return $states;
}

/**
 * Resolves persisted settings to visitor-facing booleans and applies dependencies.
 *
 * @param string|array<int, string>|null $preview
 * @return array<string, bool>
 */
function resolve_ui_settings(string|array|null $preview = null): array
{
    $previewKeys = normalize_ui_preview($preview);
    $states = ui_setting_states();
    $resolved = [];
    $resolve = static function (string $key) use (&$resolve, &$resolved, $states, $previewKeys): bool {
        if (array_key_exists($key, $resolved)) {
            return $resolved[$key];
        }
        $state = $states[$key];
        $enabled = $state === 'on' || ($state === 'test' && (isset($previewKeys['all']) || isset($previewKeys[$key])));
        foreach (UI_REGISTRY[$key]['requires'] as $required) {
            $enabled = $enabled && $resolve($required);
        }
        $resolved[$key] = $enabled;
        return $enabled;
    };

    foreach (UI_REGISTRY as $key => $_definition) {
        $resolve($key);
    }
    return $resolved;
}

/** @return array<string, true> */
function normalize_ui_preview(string|array|null $preview): array
{
    if (is_string($preview)) {
        $preview = explode(',', $preview);
    }
    $normalized = [];
    foreach ($preview ?? [] as $item) {
        if (!is_string($item)) {
            continue;
        }
        $item = trim($item);
        if ($item === 'all') {
            $normalized['all'] = true;
        } elseif (isset(UI_REGISTRY[$item])) {
            $normalized[$item] = true;
        } elseif (isset(UI_REGISTRY['ui.' . $item])) {
            $normalized['ui.' . $item] = true;
        }
    }
    return $normalized;
}

function ui_option_enabled(string $key, string|array|null $preview = null): bool
{
    $resolved = resolve_ui_settings($preview);
    return $resolved[$key] ?? false;
}

/** @param array<string, mixed> $values */
function save_ui_settings(array $values): void
{
    foreach ($values as $key => $value) {
        if (!isset(UI_REGISTRY[$key]) || !is_string($value) || !in_array($value, UI_SETTING_STATES, true)) {
            throw new InvalidArgumentException('Ungültige Einstellung.');
        }
    }
    foreach (UI_REGISTRY as $key => $_definition) {
        if (!array_key_exists($key, $values)) {
            throw new InvalidArgumentException('Unvollständige Einstellungen.');
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'INSERT INTO settings (key, value, updated_at) VALUES (?, ?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at'
        );
        $timestamp = now();
        foreach (UI_REGISTRY as $key => $_definition) {
            $statement->execute([$key, $values[$key], $timestamp]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
