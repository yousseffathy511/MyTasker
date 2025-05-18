<?php
/**
 * Bootstrap File
 *
 * Loads Composer’s autoloader and parses the .env file into environment variables.
 * No external dependencies required.
 */

declare(strict_types=1);

// 1) Bring in Composer’s autoloader
require_once __DIR__ . '/vendor/autoload.php';

// 2) Load and parse .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue; // skip blanks and comments
        }
        if (strpos($line, '=') === false) {
            continue; // skip malformed lines
        }
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // strip surrounding quotes
        if (preg_match('/^([\'"])(.*)\1$/', $val, $m)) {
            $val = $m[2];
        }
        putenv("$key=$val");
        $_ENV[$key]    = $val;
        $_SERVER[$key] = $val;
    }
}
