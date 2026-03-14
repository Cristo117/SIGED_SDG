<?php

/**
 * Carga variables de entorno desde .env (si existe).
 * Las credenciales sensibles deben estar en .env y NUNCA en el código.
 */
function load_env($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }
    if (!file_exists($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1), " \t\n\r\0\x0B\"'");
        if ($key !== '' && !getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
