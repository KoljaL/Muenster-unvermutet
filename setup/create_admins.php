<?php
declare(strict_types=1);

// Legt Admin-Konten an oder setzt Passwörter zurück. Nur per CLI.
//   php setup/create_admins.php                      -> drei Testkonten (nur Entwicklung!)
//   php setup/create_admins.php <name> <passwort>    -> einzelnes Konto
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$accounts = $argc >= 3
    ? [$argv[1] => $argv[2]]
    : [
        'admin1' => 'Muenster-Test-2026-1!',
        'admin2' => 'Muenster-Test-2026-2!',
        'admin3' => 'Muenster-Test-2026-3!',
    ];

$stmt = db()->prepare(
    'INSERT INTO admins (username, password_hash, active, created_at) VALUES (?, ?, 1, ?)
     ON CONFLICT(username) DO UPDATE SET password_hash = excluded.password_hash, active = 1'
);
foreach ($accounts as $name => $password) {
    if (strlen($password) < 12) {
        fwrite(STDERR, "Passwort für {$name} ist zu kurz (mind. 12 Zeichen).\n");
        exit(1);
    }
    $stmt->execute([$name, password_hash($password, PASSWORD_DEFAULT), now()]);
    echo "Konto gesetzt: {$name}\n";
}
