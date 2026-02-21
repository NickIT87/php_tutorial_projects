<?php
// bootstrap.php

declare(strict_types=1);

/**
 * 1. Автозагрузка классов
 */
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/App/';

    // если класс не из нашего namespace — игнор
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * 2. Загрузка конфига
 */
$config = require __DIR__ . '/config/app.php';

/**
 * 3. Возвращаем всё, что нужно приложению
 */
return [
    'config' => $config,
];