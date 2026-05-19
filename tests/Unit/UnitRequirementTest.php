<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\TrainingService;
use sdo\Models\Kingdom;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class UnitRequirementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->createTables();
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->boolean('is_bot')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('kingdoms', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('kingdom_name');
            $table->integer('gold')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('foundation_level')->default(1);
            $table->integer('armory_level')->default(0);
            $table->integer('stable_level')->default(0);
            $table->integer('unit_guards')->default(0);
            $table->integer('unit_soldiers')->default(0);
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
            $table->string('requirement_slug')->nullable();
            $table->integer('foundation_level_req')->default(0);
            $table->integer('stable_level_req')->default(0);
            $table->integer('armory_level_req')->default(0);
        });
    }

    public function testTrainFailsIfFoundationLevelTooLow(): void
    {
        Capsule::table('units')->insert([
            'slug' => 'elite',
            'name' => 'Elite',
            'description' => 'D',
            'cost_gold' => 10, 'cost_citizens' => 1, 'cost_turns' => 1,
            'power_offense' => 1, 'power_defense' => 1,
            'foundation_level_req' => 10
        ]);

        $user = User::create(['username' => 'u', 'email' => 'e', 'password' => 'p']);
        $kingdom = $user->kingdom()->create(['kingdom_name' => 'K', 'foundation_level' => 1]);

        $service = new TrainingService();
        $res = $service->train($kingdom->id, 'elite', 1);

        $this->assertFalse($res['success']);
        $this->assertEquals('Foundation level 10 required.', $res['message']);
    }

    public function testTrainFailsIfPrerequisiteUnitMissing(): void
    {
        Capsule::table('units')->insert([
            [
                'slug' => 'guards', 'name' => 'Guards', 'description' => 'D',
                'cost_gold' => 10, 'cost_citizens' => 1, 'cost_turns' => 1,
                'power_offense' => 1, 'power_defense' => 1,
                'requirement_slug' => null, 'foundation_level_req' => 0, 'stable_level_req' => 0, 'armory_level_req' => 0
            ],
            [
                'slug' => 'soldiers', 'name' => 'Soldiers', 'description' => 'D',
                'cost_gold' => 20, 'cost_citizens' => 1, 'cost_turns' => 1,
                'power_offense' => 1, 'power_defense' => 1,
                'requirement_slug' => 'guards', 'foundation_level_req' => 0, 'stable_level_req' => 0, 'armory_level_req' => 0
            ]
        ]);

        $user = User::create(['username' => 'u', 'email' => 'e', 'password' => 'p']);
        $kingdom = $user->kingdom()->create(['kingdom_name' => 'K']);

        $service = new TrainingService();
        
        // Should fail because no guards owned
        $res = $service->train($kingdom->id, 'soldiers', 1);
        $this->assertFalse($res['success']);
        $this->assertEquals('You must own at least one guards first.', $res['message']);

        // Give one guard
        $kingdom->unit_guards = 1;
        $kingdom->save();

        // Should now succeed
        $res = $service->train($kingdom->id, 'soldiers', 1);
        $this->assertTrue($res['success']);
    }
}
