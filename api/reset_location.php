<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/location.php';
require_method('POST');
require_same_origin();

start_game_session();
unset($_SESSION['verified_stations']);
json_ok(['reset' => true]);
