<?php

declare(strict_types=1);

/**
 * -----------------------------------------
 * CLI аргументы
 * -----------------------------------------
 *
 * --ext=php     фильтр по расширению (опционально)
 * --depth=2     максимальная глубина (опционально)
 * --dir=/path   корневая директория (опционально, по умолчанию /)
 */

$options = getopt('', ['ext::', 'depth::', 'dir::']);

$rootDir = $options['dir'] ?? '/';
$filterExtension = $options['ext'] ?? null;
$maxDepth = isset($options['depth']) ? (int)$options['depth'] : null;

// Нормализация расширения
if ($filterExtension !== null) {
    $filterExtension = ltrim($filterExtension, '.');
}

/**
 * -----------------------------------------
 * Рекурсивный сканер
 * -----------------------------------------
 */
function scanDirectory(
    string $dir,
    int $level = 0,
    ?string $extFilter = null,
    ?int $maxDepth = null
): void {
    // Ограничение глубины
    if ($maxDepth !== null && $level > $maxDepth) {
        return;
    }

    // Проверка прав доступа
    if (!is_readable($dir)) {
        echo str_repeat('  ', $level) . "🚫 [NO ACCESS] $dir" . PHP_EOL;
        return;
    }

    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = rtrim($dir, '/') . '/' . $item;
        $indent = str_repeat('  ', $level);

        if (is_dir($path)) {
            echo $indent . "📁 $item" . PHP_EOL;
            scanDirectory($path, $level + 1, $extFilter, $maxDepth);
            continue;
        }

        if ($extFilter !== null) {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if ($ext !== $extFilter) {
                continue;
            }
        }

        echo $indent . "📄 $item" . PHP_EOL;
    }
}

/**
 * -----------------------------------------
 * Запуск
 * -----------------------------------------
 */

echo "Scanning directory: $rootDir" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

scanDirectory(
    dir: $rootDir,
    level: 0,
    extFilter: $filterExtension,
    maxDepth: $maxDepth
);
