<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/uploads.php';

ini_set('display_errors', '0');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Methode nicht erlaubt.');
    }
    if (post_body_exceeds_limit()) {
        http_response_code(413);
        exit('Die Anfrage ist größer als das Serverlimit. Bitte kleinere Bilder verwenden oder den Serveradministrator um Erhöhung von post_max_size bitten.');
    }
    csrf_verify();
}

function post_body_exceeds_limit(): bool
{
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? null;
    $postMaxSize = trim((string) ini_get('post_max_size'));
    if (!is_string($contentLength) || !ctype_digit($contentLength) || $postMaxSize === '' || $postMaxSize === '0') {
        return false;
    }
    if (!preg_match('/^(\d+)\s*([KMG])?$/i', $postMaxSize, $matches)) {
        return false;
    }

    $limit = (float) $matches[1];
    $unit = strtoupper($matches[2] ?? '');
    $limit *= match ($unit) {
        'K' => 1024,
        'M' => 1024 ** 2,
        'G' => 1024 ** 3,
        default => 1,
    };
    return (float) $contentLength > $limit;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function admin_header(string $title, ?array $admin = null, string $extraHead = ''): void
{
    $current = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $nav = [
        'index.php' => ['Stationen', ['index.php', 'station-edit.php']],
        'categories.php' => ['Kategorien', ['categories.php', 'category-edit.php']],
        'suggestions.php' => ['Vorschläge', ['suggestions.php']],
        'settings.php' => ['Einstellungen', ['settings.php']],
        'help.php' => ['Hilfe', ['help.php']],
    ];
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8">',
        '<meta name="viewport" content="width=device-width, initial-scale=1">',
        '<meta name="color-scheme" content="light dark">',
        '<meta name="robots" content="noindex,nofollow">',
        '<title>', h($title), ' – Admin Münster unvermutet</title>',
        '<link rel="stylesheet" href="admin.css">', $extraHead, '</head><body><div class="page">';
    echo '<header class="hero"><div class="admin-bar"><strong class="admin-brand">Münster unvermutet</strong>';
    if ($admin) {
        echo '<nav class="admin-nav" aria-label="Hauptnavigation">';
        foreach ($nav as $href => [$label, $pages]) {
            echo '<a href="', $href, '"', in_array($current, $pages, true) ? ' aria-current="page"' : '', '>', $label, '</a>';
        }
        echo '</nav><div class="admin-user">',
            '<form method="post" action="logout.php">', csrf_field(), '<button class="secondary small">Abmelden</button></form></div>';
    }
    echo '</div></header><main>';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        foreach ((array) $f['message'] as $m) {
            echo '<div class="msg ', $f['type'] === 'ok' ? 'ok' : 'err', '" role="', $f['type'] === 'ok' ? 'status' : 'alert', '">', h($m), '</div>';
        }
    }
    unset($_SESSION['flash']);
}

function admin_footer(): void
{
    echo '</main></div></body></html>';
}

/** Kategorie-Symbol: hochgeladenes Bild oder Initiale als Fallback. */
function category_ico(?array $category): string
{
    if ($category && is_managed_upload($category['icon'] ?? null)) {
        return '<img src="../' . h($category['icon']) . '" alt="" loading="lazy">';
    }
    return h(mb_strtoupper(mb_substr((string) ($category['name'] ?? '?'), 0, 1)));
}
