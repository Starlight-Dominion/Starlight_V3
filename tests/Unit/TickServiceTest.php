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
use sdo\Infrastructure\TransactionManager;
use sdo\Repositories\Eloquent\EloquentTickRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Predis\Client as Redis;

class TickServiceTest extends TestCase
{
    private TickService $service;
    private $configMock;
    private $redisMock;

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

        Capsule::schema()->create('game_settings', function ($table) { $table->string('setting_key')->unique(); $table->text('setting_value')->nullable(); });
        Capsule::schema()->create('races', function ($table) { $table->increments('id'); $table->string('name'); $table->string('slug'); $table->text('description')->nullable(); });
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

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->integer('production_credits')->default(0);
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });

        Capsule::schema()->create('tick_logs', function ($table) {
            $table->increments('id');
            $table->datetime('tick_time');
            $table->integer('total_sectors');
            $table->bigInteger('total_credits_granted');
            $table->integer('total_citizens_born');
            $table->integer('total_turns_granted');
            $table->float('execution_time_ms');
            $table->text('metadata')->nullable();
        });

        $this->configMock = $this->createMock(ConfigService::class);
        $this->configMock->method('get')->willReturnMap([
            ['baseline_citizens_per_tick', 50, 50],
            ['baseline_credits_per_tick', 100, 100],
        ]);

        $this->redisMock = $this->getMockBuilder(Redis::class)->disableOriginalConstructor()->getMock();

        $this->service = new TickService(
            $this->configMock ?? $this->createMock(\sdo\Services\ConfigService::class),
            $this->redisMock ?? $this->getMockBuilder(\Predis\Client::class)->disableOriginalConstructor()->getMock(),
            new \sdo\Repositories\Eloquent\EloquentTickRepository(),
            new \sdo\Infrastructure\TransactionManager()
        );
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

    public function testProcessTickJobUpdatesDominions(): void
    {
        $dominion = $this->createTestDominion();

        $this->service->processTickJob([$dominion->id], '2026-05-27 12:00:00', 'test-tick');

        $dominion->refresh();

        // Baseline: 100 credits, 50 citizens, 4 turns
        $this->assertEquals(100, $dominion->credits);
        $this->assertEquals(50, $dominion->citizens);
        $this->assertEquals(4, $dominion->turns);
        $this->assertEquals('2026-05-27 12:00:00', $dominion->last_tick->format('Y-m-d H:i:s'));
    }

    public function testProcessTickJobWithBuffs(): void
    {
        $dominion = $this->createTestDominion();

        $econStruct = Structure::create(['slug' => 'economy', 'name' => 'Economy']);
        StructureLevel::create(['structure_id' => $econStruct->id, 'level' => 1, 'buff_economy' => 20]);
        DominionStructure::create(['dominion_id' => $dominion->id, 'structure_id' => $econStruct->id, 'level' => 1]);

        $housingStruct = Structure::create(['slug' => 'housing', 'name' => 'Housing']);
        StructureLevel::create(['structure_id' => $housingStruct->id, 'level' => 1, 'buff_citizens_per_tick' => 25]);
        DominionStructure::create(['dominion_id' => $dominion->id, 'structure_id' => $housingStruct->id, 'level' => 1]);

        $this->service->processTickJob([$dominion->id], '2026-05-27 12:00:00', 'test-tick');

        $dominion->refresh();

        // Credits: 100 * (1 + 0.20) = 120
        // Citizens: 50 + 25 = 75
        $this->assertEquals(120, $dominion->credits);
        $this->assertEquals(75, $dominion->citizens);
    }

    public function testProcessTickJobWithUnitProduction(): void
    {
        $dominion = $this->createTestDominion();
        
        $workerUnit = \sdo\Models\Unit::create([
            'slug' => 'workers',
            'production_credits' => 10
        ]);

        \sdo\Models\DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $workerUnit->id,
            'total_quantity' => 5
        ]);

        $this->service->processTickJob([$dominion->id], '2026-05-27 12:00:00', 'test-tick');

        $dominion->refresh();

        // Baseline: 100
        // Unit Prod: 5 * 10 = 50
        // Total: 150
        $this->assertEquals(150, $dominion->credits);
    }

    public function testProcessTickJobBatchesCorrectly(): void
    {
        $ids = [];
        for ($i = 1; $i <= 150; $i++) {
            $ids[] = $this->createTestDominion()->id;
        }

        $this->service->processTickJob($ids, '2026-05-27 12:00:00', 'test-tick');

        $count = Dominion::where('turns', 4)->count();
        $this->assertEquals(150, $count);
    }

    public function testProcessTickJobEmptyDatabase(): void
    {
        $this->service->processTickJob([], '2026-05-27 12:00:00', 'test-tick');
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

        $this->service->processTickJob([$dominion->id], '2026-05-27 12:00:00', 'test-tick');

        $dominion->refresh();
        $this->assertEquals(204, $dominion->turns);
    }

    public function testProcessTickJobWithNegativeBuffs(): void
    {
        $dominion = $this->createTestDominion();

        $curseStruct = Structure::create(['slug' => 'curse', 'name' => 'Curse']);
        // Buff so negative it would normally result in negative gains
        StructureLevel::create(['structure_id' => $curseStruct->id, 'level' => 1, 'buff_economy' => -200, 'buff_citizens_per_tick' => -200]);
        DominionStructure::create(['dominion_id' => $dominion->id, 'structure_id' => $curseStruct->id, 'level' => 1]);

        $this->service->processTickJob([$dominion->id], '2026-05-27 12:00:00', 'test-tick');

        $dominion->refresh();

        // Should be bounded at 0, not subtract resources
        $this->assertEquals(0, $dominion->credits);
        $this->assertEquals(0, $dominion->citizens);
    }

    public function testProcessTickJobWithMissingBuffData(): void
    {
        $dominion = $this->createTestDominion();
        
        // Structure exists but no level record
        $ghostStruct = Structure::create(['slug' => 'ghost', 'name' => 'Ghost']);
        DominionStructure::create(['dominion_id' => $dominion->id, 'structure_id' => $ghostStruct->id, 'level' => 5]);

        $this->service->processTickJob([$dominion->id], '2026-05-27 12:00:00', 'test-tick');

        $dominion->refresh();

        // Should still get baseline resources
        $this->assertEquals(100, $dominion->credits);
        $this->assertEquals(50, $dominion->citizens);
    }

    public function testDispatchTickJobsPushesToRedis(): void
    {
        $this->createTestDominion();
        $this->createTestDominion();

        // Expect 1 XADD call (since batch size is 100 and we only have 2)
        $this->redisMock->expects($this->once())
            ->method('executeRaw')
            ->with($this->callback(function($args) {
                return $args[0] === 'XADD' && $args[1] === TickService::STREAM_KEY;
            }));

        $this->service->dispatchTickJobs();

        $log = \sdo\Models\TickLog::first();
        $this->assertNotNull($log);
        $this->assertEquals(2, $log->total_sectors);
        $this->assertEquals('dispatched', $log->metadata['status']);
    }
}
