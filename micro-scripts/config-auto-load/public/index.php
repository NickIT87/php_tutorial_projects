<?php
// public/index.php

declare(strict_types=1);

$container = require __DIR__ . '/../bootstrap.php';

$config = $container['config'];

use App\Logger;

$logger = new Logger($config['log']['path']);
$logger->log('Application started');

echo "Hello from {$config['app_name']}";