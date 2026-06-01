<?php

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;
use sdo\Infrastructure\Eloquent;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\BankRepositoryInterface;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;
use sdo\Repositories\Eloquent\EloquentUserRepository;
use sdo\Repositories\Eloquent\EloquentDominionRepository;
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
    DominionRepositoryInterface::class => DI\create(EloquentDominionRepository::class),
    BankRepositoryInterface::class => DI\create(EloquentBankRepository::class),
    CombatRepositoryInterface::class => DI\create(EloquentCombatRepository::class),
    \sdo\Repositories\Interfaces\UnitRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentUnitRepository::class),
    \sdo\Repositories\Interfaces\StructureRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentStructureRepository::class),
    \sdo\Repositories\Interfaces\DominionStructureRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentDominionStructureRepository::class),
    \sdo\Repositories\Interfaces\ArmoryRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentArmoryRepository::class),
    \sdo\Repositories\Interfaces\DominionArmoryRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentDominionArmoryRepository::class),
    \sdo\Repositories\Interfaces\ManpowerRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentManpowerRepository::class),
    \sdo\Repositories\Interfaces\LogRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentLogRepository::class),
    \sdo\Repositories\Interfaces\AdminLogRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentAdminLogRepository::class),
    \sdo\Repositories\Interfaces\RecruitmentLogRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentRecruitmentLogRepository::class),
    \sdo\Repositories\Interfaces\RaceRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentRaceRepository::class),
    \sdo\Repositories\Interfaces\ApiRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentApiRepository::class),
    \sdo\Repositories\Interfaces\ConfigRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentConfigRepository::class),
    \sdo\Repositories\Interfaces\DiscordLinkRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentDiscordLinkRepository::class),
    \sdo\Repositories\Interfaces\RecruitmentRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentRecruitmentRepository::class),
    \sdo\Repositories\Interfaces\TickRepositoryInterface::class => DI\create(\sdo\Repositories\Eloquent\EloquentTickRepository::class),
    \sdo\Services\ConfigService::class => DI\autowire(),
    \sdo\Services\ApiService::class => DI\autowire(),
    \sdo\Services\AdminPlayerService::class => DI\autowire(),
    \sdo\Services\AdminGameDataService::class => DI\autowire(),
    \sdo\Services\AdminSystemService::class => DI\autowire(),
    \sdo\Services\AdminAutomationService::class => DI\autowire(),
    \sdo\Services\BotAutomationService::class => DI\autowire(),
    \sdo\Services\DiscordLinkService::class => DI\autowire(),
    \sdo\Services\RateLimitService::class => DI\autowire(),
    \sdo\Infrastructure\ApiAuthMiddleware::class => DI\autowire(),
    \sdo\Infrastructure\TransactionManager::class => DI\autowire(),
]);

return $containerBuilder->build();