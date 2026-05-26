<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicDir = dirname(__DIR__, 2) . '/public';
$assetPath = $publicDir . $uri;

if ($uri !== '/' && is_file($assetPath)) {
    return false;
}

require $publicDir . '/index.php';
