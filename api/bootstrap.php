<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/json.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/uploads.php';

ini_set('display_errors', '0');

set_exception_handler(function (Throwable $e): void {
    error_log('[muenster-api] ' . $e);
    json_error(500, 'server_error', 'Interner Serverfehler.');
});
