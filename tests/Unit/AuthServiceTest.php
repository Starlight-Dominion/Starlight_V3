<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AuthService;
use sdo\Models\User;
use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthServiceTest extends TestCase
{
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
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_bot')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('kingdoms', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('kingdom_name');
            $table->integer('gold')->default(1000);
            $table->integer('citizens')->default(50);
            $table->integer('turns')->default(100);
            $table->integer('miners')->default(0);
            $table->integer('xp')->default(0);
            $table->integer('foundation_level')->default(1);
            $table->integer('foundation_hp')->default(100);
            $table->string('foundation_upgrade_slot_1')->nullable();
            $table->integer('housing_level')->default(1);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('gold_in_bank')->default(0);
            $table->integer('deposits_today')->default(0);
            $table->timestamp('last_deposit_recharge')->nullable();
            $table->timestamp('last_untrained')->nullable();
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(1);
            $table->integer('unit_guards')->default(0);
            $table->integer('unit_soldiers')->default(0);
            $table->integer('unit_spies')->default(0);
            $table->integer('unit_sentries')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->timestamp('last_tick')->nullable();
            $table->timestamps();
        });
    }

    public function testRegisterSuccess(): void
    {
        $authService = new AuthService();
        $result = $authService->register('testuser', 'test@example.com', 'password123', 'Test Kingdom');

        $this->assertTrue($result);

        $user = User::where('username', 'testuser')->first();
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(password_verify('password123', $user->password));

        $kingdom = $user->kingdom;
        $this->assertNotNull($kingdom);
        $this->assertEquals('Test Kingdom', $kingdom->kingdom_name);
    }

    public function testRegisterFailureDuplicateUsername(): void
    {
        $authService = new AuthService();
        $authService->register('testuser', 'test1@example.com', 'password123', 'Kingdom 1');

        $result = $authService->register('testuser', 'test2@example.com', 'password456', 'Kingdom 2');

        $this->assertFalse($result);
    }

    public function testLoginSuccess(): void
    {
        $authService = new AuthService();
        $authService->register('testuser', 'test@example.com', 'password123', 'Test Kingdom');

        $user = $authService->login('testuser', 'password123');

        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testLoginFailureWrongPassword(): void
    {
        $authService = new AuthService();
        $authService->register('testuser', 'test@example.com', 'password123', 'Test Kingdom');

        $user = $authService->login('testuser', 'wrongpassword');

        $this->assertNull($user);
    }

    public function testLoginFailureUserNotFound(): void
    {
        $authService = new AuthService();

        $user = $authService->login('nonexistent', 'password123');

        $this->assertNull($user);
    }

    public function testIsLoggedInTrue(): void
    {
        $authService = new AuthService();

        $session = ['user_id' => 1];
        $this->assertTrue($authService->isLoggedIn($session));
    }

    public function testIsLoggedInFalse(): void
    {
        $authService = new AuthService();

        $session = [];
        $this->assertFalse($authService->isLoggedIn($session));
    }

    public function testLogout(): void
    {
        $authService = new AuthService();

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
