<?php
declare(strict_types=1);

function json_send(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

function json_ok($data, int $status = 200): void
{
    json_send($status, ['data' => $data]);
}

function json_error(int $status, string $code, string $message): void
{
    json_send($status, ['data' => null, 'error' => ['code' => $code, 'message' => $message]]);
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        json_error(405, 'method_not_allowed', 'Methode nicht erlaubt.');
    }
}
