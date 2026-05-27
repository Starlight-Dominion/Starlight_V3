<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Services\AdminPlayerService;
use sdo\Repositories\Eloquent\EloquentUserRepository;
use sdo\Repositories\Eloquent\EloquentDominionRepository;
use sdo\Repositories\Eloquent\EloquentUnitRepository;
use sdo\Repositories\Eloquent\EloquentStructureRepository;
use sdo\Repositories\Eloquent\EloquentArmoryRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminInspectorTest extends TestCase
{
    private AdminPlayerService $service;
    private int $testDomId;

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

        $this->service = new AdminPlayerService(
            new EloquentDominionRepository(),
            new EloquentUserRepository(),
            new EloquentUnitRepository(),
            new EloquentStructureRepository(),
            new EloquentArmoryRepository()
        );
        
        // Setup a test player
        $user = User::create([
            'username' => 'test_inspector_' . time(),
            'email' => 'inspector@test.com',
            'password' => 'secret'
        ]);

        $dominion = Dominion::create([
            'user_id' => $user->id,
            'name' => 'Test Sector',
            'race_id' => 1,
            'credits' => 5000,
            'citizens' => 100
        ]);

        Unit::create(['name' => 'Soldier', 'slug' => 'soldier']);

        $this->testDomId = (int)$dominion->id;
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
            $table->dateTime('stasis_until')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->integer('race_id')->unsigned();
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('strength_points')->default(0);
            $table->timestamps();
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->integer('stabled_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });

        Capsule::schema()->create('armory_items', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
        });

        Capsule::schema()->create('kingdom_armory_items', function ($table) {
            $table->integer('kingdom_id')->unsigned();
            $table->integer('item_id')->unsigned();
            $table->integer('quantity')->default(0);
            $table->boolean('is_equipped')->default(false);
        });

        Capsule::schema()->create('structures', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
        });

        Capsule::schema()->create('dominion_structures', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->default(0);
            $table->primary(['dominion_id', 'structure_id']);
        });

        Capsule::schema()->create('races', function ($table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    public function testGetKingdomFullProfile(): void
    {
        $profile = $this->service->getKingdomFullProfile($this->testDomId);
        
        $this->assertArrayHasKey('dominion', $profile);
        $this->assertArrayHasKey('manpower', $profile['dominion']);
        $this->assertArrayHasKey('all_units', $profile);
        $this->assertEquals('Test Sector', $profile['dominion']['name']);
    }

    public function testSchemaSafeUpdate(): void
    {
        $newData = [
            'credits' => 99999,
            'email' => 'new_email@test.com',
            'is_bot' => true,
            'non_existent_column' => 'garbage'
        ];

        $res = $this->service->updateDominionStats($this->testDomId, $newData);
        $this->assertTrue($res);

        $updatedDom = Dominion::with('user')->find($this->testDomId);
        $this->assertEquals(99999, $updatedDom->credits);
        $this->assertEquals('new_email@test.com', $updatedDom->user->email);
        $this->assertTrue($updatedDom->user->is_bot);
    }

    public function testUpdateManpower(): void
    {
        $unit = Unit::first();
        if (!$unit) $this->markTestSkipped('No units in DB');

        $res = $this->service->updateKingdomManpower($this->testDomId, (int)$unit->id, 500, 100);
        $this->assertTrue($res);

        $manpower = \sdo\Models\DominionManpower::where('dominion_id', $this->testDomId)
            ->where('unit_id', $unit->id)
            ->first();

        $this->assertEquals(500, $manpower->total_quantity);
        $this->assertEquals(100, $manpower->stabled_quantity);
    }
}
