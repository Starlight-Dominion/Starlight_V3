<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminService;
use sdo\Services\TrainingService;
use sdo\Services\LogService;
use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
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

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->bigInteger('credits')->default(1000);
            $table->integer('citizens')->default(50);
            $table->integer('turns')->default(100);
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
            $table->timestamps();
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });
    }

    public function testAdminChangeAffectsTraining(): void
    {
        // 1. Setup a unit
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

        // 2. Setup a user
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $dominion = $user->dominion()->create(['name' => 'D', 'credits' => 150, 'citizens' => 10, 'turns' => 10]);

        $logMock = $this->createMock(LogService::class);
        $trainingService = new TrainingService($logMock);
        $adminService = new AdminService();

        // 3. Verify current training works (costs 100 credits)
        $res = $trainingService->train($dominion->id, 'soldiers', 1);
        $this->assertTrue($res['success']);
        
        $dominion->refresh();
        $this->assertEquals(50, $dominion->credits);

        // 4. Admin updates cost to 200 credits
        $adminService->updateUnit((int)$unit->id, ['cost_credits' => 200]);

        // 5. Training should now fail for another unit (remaining credits 50 < 200)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient resources');

        $trainingService->train($dominion->id, 'soldiers', 1);
    }
}
