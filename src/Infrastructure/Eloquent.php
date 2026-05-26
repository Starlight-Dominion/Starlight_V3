<?php

declare(strict_types=1);

namespace sdo\Infrastructure;

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent
{
    public static function boot(): void
    {
        $capsule = new Capsule();

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'],
            'database'  => $_ENV['DB_NAME'],
            'username'  => $_ENV['DB_USER'],
            'password'  => $_ENV['DB_PASS'],
            'port'      => $_ENV['DB_PORT'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
