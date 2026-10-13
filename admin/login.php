<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (current_admin() !== null) {
    redirect('index.php');
}
start_admin_session();

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $ip = client_ip();
    $max = (int) config('login_max_attempts');
    $window = (int) config('login_window');
    if (rate_limit_exceeded('login', $ip, $max, $window)) {
        $error = 'Zu viele Fehlversuche. Bitte später erneut versuchen.';
    } else {
        $user = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (attempt_login($user, $pass)) {
            rate_limit_clear('login', $ip);
            redirect('index.php');
        }
        rate_limit_hit('login', $ip);
        $error = 'Benutzername oder Passwort ist falsch.';
    }
} elseif (isset($_GET['expired'])) {
    $error = 'Bitte melde dich an (Sitzung abgelaufen oder nicht angemeldet).';
}

admin_header('Login');
?>
<h1>Admin-Login</h1>
<?php if ($error): ?><div class="msg err"><?= h($error) ?></div><?php endif; ?>
<form method="post" autocomplete="off" style="max-width:360px">
  <?= csrf_field() ?>
  <label for="username">Benutzername</label>
  <input type="text" id="username" name="username" required autofocus>
  <label for="password">Passwort</label>
  <input type="password" id="password" name="password" required>
  <p><button>Anmelden</button></p>
</form>
<?php admin_footer();
