<?php
/**
 * CLI Arguments Parser
 *
 * Запуск:
 *   php cli.php command [arguments] [options]
 *
 * Примеры:
 *   php cli.php migrate users --env=prod --force
 *   php cli.php greet Nick --yell
 *   php cli.php task run -vf
 */

declare(strict_types=1);

/**
 * ---------------------------------------
 * 1. Проверка: мы точно в CLI?
 * ---------------------------------------
 */
if (php_sapi_name() !== 'cli') {
    echo "This script can only be run from CLI.\n";
    exit(1);
}

/**
 * ---------------------------------------
 * 2. Сырые аргументы PHP
 * ---------------------------------------
 *
 * $argv — массив аргументов
 * $argc — их количество
 *
 * $argv[0] всегда имя файла!
 */
global $argv, $argc;

/**
 * Удаляем имя файла, чтобы не мешалось
 */
array_shift($argv);

/**
 * ---------------------------------------
 * 3. Структуры для результата
 * ---------------------------------------
 */
$command   = null; // основная команда
$args      = [];   // позиционные аргументы
$options   = [];   // --key=value, --flag, -abc

/**
 * ---------------------------------------
 * 4. Основной цикл парсинга
 * ---------------------------------------
 */
while (($arg = array_shift($argv)) !== null) {

    /**
     * -----------------------------------
     * ДЛИННЫЕ ОПЦИИ: --key или --key=value
     * -----------------------------------
     */
    if (str_starts_with($arg, '--')) {

        // убираем "--"
        $option = substr($arg, 2);

        // --key=value
        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $options[$key] = $value;
            continue;
        }

        // --key value
        if (isset($argv[0]) && !str_starts_with($argv[0], '-')) {
            $options[$option] = array_shift($argv);
            continue;
        }

        // --flag (boolean)
        $options[$option] = true;
        continue;
    }

    /**
     * -----------------------------------
     * КОРОТКИЕ ФЛАГИ: -a -b -abc
     * -----------------------------------
     */
    if (str_starts_with($arg, '-') && strlen($arg) > 1) {

        // убираем "-"
        $flags = substr($arg, 1);

        // -abc → a=true, b=true, c=true
        foreach (str_split($flags) as $flag) {
            $options[$flag] = true;
        }
        continue;
    }

    /**
     * -----------------------------------
     * КОМАНДА
     * -----------------------------------
     *
     * Первая строка без "-" считается командой
     */
    if ($command === null) {
        $command = $arg;
        continue;
    }

    /**
     * -----------------------------------
     * ПОЗИЦИОННЫЕ АРГУМЕНТЫ
     * -----------------------------------
     */
    $args[] = $arg;
}

/**
 * ---------------------------------------
 * 5. Вывод результата (для обучения)
 * ---------------------------------------
 */
echo "Command:\n";
var_dump($command);

echo "\nArguments:\n";
var_dump($args);

echo "\nOptions:\n";
var_dump($options);

/**
 * ---------------------------------------
 * 6. Пример использования данных
 * ---------------------------------------
 */
if ($command === 'greet') {
    $name = $args[0] ?? 'World';

    $greeting = "Hello, $name";

    if (!empty($options['yell'])) {
        $greeting = strtoupper($greeting);
    }

    echo "\n$greeting\n";
}