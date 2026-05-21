<?php

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;
use sdo\Infrastructure\Eloquent;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use sdo\Repositories\Interfaces\BankRepositoryInterface;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;
use sdo\Repositories\Eloquent\EloquentUserRepository;
use sdo\Repositories\Eloquent\EloquentKingdomRepository;
use sdo\Repositories\Eloquent\EloquentBankRepository;
use sdo\Repositories\Eloquent\EloquentCombatRepository;

// Boot Eloquent ORM
Eloquent::boot();

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Database connection
    PDO::class => function (ContainerInterface $c) {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4",
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME'],
            $_ENV['DB_PORT']
        );
        return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    },

    // Eloquent Capsule Manager
    Capsule::class => fn() => Capsule::getInstance(),

    // Redis connection
    Predis\Client::class => function (ContainerInterface $c) {
        return new Predis\Client([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
        ]);
    },

    // Repository Bindings
    UserRepositoryInterface::class => DI\create(EloquentUserRepository::class),
    KingdomRepositoryInterface::class => DI\create(EloquentKingdomRepository::class),
    BankRepositoryInterface::class => DI\create(EloquentBankRepository::class),
    CombatRepositoryInterface::class => DI\create(EloquentCombatRepository::class),
    \sdo\Services\ConfigService::class => DI\create(),
]);

return $containerBuilder->build();