<?php

$host = 'localhost';
$port = 1337;
$serverFile = __DIR__ . '/server.php';

echo "Starting Laravel development server at http://{$host}:{$port}\n";
echo "Press Ctrl+C to stop\n";

passthru("php -S {$host}:{$port} {$serverFile}");
