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
    csrf_verify();
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
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8">',
        '<meta name="viewport" content="width=device-width, initial-scale=1">',
        '<meta name="robots" content="noindex,nofollow">',
        '<title>', h($title), ' – Admin Münster unvermutet</title>',
        '<style>
        body{font:16px/1.5 system-ui,sans-serif;margin:0;background:#f4f1ea;color:#222}
        header.top{background:#1f3a5f;color:#fff;padding:.6rem 1rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
        header.top a{color:#fff} header.top form{margin-left:auto;display:inline}
        main{max-width:960px;margin:1rem auto;padding:0 1rem}
        table{border-collapse:collapse;width:100%;background:#fff} th,td{border:1px solid #ddd;padding:.4rem .6rem;text-align:left;vertical-align:top}
        label{display:block;margin:.8rem 0 .2rem;font-weight:600}
        input[type=text],input[type=password],input[type=url],input[type=email],input[type=number],textarea,select{width:100%;box-sizing:border-box;padding:.4rem;font:inherit}
        textarea{min-height:6rem}
        button,.btn{background:#1f3a5f;color:#fff;border:0;padding:.45rem .9rem;border-radius:4px;font:inherit;cursor:pointer;text-decoration:none;display:inline-block}
        button.secondary,.btn.secondary{background:#6b7280} button.danger{background:#b91c1c}
        .msg{padding:.6rem 1rem;border-radius:4px;margin:.6rem 0}.msg.ok{background:#d1fae5}.msg.err{background:#fee2e2}
        .row{display:flex;gap:1rem;flex-wrap:wrap}.row>*{flex:1;min-width:140px}
        .opt{display:flex;gap:.5rem;align-items:center;margin:.3rem 0}.opt input[type=text]{flex:1}
        .muted{color:#666;font-size:.9rem} img.thumb{max-width:200px;max-height:140px;display:block;margin:.3rem 0}
        #picker{height:320px;border:1px solid #999}
        fieldset{background:#fff;border:1px solid #ddd;margin:1rem 0}
        </style>', $extraHead, '</head><body>';
    if ($admin) {
        echo '<header class="top"><strong>Münster unvermutet – Admin</strong>',
            '<a href="index.php">Stationen</a><a href="suggestions.php">Vorschläge</a>',
            '<span>', h($admin['username']), '</span>',
            '<form method="post" action="logout.php">', csrf_field(), '<button class="secondary">Abmelden</button></form></header>';
    }
    echo '<main>';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        foreach ((array) $f['message'] as $m) {
            echo '<div class="msg ', $f['type'] === 'ok' ? 'ok' : 'err', '">', h($m), '</div>';
        }
    }
    unset($_SESSION['flash']);
}

function admin_footer(): void
{
    echo '</main></body></html>';
}
