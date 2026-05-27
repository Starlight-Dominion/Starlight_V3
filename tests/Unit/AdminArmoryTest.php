<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminGameDataService;
use sdo\Repositories\Eloquent\EloquentUnitRepository;
use sdo\Repositories\Eloquent\EloquentStructureRepository;
use sdo\Repositories\Eloquent\EloquentArmoryRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminArmoryTest extends TestCase
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
            new EloquentUnitRepository(),
            new EloquentStructureRepository(),
            new EloquentArmoryRepository()
        );
    }

    private function createTables(): void
    {
        Capsule::schema()->create('armory_unit_types', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('title');
        });

        Capsule::schema()->create('armory_categories', function ($table) {
            $table->increments('id');
            $table->integer('unit_type_id')->unsigned();
            $table->string('slug')->unique();
            $table->string('name');
            $table->integer('slots')->default(1);
        });

        Capsule::schema()->create('armory_items', function ($table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('unit_type');
            $table->integer('cost');
            $table->string('requirement_slug')->nullable();
            $table->integer('armory_level_req')->default(0);
            $table->integer('attack_bonus')->default(0);
            $table->integer('defense_bonus')->default(0);
        });
        
        // Mock units/structures tables since service expects them
        Capsule::schema()->create('units', function($table) { $table->increments('id'); });
        Capsule::schema()->create('structures', function($table) { $table->increments('id'); });
    }

    public function testAddAndDeleteArmoryItem(): void
    {
        // 1. Create dependencies
        $utId = Capsule::table('armory_unit_types')->insertGetId(['slug' => 's', 'name' => 'S', 'title' => 'T']);
        $catId = Capsule::table('armory_categories')->insertGetId(['unit_type_id' => $utId, 'slug' => 'c', 'name' => 'C']);

        // 2. Add Item
        $itemId = $this->service->addArmoryItem([
            'category_id' => $catId,
            'slug' => 'test_sword',
            'name' => 'Test Sword',
            'cost' => 500,
            'unit_type' => 'soldiers'
        ]);

        $this->assertNotNull($itemId);
        $this->assertEquals(1, Capsule::table('armory_items')->count());

        // 3. Delete Item
        $res = $this->service->deleteArmoryItem($itemId);
        $this->assertTrue($res);
        $this->assertEquals(0, Capsule::table('armory_items')->count());
    }

    public function testUpdateArmoryItemStats(): void
    {
        $itemId = Capsule::table('armory_items')->insertGetId([
            'category_id' => 1, 'slug' => 'sw', 'name' => 'Sword', 'cost' => 100, 'unit_type' => 's'
        ]);

        $res = $this->service->updateArmoryItem($itemId, ['attack_bonus' => 50, 'cost' => 999]);
        
        $this->assertTrue($res);
        $item = Capsule::table('armory_items')->where('id', $itemId)->first();
        $this->assertEquals(50, $item->attack_bonus);
        $this->assertEquals(999, $item->cost);
    }
}
