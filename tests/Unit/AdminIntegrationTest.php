<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminService;
use sdo\Services\TrainingService;
use sdo\Models\User;
use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminIntegrationTest extends TestCase
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

    public function testAdminChangeAffectsTraining(): void
    {
        // 1. Setup a unit
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

        // 2. Setup a user
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $kingdom = $user->kingdom()->create(['kingdom_name' => 'K', 'gold' => 150, 'citizens' => 10, 'turns' => 10]);

        $trainingService = new TrainingService();
        $adminService = new AdminService();

        // 3. Verify current training works (costs 100 gold)
        $res = $trainingService->train($kingdom->id, 'soldiers', 1);
        $this->assertTrue($res['success']);
        
        $kingdom->refresh();
        $this->assertEquals(50, $kingdom->gold);

        // 4. Admin updates cost to 200 gold
        $adminService->updateUnit($unitId, ['cost_gold' => 200]);

        // 5. Training should now fail for another unit (remaining gold 50 < 200)
        $res = $trainingService->train($kingdom->id, 'soldiers', 1);
        $this->assertFalse($res['success']);
        $this->assertEquals('Insufficient gold.', $res['message']);
    }
}
