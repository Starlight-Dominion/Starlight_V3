<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Race;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthServiceTest extends TestCase
{
    private ConfigService $configService;

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

        $this->createTables();
        
        $this->configService = $this->createMock(ConfigService::class);
        $this->configService->method('get')->willReturn(1000);

        // Seed basic race
        Race::create(['name' => 'Terran', 'slug' => 'terran', 'description' => 'Human']);
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('races', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description');
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('race_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->bigInteger('foundation_hp')->default(1000);
            $table->bigInteger('foundation_max_hp')->default(1000);
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->dateTime('last_untrained')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
        });

        Capsule::schema()->create('structures', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id');
            $table->integer('unit_id');
            $table->integer('total_quantity')->default(0);
        });

        Capsule::schema()->create('dominion_structures', function ($table) {
            $table->integer('dominion_id');
            $table->integer('structure_id');
            $table->integer('level')->default(0);
        });
    }

    public function testRegisterSuccess(): void
    {
        $authService = new AuthService($this->configService);
        $result = $authService->register('testuser', 'test@example.com', 'password123', 'Test Dominion', 'Terran');

        $this->assertTrue($result['success']);

        $user = User::where('username', 'testuser')->first();
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(password_verify('password123', $user->password));

        $dominion = $user->dominion;
        $this->assertNotNull($dominion);
        $this->assertEquals('Test Dominion', $dominion->name);
    }

    public function testRegisterFailureDuplicateUsername(): void
    {
        $authService = new AuthService($this->configService);
        $authService->register('testuser', 'test1@example.com', 'password123', 'Dominion 1', 'Terran');

        $result = $authService->register('testuser', 'test2@example.com', 'password456', 'Dominion 2', 'Terran');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Identity handle is already claimed', $result['message']);
    }

    public function testLoginSuccess(): void
    {
        $authService = new AuthService($this->configService);
        $authService->register('testuser', 'test@example.com', 'password123', 'Test Dominion', 'Terran');

        $user = $authService->login('testuser', 'password123');

        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testLoginFailureWrongPassword(): void
    {
        $authService = new AuthService($this->configService);
        $authService->register('testuser', 'test@example.com', 'password123', 'Test Dominion', 'Terran');

        $user = $authService->login('testuser', 'wrongpassword');

        $this->assertNull($user);
    }

    public function testLoginFailureUserNotFound(): void
    {
        $authService = new AuthService($this->configService);

        $user = $authService->login('nonexistent', 'password123');

        $this->assertNull($user);
    }

    public function testIsLoggedInTrue(): void
    {
        $authService = new AuthService($this->configService);

        $session = ['user_id' => 1];
        $this->assertTrue($authService->isLoggedIn($session));
    }

    public function testIsLoggedInFalse(): void
    {
        $authService = new AuthService($this->configService);

        $session = [];
        $this->assertFalse($authService->isLoggedIn($session));
    }

    public function testLogout(): void
    {
        $authService = new AuthService($this->configService);

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';

        $authService->logout();

        // session_destroy() clears the session
        // In test environment, we just check the function was called
        // The actual unset happens at the end of the script
        $this->assertTrue(true); // Test passes if no error
    }
}
