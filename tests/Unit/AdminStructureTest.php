<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminService;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminStructureTest extends TestCase
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
    }

    public function testAddAndFetchStructure(): void
    {
        $adminService = new AdminService();
        $sId = $adminService->addStructure([
            'slug' => 'test_bldg',
            'name' => 'Test Building',
            'description' => 'Desc'
        ]);

        $this->assertNotNull($sId);
        
        $all = $adminService->getAllStructures();
        $this->assertCount(1, $all);
        $this->assertEquals('test_bldg', $all[0]['slug']);
    }

    public function testUpdateStructureLevelBuffs(): void
    {
        $adminService = new AdminService();
        $sId = Capsule::table('structures')->insertGetId(['slug' => 'f', 'name' => 'F', 'description' => 'D']);
        
        Capsule::table('structure_levels')->insert([
            'structure_id' => $sId,
            'level' => 1,
            'cost' => 1000,
            'buff_offense' => 0
        ]);

        $res = $adminService->updateStructureLevel((int)$sId, 1, ['buff_offense' => 50, 'cost' => 5000]);
        $this->assertTrue($res);

        $level = Capsule::table('structure_levels')->where('structure_id', $sId)->where('level', 1)->first();
        $this->assertEquals(50, $level->buff_offense);
        $this->assertEquals(5000, $level->cost);
    }
}
