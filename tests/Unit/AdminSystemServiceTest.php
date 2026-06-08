<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminSystemService;
use sdo\Models\User;
use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminSystemServiceTest extends TestCase
{
    private AdminSystemService $service;

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
        
        $this->service = new AdminSystemService(
            new \sdo\Repositories\Eloquent\EloquentUserRepository(),
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentLogRepository(),
            new \sdo\Repositories\Eloquent\EloquentAdminLogRepository(),
            new \sdo\Repositories\Eloquent\EloquentRecruitmentLogRepository()
        );
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('game_settings', function ($table) { $table->string('setting_key')->unique(); $table->text('setting_value')->nullable(); });
        Capsule::schema()->create('races', function ($table) { $table->increments('id'); $table->string('name'); $table->string('slug'); $table->text('description')->nullable(); });
        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(0);
            $table->integer('citizens')->default(0);
            $table->timestamps();
        });
        
        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->increments('id');
            $table->integer('dominion_id');
            $table->integer('unit_id');
            $table->integer('total_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function testGetSystemStats(): void
    {
        User::create(['username' => 'admin', 'email' => 'admin@test.com', 'password' => 'pass', 'is_admin' => true]);
        $user = User::create(['username' => 'player', 'email' => 'player@test.com', 'password' => 'pass']);
        $user->dominion()->create(['name' => 'Test Dominion']);

        $stats = $this->service->getSystemStats();

        $this->assertEquals(2, $stats['total_users']);
        $this->assertEquals(1, $stats['total_kingdoms']);
        $this->assertArrayHasKey('server_time', $stats);
    }
}
