<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$envOrDefault = static function (string $key, string $default): string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value == '') {
        return $default;
    }

    return (string) $value;
};

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => $envOrDefault('DB_HOST', 'localhost'),
            'name' => $envOrDefault('DB_NAME', 'sdo'),
            'user' => $envOrDefault('DB_USER', 'sdo_admin'),
            'pass' => $envOrDefault('DB_PASS', 'password'),
            'port' => $envOrDefault('DB_PORT', '3306'),
            'charset' => 'utf8mb4',
        ]
    ],
    'version_order' => 'creation'
];