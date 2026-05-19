<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

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
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'sdo',
            'user' => $_ENV['DB_USER'] ?? 'sdo_admin',
            'pass' => $_ENV['DB_PASS'] ?? 'password',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
        ]
    ],
    'version_order' => 'creation'
];