<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

const UI_REGISTRY = [
    'ui.suggest' => [
        'label' => 'Station vorschlagen',
        'group' => 'Karte',
        'description' => 'Button, Dialog und Standortauswahl für Vorschläge.',
        'default' => 'on',
        'requires' => [],
        'server_enforced' => true,
    ],
];

const UI_SETTING_STATES = ['off', 'on', 'test'];

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
