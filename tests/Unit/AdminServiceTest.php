<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminService;
use sdo\Models\User;
use sdo\Models\Kingdom;
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

        Capsule::schema()->create('kingdoms', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('kingdom_name');
            $table->integer('gold')->default(1000);
            $table->integer('citizens')->default(50);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('foundation_level')->default(1);
            $table->integer('armory_level')->default(0);
            $table->integer('stable_level')->default(0);
            $table->integer('unit_guards')->default(0);
            $table->integer('unit_soldiers')->default(0);
            $table->integer('unit_spies')->default(0);
            $table->integer('unit_sentries')->default(0);
            $table->timestamps();
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('cost_gold');
            $table->integer('cost_citizens');
            $table->integer('cost_turns');
            $table->integer('power_offense');
            $table->integer('power_defense');
        });
    }

    public function testGetSystemStats(): void
    {
        User::create(['username' => 'admin', 'email' => 'admin@test.com', 'password' => 'pass', 'is_admin' => true]);
        $user = User::create(['username' => 'player', 'email' => 'player@test.com', 'password' => 'pass']);
        $user->kingdom()->create(['kingdom_name' => 'Test Kingdom']);

        $adminService = new AdminService();
        $stats = $adminService->getSystemStats();

        $this->assertEquals(2, $stats['total_users']);
        $this->assertEquals(1, $stats['total_kingdoms']);
        $this->assertArrayHasKey('server_time', $stats);
    }

    public function testSearchKingdoms(): void
    {
        $user1 = User::create(['username' => 'alpha', 'email' => 'a@t.com', 'password' => 'p']);
        $user1->kingdom()->create(['kingdom_name' => 'First Kingdom']);
        
        $user2 = User::create(['username' => 'beta', 'email' => 'b@t.com', 'password' => 'p']);
        $user2->kingdom()->create(['kingdom_name' => 'Second Realm']);

        $adminService = new AdminService();
        
        $results = $adminService->searchKingdoms('First');
        $this->assertCount(1, $results);
        $this->assertEquals('First Kingdom', $results[0]['kingdom_name']);

        $results = $adminService->searchKingdoms('alpha');
        $this->assertCount(1, $results);
        $this->assertEquals('alpha', $results[0]['user']['username']);
    }

    public function testUpdateKingdomStats(): void
    {
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $kingdom = $user->kingdom()->create(['kingdom_name' => 'K', 'gold' => 100]);

        $adminService = new AdminService();
        $res = $adminService->updateKingdomStats($kingdom->id, ['gold' => 5000, 'xp' => 1000]);

        $this->assertTrue($res);
        $updated = Kingdom::find($kingdom->id);
        $this->assertEquals(5000, $updated->gold);
        $this->assertEquals(1000, $updated->xp);
    }

    public function testUpdateUnit(): void
    {
        $unitId = Capsule::table('units')->insertGetId([
            'slug' => 'soldiers',
            'name' => 'Soldiers',
            'description' => 'Desc',
            'cost_gold' => 100,
            'cost_citizens' => 1,
            'cost_turns' => 2,
            'power_offense' => 10,
            'power_defense' => 10,
        ]);

        $adminService = new AdminService();
        $res = $adminService->updateUnit($unitId, ['cost_gold' => 999]);

        $this->assertTrue($res);
        $unit = Capsule::table('units')->where('id', $unitId)->first();
        $this->assertEquals(999, $unit->cost_gold);
    }
}
