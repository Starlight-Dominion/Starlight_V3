<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Models\DominionStructure;
use sdo\Services\TickService;
use sdo\Services\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

class TickServiceTest extends TestCase
{
    private TickService $service;
    private $configMock;

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

        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(0);
            $table->integer('citizens')->default(0);
            $table->integer('turns')->default(0);
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->datetime('last_untrained')->nullable();
            $table->datetime('last_tick')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('structures', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
        });

        Capsule::schema()->create('structure_levels', function ($table) {
            $table->integer('structure_id')->unsigned();
            $table->integer('level');
            $table->integer('buff_economy')->default(0);
            $table->integer('buff_citizens_per_tick')->default(0);
            $table->primary(['structure_id', 'level']);
        });

        Capsule::schema()->create('dominion_structures', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->default(0);
            $table->primary(['dominion_id', 'structure_id']);
        });

        $this->configMock = $this->createMock(ConfigService::class);
        $this->configMock->method('get')->willReturnMap([
            ['baseline_citizens_per_tick', 50, 50],
            ['baseline_credits_per_tick', 100, 100],
        ]);

        $this->service = new TickService($this->configMock);
    }

    private function createTestDominion(): Dominion
    {
        $user = User::create([
            'username' => 'testuser' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        return $user->dominion()->create([
            'name' => 'Test Dominion' . uniqid(),
            'credits' => 0,
            'citizens' => 0,
            'turns' => 0,
        ]);
    }

    public function testProcessGlobalTickUpdatesDominions(): void
    {
        $dominion = $this->createTestDominion();

        $this->service->processGlobalTick();

        $dominion->refresh();

        // Baseline: 100 credits, 50 citizens, 4 turns
        $this->assertEquals(100, $dominion->credits);
        $this->assertEquals(50, $dominion->citizens);
        $this->assertEquals(4, $dominion->turns);
        $this->assertNotNull($dominion->last_tick);
    }

    public function testProcessGlobalTickWithBuffs(): void
    {
        $dominion = $this->createTestDominion();

        $econStruct = Structure::create(['slug' => 'economy', 'name' => 'Economy']);
        StructureLevel::create(['structure_id' => $econStruct->id, 'level' => 1, 'buff_economy' => 20]);
        DominionStructure::create(['dominion_id' => $dominion->id, 'structure_id' => $econStruct->id, 'level' => 1]);

        $housingStruct = Structure::create(['slug' => 'housing', 'name' => 'Housing']);
        StructureLevel::create(['structure_id' => $housingStruct->id, 'level' => 1, 'buff_citizens_per_tick' => 25]);
        DominionStructure::create(['dominion_id' => $dominion->id, 'structure_id' => $housingStruct->id, 'level' => 1]);

        $this->service->processGlobalTick();

        $dominion->refresh();

        // Credits: 100 * (1 + 0.20) = 120
        // Citizens: 50 + 25 = 75
        $this->assertEquals(120, $dominion->credits);
        $this->assertEquals(75, $dominion->citizens);
    }

    public function testProcessGlobalTickBatchesCorrectly(): void
    {
        for ($i = 1; $i <= 150; $i++) {
            $this->createTestDominion();
        }

        $this->service->processGlobalTick();

        $count = Dominion::where('turns', 4)->count();
        $this->assertEquals(150, $count);
    }

    public function testProcessGlobalTickEmptyDatabase(): void
    {
        $this->service->processGlobalTick();
        $this->assertEquals(0, Dominion::count());
    }

    public function testTurnsCanAccumulateBeyondCap(): void
    {
        $user = User::create([
            'username' => 'capbreaker',
            'email' => 'cap@example.com',
            'password' => 'password123',
        ]);

        $dominion = $user->dominion()->create([
            'name' => 'Cap Breaker',
            'turns' => 200,
        ]);

        $this->service->processGlobalTick();

        $dominion->refresh();
        $this->assertEquals(204, $dominion->turns);
    }
}
