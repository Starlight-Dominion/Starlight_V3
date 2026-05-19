<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\ArmoryService;
use sdo\Models\Kingdom;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class ArmoryDynamicTest extends TestCase
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
            $table->integer('gold')->default(1000);
            $table->integer('citizens')->default(50);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('foundation_level')->default(1);
            $table->integer('armory_level')->default(1);
            $table->integer('unit_soldiers')->default(0);
            $table->timestamps();
        });

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

        Capsule::schema()->create('kingdom_armory_items', function ($table) {
            $table->integer('kingdom_id');
            $table->integer('item_id');
            $table->integer('quantity')->default(0);
            $table->primary(['kingdom_id', 'item_id']);
        });

        Capsule::schema()->create('hidden_armory_items', function ($table) {
            $table->integer('kingdom_id');
            $table->integer('item_id');
            $table->primary(['kingdom_id', 'item_id']);
        });

        Capsule::schema()->create('armory_upgrades', function ($table) {
            $table->integer('level')->primary();
            $table->bigInteger('cost');
        });
    }

    public function testGetArmoryDataAssemblesCorrectStructure(): void
    {
        // 1. Seed Dynamic Config
        $utId = Capsule::table('armory_unit_types')->insertGetId(['slug' => 'soldiers', 'name' => 'Soldiers', 'title' => 'Title']);
        $catId = Capsule::table('armory_categories')->insertGetId(['unit_type_id' => $utId, 'slug' => 'weapon', 'name' => 'Weapon']);
        Capsule::table('armory_items')->insert([
            'category_id' => $catId, 'slug' => 'sword', 'name' => 'Sword', 'cost' => 100, 'unit_type' => 'soldiers'
        ]);

        // 2. Setup User
        $user = User::create(['username' => 'u', 'email' => 'e', 'password' => 'p']);
        $kingdom = $user->kingdom()->create(['kingdom_name' => 'K', 'armory_level' => 1]);

        $service = new ArmoryService();
        $data = $service->getArmoryData($kingdom->id);

        $this->assertArrayHasKey('soldiers', $data['loadouts']);
        $this->assertArrayHasKey('weapon', $data['loadouts']['soldiers']['categories']);
        $this->assertArrayHasKey('sword', $data['loadouts']['soldiers']['categories']['weapon']['items']);
        $this->assertEquals('Sword', $data['loadouts']['soldiers']['categories']['weapon']['items']['sword']['name']);
    }

    public function testUnlockLogicWithDynamicPrerequisites(): void
    {
        $utId = Capsule::table('armory_unit_types')->insertGetId(['slug' => 'soldiers', 'name' => 'S', 'title' => 'T']);
        $catId = Capsule::table('armory_categories')->insertGetId(['unit_type_id' => $utId, 'slug' => 'w', 'name' => 'W']);
        
        $pId = Capsule::table('armory_items')->insertGetId([
            'category_id' => $catId, 'slug' => 'pre', 'name' => 'Prereq', 'cost' => 10, 'unit_type' => 'soldiers'
        ]);
        Capsule::table('armory_items')->insert([
            'category_id' => $catId, 'slug' => 'adv', 'name' => 'Advanced', 'cost' => 50, 'unit_type' => 'soldiers', 'requirement_slug' => 'pre'
        ]);

        $user = User::create(['username' => 'u', 'email' => 'e', 'password' => 'p']);
        $kingdom = $user->kingdom()->create(['kingdom_name' => 'K', 'armory_level' => 1]);

        $service = new ArmoryService();
        
        // Initially, Advanced should be locked
        $data = $service->getArmoryData($kingdom->id);
        $this->assertFalse($data['loadouts']['soldiers']['categories']['w']['items']['adv']['unlocked']);

        // Give player the prerequisite
        Capsule::table('kingdom_armory_items')->insert(['kingdom_id' => $kingdom->id, 'item_id' => $pId, 'quantity' => 1]);

        // Now, Advanced should be unlocked
        $data = $service->getArmoryData($kingdom->id);
        $this->assertTrue($data['loadouts']['soldiers']['categories']['w']['items']['adv']['unlocked']);
    }
}
