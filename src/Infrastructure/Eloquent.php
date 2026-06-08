<?php

declare(strict_types=1);

namespace sdo\Infrastructure;

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent
{
    public static function boot(): void
    {
        $capsule = new Capsule();

        $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
        $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'sdo');
        $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
        $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
        $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306);

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $host,
            'database'  => $name,
            'username'  => $user,
            'password'  => $pass,
            'port'      => $port,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
