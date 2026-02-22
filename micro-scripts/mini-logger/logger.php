<?php
/**
 * Mini Logger
 *
 * Записывает сообщения в файл с датой и уровнем.
 *
 * Пример:
 *   log_message('info', 'Application started');
 */

declare(strict_types=1);

/**
 * -----------------------------------------
 * 1. Базовые настройки логгера
 * -----------------------------------------
 */

// Путь к файлу лога
$logFile = __DIR__ . '/logs/app.log';

// Допустимые уровни логирования
$allowedLevels = ['debug', 'info', 'warning', 'error'];

/**
 * -----------------------------------------
 * 2. Функция логирования
 * -----------------------------------------
 */
function log_message(string $level, string $message): void
{
    global $logFile, $allowedLevels;

    // Приводим уровень к нижнему регистру
    $level = strtolower($level);

    // Проверка уровня логирования
    if (!in_array($level, $allowedLevels, true)) {
        throw new InvalidArgumentException("Invalid log level: $level");
    }

    // Гарантируем, что директория существует
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Формируем строку лога
    $date = date('Y-m-d H:i:s');
    $line = "[$date] [$level] $message" . PHP_EOL;

    /**
     * fopen:
     *  'a' — append (добавлять в конец файла)
     *  файл создаётся автоматически, если его нет
     */
    $handle = fopen($logFile, 'a');

    if ($handle === false) {
        throw new RuntimeException('Cannot open log file');
    }

    fwrite($handle, $line);
    fclose($handle);
}

/**
 * -----------------------------------------
 * 3. Примеры использования
 * -----------------------------------------
 */

log_message('info', 'Application started');
log_message('debug', 'Debugging value x=42');
log_message('warning', 'Low disk space');
log_message('error', 'Something went wrong');

echo "Logs written successfully" . PHP_EOL;