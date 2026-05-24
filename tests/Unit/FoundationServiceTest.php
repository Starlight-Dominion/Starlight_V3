<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\FoundationService;
use sdo\Services\LogService;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Models\DominionStructure;
use Illuminate\Database\Capsule\Manager as Capsule;

class FoundationServiceTest extends TestCase
{
    private FoundationService $service;
    private $logMock;

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

        $this->createSchema();
        $this->seedData();

        $this->logMock = $this->createMock(LogService::class);
        $this->service = new FoundationService($this->logMock);
    }

    private function createSchema(): void
    {
        $schema = Capsule::schema();
        
        $schema->create('users', function ($table) {
            $table->increments('id');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $schema->create('races', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $schema->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('race_id')->unsigned()->nullable();
            $table->string('name');
            $table->bigInteger('credits')->default(10000);
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->datetime('last_untrained')->nullable();
            $table->bigInteger('foundation_hp')->default(1000);
            $table->bigInteger('foundation_max_hp')->default(1000);
            $table->timestamps();
        });

        $schema->create('structures', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description');
            $table->integer('max_level');
        });

        $schema->create('structure_levels', function ($table) {
            $table->integer('structure_id')->unsigned();
            $table->integer('level');
            $table->integer('cost');
            $table->integer('buff_hp')->default(0);
            $table->integer('buff_unit_guards')->default(0);
            $table->integer('buff_unit_soldiers')->default(0);
            $table->integer('buff_unit_spies')->default(0);
            $table->integer('buff_unit_sentries')->default(0);
            $table->primary(['structure_id', 'level']);
        });

        $schema->create('dominion_structures', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->default(0);
            $table->string('mod_slot_1')->nullable();
            $table->primary(['dominion_id', 'structure_id']);
        });

        $schema->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
        });

        $schema->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });
    }

    private function seedData(): void
    {
        Structure::create(['id' => 1, 'slug' => 'foundation', 'name' => 'Foundation', 'description' => 'Core', 'max_level' => 20]);
        StructureLevel::create(['structure_id' => 1, 'level' => 1, 'cost' => 1000, 'buff_hp' => 1500]);
    }

    private function createTestDominion(): Dominion
    {
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        return $user->dominion()->create(['name' => 'D', 'credits' => 10000]);
    }

    public function testGetFoundationState(): void
    {
        $dominion = $this->createTestDominion();
        
        $state = $this->service->getFoundationState($dominion->id);

        $this->assertArrayHasKey('dominion', $state);
        $this->assertArrayHasKey('structures', $state);
        $this->assertArrayHasKey('foundation', $state['structures']);
        $this->assertEquals(0, $state['structures']['foundation']['current_level']);
    }

    public function testUpgradeFoundationSuccessfully(): void
    {
        $dominion = $this->createTestDominion();
        
        $result = $this->service->upgrade($dominion->id, 1);

        $this->assertTrue($result['success']);
        $dominion->refresh();
        $this->assertEquals(1500, $dominion->foundation_hp);
        $this->assertEquals(1500, $dominion->foundation_max_hp);
        $this->assertEquals(9000, $dominion->credits);
    }

    public function testRepairFoundation(): void
    {
        $dominion = $this->createTestDominion();
        $dominion->foundation_hp = 500;
        $dominion->save();

        $result = $this->service->repair($dominion->id);

        $this->assertTrue($result['success']);
        $dominion->refresh();
        $this->assertEquals(1000, $dominion->foundation_hp);
        $this->assertEquals(5000, $dominion->credits); // (1000-500) * 10 = 5000 cost
    }
}
