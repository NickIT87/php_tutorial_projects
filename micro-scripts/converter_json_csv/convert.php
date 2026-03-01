<?php

declare(strict_types=1);

/**
 * ============================================================
 * JSON ⇄ CSV Converter (Advanced)
 * ============================================================
 *
 * Возможности:
 *  - JSON → CSV (с расплющиванием вложенных структур)
 *  - CSV → JSON (с восстановлением вложенности)
 *  - Поддержка массивов через индексы (phones.0, phones.1)
 *
 * Использование:
 * php convert.php --from=json --to=csv --input=data.json --output=data.csv
 * php convert.php --from=csv --to=json --input=data.csv --output=data.json
 *
 * ============================================================
 */

/**
 * ------------------------------------------------------------
 * настройки для fputcsv
 * ------------------------------------------------------------
 */
$delimiter = ',';
$enclosure = '"';
$escape    = '\\';

/**
 * ------------------------------------------------------------
 * Чтение CLI аргументов
 * ------------------------------------------------------------
 */

$options = getopt('', [
    'from:',   // json | csv
    'to:',     // json | csv
    'input:',  // входной файл
    'output:'  // выходной файл
]);

$from   = $options['from']   ?? null;
$to     = $options['to']     ?? null;
$input  = $options['input']  ?? null;
$output = $options['output'] ?? null;

/**
 * Проверка аргументов
 */
if (!$from || !$to || !$input || !$output) {
    exit("Usage: php convert.php --from=json|csv --to=json|csv --input=file --output=file" . PHP_EOL);
}

if (!file_exists($input)) {
    exit("Input file not found: $input" . PHP_EOL);
}

/**
 * Маршрутизация конвертации
 */
if ($from === 'json' && $to === 'csv') {
    jsonToCsv($input, $output);
} elseif ($from === 'csv' && $to === 'json') {
    csvToJson($input, $output);
} else {
    exit("Unsupported conversion: $from → $to" . PHP_EOL);
}

echo "Conversion completed successfully." . PHP_EOL;


/**
 * ============================================================
 * JSON → CSV
 * ============================================================
 *
 * 1. Читаем JSON
 * 2. Декодируем в массив
 * 3. Расплющиваем вложенные структуры
 * 4. Собираем все возможные заголовки
 * 5. Записываем CSV
 */
function jsonToCsv(string $input, string $output): void
{
    global $delimiter, $enclosure, $escape;

    $json = file_get_contents($input);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        exit("Invalid JSON format" . PHP_EOL);
    }

    $flattenedRows = [];

    // Расплющиваем каждую строку
    foreach ($data as $row) {
        $flattenedRows[] = flattenArray($row);
    }

    // Собираем все возможные заголовки
    $headers = collectHeaders($flattenedRows);

    $handle = fopen($output, 'w');

    // Записываем заголовки
    fputcsv($handle, $headers, $delimiter, $enclosure, $escape);

    // Записываем строки
    foreach ($flattenedRows as $row) {
        $line = [];

        foreach ($headers as $header) {
            $line[] = $row[$header] ?? '';
        }

        fputcsv($handle, $line, $delimiter, $enclosure, $escape);
    }

    fclose($handle);
}


/**
 * ============================================================
 * CSV → JSON (с восстановлением структуры)
 * ============================================================
 *
 * 1. Читаем CSV
 * 2. Берём заголовки
 * 3. Для каждой строки:
 *      - создаём вложенный массив
 *      - вставляем значения по пути (a.b.c)
 */
function csvToJson(string $input, string $output): void
{
    global $delimiter, $enclosure, $escape;

    $handle = fopen($input, 'r');

    // Первая строка — заголовки
    $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);

    $result = [];

    while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {

        $rowAssoc = array_combine($headers, $row);

        $nestedRow = [];

        foreach ($rowAssoc as $key => $value) {

            // Пропускаем пустые значения
            if ($value === '') {
                continue;
            }

            insertNestedValue($nestedRow, $key, $value);
        }

        $result[] = $nestedRow;
    }

    fclose($handle);

    file_put_contents(
        $output,
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}


/**
 * ============================================================
 * Flatten nested array
 * ============================================================
 *
 * Превращает:
 *
 * [
 *   "address" => [
 *      "city" => "Lviv"
 *   ]
 * ]
 *
 * В:
 *
 * [
 *   "address.city" => "Lviv"
 * ]
 */
function flattenArray(array $array, string $prefix = ''): array
{
    $result = [];

    foreach ($array as $key => $value) {

        // Формируем новый ключ
        $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

        if (is_array($value)) {
            // Если вложенный массив — рекурсивно расплющиваем
            $result += flattenArray($value, $newKey);
        } else {
            $result[$newKey] = $value;
        }
    }

    return $result;
}


/**
 * ============================================================
 * Вставка значения во вложенный массив
 * ============================================================
 *
 * "address.city" → ["address"]["city"]
 * "phones.0"     → ["phones"][0]
 */
function insertNestedValue(array &$array, string $key, mixed $value): void
{
    $parts = explode('.', $key);

    $current = &$array;

    foreach ($parts as $index => $part) {

        $isLast = $index === count($parts) - 1;

        // Если последний уровень — вставляем значение
        if ($isLast) {

            if (is_numeric($part)) {
                $current[(int)$part] = $value;
            } else {
                $current[$part] = $value;
            }

            return;
        }

        // Если промежуточный уровень отсутствует — создаём массив
        if (!isset($current[$part])) {
            $current[$part] = [];
        }

        $current = &$current[$part];
    }
}


/**
 * ============================================================
 * Сбор всех возможных заголовков
 * ============================================================
 */
function collectHeaders(array $rows): array
{
    $headers = [];

    foreach ($rows as $row) {
        $headers = array_unique(
            array_merge($headers, array_keys($row))
        );
    }

    sort($headers);

    return $headers;
}