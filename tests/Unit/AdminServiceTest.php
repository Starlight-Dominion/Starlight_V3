<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminService;
use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminServiceTest extends TestCase
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
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->dateTime('last_untrained')->nullable();
            $table->integer('xp')->default(0);
            $table->timestamps();
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('cost_credits');
            $table->integer('cost_citizens');
            $table->integer('cost_turns');
            $table->integer('power_offense');
            $table->integer('power_defense');
            $table->integer('production_credits')->default(0);
            $table->timestamps();
        });
    }

    public function testGetSystemStats(): void
    {
        User::create(['username' => 'admin', 'email' => 'admin@test.com', 'password' => 'pass', 'is_admin' => true]);
        $user = User::create(['username' => 'player', 'email' => 'player@test.com', 'password' => 'pass']);
        $user->dominion()->create(['name' => 'Test Dominion']);

        $adminService = new AdminService();
        $stats = $adminService->getSystemStats();

        $this->assertEquals(2, $stats['total_users']);
        $this->assertEquals(1, $stats['total_kingdoms']);
        $this->assertArrayHasKey('server_time', $stats);
    }

    public function testSearchDominions(): void
    {
        $user1 = User::create(['username' => 'alpha', 'email' => 'a@t.com', 'password' => 'p']);
        $user1->dominion()->create(['name' => 'First Dominion']);
        
        $user2 = User::create(['username' => 'beta', 'email' => 'b@t.com', 'password' => 'p']);
        $user2->dominion()->create(['name' => 'Second Realm']);

        $adminService = new AdminService();
        
        $results = $adminService->searchDominions('First');
        $this->assertCount(1, $results);
        $this->assertEquals('First Dominion', $results[0]['name']);

        $results = $adminService->searchDominions('alpha');
        $this->assertCount(1, $results);
        $this->assertEquals('alpha', $results[0]['user']['username']);
    }

    public function testUpdateDominionStats(): void
    {
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $dominion = $user->dominion()->create(['name' => 'D', 'credits' => 100]);

        $adminService = new AdminService();
        $res = $adminService->updateDominionStats($dominion->id, ['credits' => 5000, 'xp' => 1000]);

        $this->assertTrue($res);
        $updated = Dominion::find($dominion->id);
        $this->assertEquals(5000, $updated->credits);
        $this->assertEquals(1000, $updated->xp);
    }

    public function testUpdateUnit(): void
    {
        $unit = Unit::create([
            'slug' => 'soldiers',
            'name' => 'Soldiers',
            'description' => 'Desc',
            'cost_credits' => 100,
            'cost_citizens' => 1,
            'cost_turns' => 2,
            'power_offense' => 10,
            'power_defense' => 10,
        ]);

        $adminService = new AdminService();
        $res = $adminService->updateUnit((int)$unit->id, ['cost_credits' => 999]);

        $this->assertTrue($res);
        $updatedUnit = Unit::find($unit->id);
        $this->assertEquals(999, $updatedUnit->cost_credits);
    }
}
