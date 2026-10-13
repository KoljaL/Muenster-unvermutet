<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

start_admin_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    logout_admin();
}
redirect('login.php');
