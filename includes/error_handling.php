<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

const LOGGED_WARNING_LEVELS = E_WARNING | E_USER_WARNING | E_DEPRECATED | E_USER_DEPRECATED | E_RECOVERABLE_ERROR;
const MAX_WARNINGS_PER_REQUEST = 5;

/** Registriert Handler für Warnungen, Fatal Errors und nicht abgefangene Exceptions. */
function install_error_handlers(string $scope, callable $onException): void
{
    set_error_handler(function (int $level, string $message, string $file, int $line) use ($scope): bool {
        static $count = 0;
        static $busy = false;
        // Unterdrückte (@) Meldungen und Notices werden nicht protokolliert; Rekursion und Flut werden verhindert.
        if (!(error_reporting() & $level) || !($level & LOGGED_WARNING_LEVELS) || $busy || $count >= MAX_WARNINGS_PER_REQUEST) {
            return false;
        }
        $busy = true;
        $count++;
        log_server_error($scope . ' warning', $message . ' (' . basename($file) . ':' . $line . ')');
        $busy = false;
        return false;
    });

    register_shutdown_function(function () use ($scope): void {
        $last = error_get_last();
        if ($last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            log_server_error($scope . ' fatal', $last['message'] . ' (' . basename($last['file']) . ':' . $last['line'] . ')');
        }
    });

    set_exception_handler(function (Throwable $e) use ($scope, $onException): void {
        log_server_error($scope, $e);
        $onException($e);
    });
}
