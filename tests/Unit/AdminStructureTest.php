<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminGameDataService;
use sdo\Repositories\Eloquent\EloquentUnitRepository;
use sdo\Repositories\Eloquent\EloquentStructureRepository;
use sdo\Repositories\Eloquent\EloquentArmoryRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminStructureTest extends TestCase
{
    private AdminGameDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->createTables();
        
        $this->service = new AdminGameDataService(
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentStructureRepository(),
            new \sdo\Repositories\Eloquent\EloquentArmoryRepository(),
            new \sdo\Repositories\Eloquent\EloquentRaceRepository()
        );
    }

    private function createTables(): void
    {
        Capsule::schema()->create('structures', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('upgrade_slots')->default(1);
            $table->integer('max_level')->default(10);
            $table->string('dependency_slug')->nullable();
            $table->integer('dependency_multiplier')->default(1);
        });

        Capsule::schema()->create('structure_levels', function ($table) {
            $table->integer('structure_id');
            $table->integer('level');
            $table->bigInteger('cost');
            $table->string('buff_name')->nullable();
            $table->bigInteger('buff_hp')->default(0);
            $table->integer('buff_offense')->default(0);
            $table->integer('buff_defense')->default(0);
            $table->integer('buff_spy_offense')->default(0);
            $table->integer('buff_spy_defense')->default(0);
            $table->integer('buff_economy')->default(0);
            $table->integer('buff_charisma')->default(0);
            $table->integer('player_level_req')->default(0);
            $table->integer('capacity')->nullable();
            $table->primary(['structure_id', 'level']);
        });
        
        // Mock units/armory tables
        Capsule::schema()->create('units', function($table) { $table->increments('id'); });
        Capsule::schema()->create('armory_items', function($table) { $table->increments('id'); });
    }

    public function testAddAndFetchStructure(): void
    {
        $sId = $this->service->addStructure([
            'slug' => 'test_bldg',
            'name' => 'Test Building',
            'description' => 'Desc'
        ]);

        $this->assertNotNull($sId);
        
        $all = $this->service->getAllStructures();
        $this->assertCount(1, $all);
        $this->assertEquals('test_bldg', $all[0]['slug']);
    }

    public function testUpdateStructureLevelBuffs(): void
    {
        $sId = Capsule::table('structures')->insertGetId(['slug' => 'f', 'name' => 'F', 'description' => 'D']);
        
        Capsule::table('structure_levels')->insert([
            'structure_id' => $sId,
            'level' => 1,
            'cost' => 1000,
            'buff_offense' => 0
        ]);

        $res = $this->service->updateStructureLevel((int)$sId, 1, ['buff_offense' => 50, 'cost' => 5000]);
        $this->assertTrue($res);

        $level = Capsule::table('structure_levels')->where('structure_id', $sId)->where('level', 1)->first();
        $this->assertEquals(50, $level->buff_offense);
        $this->assertEquals(5000, $level->cost);
    }
}
