<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Controllers\HomeController;
use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class HomeControllerTest extends TestCase
{
    private HomeController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username');
            $table->timestamps();
        });

        Capsule::schema()->create('battle_logs', function ($table) {
            $table->increments('id');
            $table->timestamp('battle_time');
        });

        $gameService = $this->createMock(GameService::class);
        $advisorService = $this->createMock(AdvisorService::class);
        $configService = $this->createMock(ConfigService::class);
        $authService = $this->createMock(AuthService::class);

        $this->controller = new HomeController(
            $this->gameService ?? $this->createMock(\sdo\Services\GameService::class),
            $this->advisorService ?? $this->createMock(\sdo\Services\AdvisorService::class),
            $this->configService ?? $this->createMock(\sdo\Services\ConfigService::class),
            $this->authService ?? $this->createMock(\sdo\Services\AuthService::class),
            new \sdo\Repositories\Eloquent\EloquentUserRepository(),
            new \sdo\Repositories\Eloquent\EloquentCombatRepository()
        );
    }

    public function testIndexReturnsWelcomeMessage(): void
    {
        $response = $this->controller->index();
        $this->assertIsString($response);
        $this->assertStringContainsString('Starlight Dominion', $response);
    }
}
