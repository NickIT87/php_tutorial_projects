<?php
// src/App/Logger.php

namespace App;

class Logger
{
    private string $file;

    public function __construct(string $file)
    {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->file = $file;
    }

    public function log(string $message): void
    {
        $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        file_put_contents($this->file, $line, FILE_APPEND);
    }
}