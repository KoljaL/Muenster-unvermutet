<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/error_handling.php';
require_once __DIR__ . '/../includes/json.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/uploads.php';

ini_set('display_errors', '0');

install_error_handlers('muenster-api', function (Throwable $e): void {
    $message = 'Interner Serverfehler.';
    if (!empty(config()['debug'])) {
        $message .= ' ' . get_class($e) . ': ' . $e->getMessage();
    }
    json_error(500, 'server_error', $message);
});
