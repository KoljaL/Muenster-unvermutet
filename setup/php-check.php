<?php
declare(strict_types=1);

$token = 'DEIN_TOKEN';
if (!hash_equals($token, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$drivers = PDO::getAvailableDrivers();
$result = [
    'php_version' => PHP_VERSION,
    'pdo_sqlite' => in_array('sqlite', $drivers, true),
    'sqlite3_extension' => extension_loaded('sqlite3'),
];

try {
    require_once __DIR__ . '/includes/config.php';
    $path = (string) config('db_path');
    $directory = dirname($path);

    $result['database_file_exists'] = is_file($path);
    $result['database_file_readable'] = is_readable($path);
    $result['database_file_writable'] = is_writable($path);
    $result['database_directory_writable'] = is_writable($directory);

    if ($result['pdo_sqlite'] && $result['database_file_exists'] && $result['database_file_readable']) {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $result['database_connection'] = 'ok';
        $result['database_tables'] = $pdo
    ->query("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name")
    ->fetchAll(PDO::FETCH_COLUMN);
        foreach ([
            'categories' => ['id', 'name', 'description', 'icon', 'color'],
            'admins' => ['id', 'username', 'password_hash', 'active', 'must_change_password'],
        ] as $table => $requiredColumns) {
            $stmt = $pdo->query('PRAGMA table_info("' . $table . '")');
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            $result['tables'][$table] = [
                'exists' => $columns !== [],
                'missing_columns' => array_values(array_diff($requiredColumns, $columns)),
            ];
        }
    }
} catch (Throwable $e) {
    $result['error'] = get_class($e) . ': ' . $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";