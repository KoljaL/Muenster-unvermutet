<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(config('session_name'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/** Liefert den eingeloggten Admin oder null; erneuert die Inaktivitätsfrist. */
function current_admin(): ?array
{
    start_admin_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    if (time() - (int) ($_SESSION['last_activity'] ?? 0) > config('session_timeout')) {
        logout_admin();
        start_admin_session();
        return null;
    }
    $_SESSION['last_activity'] = time();
    return ['id' => (int) $_SESSION['admin_id'], 'username' => (string) $_SESSION['admin_name']];
}

function require_admin(): array
{
    $admin = current_admin();
    if ($admin === null) {
        header('Location: login.php?expired=1');
        exit;
    }
    return $admin;
}

function logout_admin(): void
{
    start_admin_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

/** @return bool true bei Erfolg */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? AND active = 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    // Konstante Rechenzeit auch bei unbekanntem Benutzer.
    $hash = $admin['password_hash'] ?? '$2y$10$usesomesillystringforsalt.abcdefghijklmnopqrstuvwxyz012';
    $ok = password_verify($password, $hash) && $admin;
    if (!$ok) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_name'] = $admin['username'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return true;
}
