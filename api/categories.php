<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_method('GET');

json_ok(array_map(static fn(array $c): array => [
    'id' => $c['id'],
    'name' => $c['name'],
    'description' => $c['description'],
    'icon' => $c['icon'],
], all_categories()));
