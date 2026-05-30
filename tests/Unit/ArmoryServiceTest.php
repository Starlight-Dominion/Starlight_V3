<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\ArmoryService;
use sdo\Services\LogService;
use sdo\Models\Dominion;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class ArmoryServiceTest extends TestCase
{
    private ArmoryService $armoryService;
    private $logMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Boot Eloquent with SQLite In-Memory
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // 2. Build Schema
        $this->createSchema();

        // 3. Populate Seed Data
        $this->seedData();

        $this->logMock = $this->createMock(LogService::class);
        $this->armoryService = new ArmoryService(
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentArmoryRepository(),
            new \sdo\Repositories\Eloquent\EloquentDominionArmoryRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentStructureRepository(),
            new \sdo\Infrastructure\TransactionManager(),
            $this->logMock ?? $logMock
        );
    }

    private function createSchema(): void
    {
        $schema = Capsule::schema();
        
        $schema->create('users', function($table) {
            $table->increments('id');
            $table->string('username');
            $table->string('avatar_path')->nullable();
        });

        $schema->create('dominions', function($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
            $table->integer('credits')->default(0);
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

        $schema->create('armory_unit_types', function($table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->string('title');
        });

        $schema->create('armory_categories', function($table) {
            $table->increments('id');
            $table->integer('unit_type_id');
            $table->string('slug');
            $table->string('name');
            $table->integer('slots')->default(1);
        });

        $schema->create('armory_items', function($table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->string('slug');
            $table->string('name');
            $table->string('unit_type');
            $table->integer('attack_bonus')->default(0);
            $table->integer('defense_bonus')->default(0);
            $table->integer('cost');
            $table->integer('armory_level_req')->default(0);
        });

        $schema->create('kingdom_armory_items', function($table) {
            $table->integer('kingdom_id');
            $table->integer('item_id');
            $table->integer('quantity')->default(0);
            $table->boolean('is_equipped')->default(false);
            $table->primary(['kingdom_id', 'item_id']);
        });

        $schema->create('units', function($table) {
            $table->increments('id');
            $table->string('slug');
        });

        $schema->create('dominion_manpower', function($table) {
            $table->integer('dominion_id');
            $table->integer('unit_id');
            $table->integer('total_quantity');
            $table->primary(['dominion_id', 'unit_id']);
        });

        $schema->create('structures', function($table) {
            $table->increments('id');
            $table->string('slug');
        });

        $schema->create('structure_levels', function($table) {
            $table->integer('structure_id');
            $table->integer('level');
            $table->integer('cost');
            $table->primary(['structure_id', 'level']);
        });
    }

    private function seedData(): void
    {
        Capsule::table('users')->insert(['id' => 1, 'username' => 'TestLord']);
        Capsule::table('dominions')->insert([
            'id' => 1, 
            'user_id' => 1, 
            'name' => 'Test Dominion', 
            'credits' => 1000000, 
            'armory_level' => 1
        ]);

        Capsule::table('units')->insert(['id' => 1, 'slug' => 'soldiers']);
        Capsule::table('dominion_manpower')->insert(['dominion_id' => 1, 'unit_id' => 1, 'total_quantity' => 1000]);

        Capsule::table('armory_unit_types')->insert(['id' => 1, 'slug' => 'soldiers', 'name' => 'Soldiers', 'title' => 'Soldier Offensive Loadout']);
        Capsule::table('armory_categories')->insert(['id' => 1, 'unit_type_id' => 1, 'slug' => 'main_weapon', 'name' => 'Heavy Main Weapons', 'slots' => 1]);
        
        Capsule::table('armory_items')->insert([
            ['id' => 1, 'category_id' => 1, 'slug' => 'pulse_rifle', 'name' => 'Pulse Rifle', 'unit_type' => 'soldiers', 'cost' => 80000, 'armory_level_req' => 0],
            ['id' => 2, 'category_id' => 1, 'slug' => 'railgun', 'name' => 'Railgun', 'unit_type' => 'soldiers', 'cost' => 120000, 'armory_level_req' => 2],
        ]);

        Capsule::table('structures')->insert(['id' => 3, 'slug' => 'armory']);
        Capsule::table('structure_levels')->insert(['structure_id' => 3, 'level' => 2, 'cost' => 210000]);
    }

    public function test_get_armory_data_retrieves_complete_structure(): void
    {
        $data = $this->armoryService->getArmoryData(1);

        $this->assertArrayHasKey('loadouts', $data);
        $this->assertArrayHasKey('soldiers', $data['loadouts']);
        $this->assertEquals(1000, $data['loadouts']['soldiers']['unit_count']);
        
        $categories = $data['loadouts']['soldiers']['categories'];
        $this->assertArrayHasKey('main_weapon', $categories);
        
        $items = $categories['main_weapon']['items'];
        $this->assertTrue($items['pulse_rifle']->unlocked);
        $this->assertFalse($items['railgun']->unlocked); // Level 1 < Req 2
    }

    public function test_buy_item_success_decrements_credits_and_increments_inventory(): void
    {
        $result = $this->armoryService->buyItem(1, 1, 10);

        $this->assertTrue($result['success']);
        
        $dom = Dominion::find(1);
        $this->assertEquals(1000000 - (80000 * 10), $dom->credits);

        $inv = Capsule::table('kingdom_armory_items')->where('kingdom_id', 1)->where('item_id', 1)->first();
        $this->assertEquals(10, $inv->quantity);
    }

    public function test_buy_item_fails_on_insufficient_credits(): void
    {
        $dom = Dominion::find(1);
        $dom->credits = 50000;
        $dom->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient credits.");
        
        $this->armoryService->buyItem(1, 1, 1);
    }

    public function test_buy_item_fails_on_unmet_tech_rank(): void
    {
        // Item ID 2 (Railgun) requires Level 2. Dominion is Level 1.
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Tech requirement not met.");
        
        $this->armoryService->buyItem(1, 2, 1);
    }

    public function test_sell_item_success_refunds_credits_and_decrements_inventory(): void
    {
        // Setup initial inventory
        Capsule::table('kingdom_armory_items')->insert(['kingdom_id' => 1, 'item_id' => 1, 'quantity' => 20]);
        
        $result = $this->armoryService->sellItem(1, 1, 10);

        $this->assertTrue($result['success']);
        
        $dom = Dominion::find(1);
        // Refund is 50% of 80000 = 40000 * 10 = 400000
        $this->assertEquals(1000000 + 400000, $dom->credits);

        $inv = Capsule::table('kingdom_armory_items')->where('kingdom_id', 1)->where('item_id', 1)->first();
        $this->assertEquals(10, $inv->quantity);
    }

    public function test_sell_item_fails_on_insufficient_stock(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient stock.");
        
        $this->armoryService->sellItem(1, 1, 1);
    }

    public function test_upgrade_armory_success_increases_level(): void
    {
        $result = $this->armoryService->upgradeArmory(1);

        $this->assertTrue($result['success']);
        
        $dom = Dominion::find(1);
        $this->assertEquals(2, $dom->armory_level);
        $this->assertEquals(1000000 - 210000, $dom->credits);
    }

    public function test_upgrade_armory_fails_at_max_level(): void
    {
        $dom = Dominion::find(1);
        $dom->armory_level = 2; // Level 3 doesn't exist in our seed
        $dom->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Maximum tech level reached.");
        
        $this->armoryService->upgradeArmory(1);
    }
}
