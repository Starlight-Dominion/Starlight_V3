<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use sdo\Services\UntrainingService;
use Illuminate\Database\Capsule\Manager as Capsule;
use DateTime;

class UntrainingServiceTest extends TestCase
{
    private UntrainingService $service;

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
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->datetime('last_untrained')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->integer('cost_credits');
            $table->integer('cost_citizens');
            $table->integer('cost_turns');
            $table->integer('power_offense');
            $table->integer('power_defense');
            $table->integer('production_credits')->default(0);
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });

        Unit::create([
            'slug' => 'workers',
            'name' => 'Utility Workers',
            'cost_credits' => 25,
            'cost_citizens' => 1,
            'cost_turns' => 1,
            'power_offense' => 1,
            'power_defense' => 2
        ]);

        $this->service = new UntrainingService(
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Infrastructure\TransactionManager()
        );
    }

    private function createTestDominion(array $data = []): Dominion
    {
        $user = User::create([
            'username' => 'testuser' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        return $user->dominion()->create(array_merge([
            'name' => 'Test Dominion' . uniqid(),
            'credits' => 10000,
            'citizens' => 500,
            'turns' => 100,
            'xp' => 0,
        ], $data));
    }

    public function testCanUntrainCitizensNoRecord(): void
    {
        $dominion = $this->createTestDominion(['last_untrained' => null]);

        $result = $this->service->canUntrainCitizens($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['available_now']);
        $this->assertEquals(0, $result['cool_down']);
    }

    public function testCanUntrainCitizensCooldown(): void
    {
        $dominion = $this->createTestDominion([
            'last_untrained' => (new DateTime())->modify('-30 minutes')->format('Y-m-d H:i:s')
        ]);

        $result = $this->service->canUntrainCitizens($dominion->id);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['available_now']);
        $this->assertGreaterThan(0, $result['cool_down']);
    }

    public function testCanUntrainCitizensReady(): void
    {
        $dominion = $this->createTestDominion([
            'last_untrained' => (new DateTime())->modify('-2 hours')->format('Y-m-d H:i:s')
        ]);

        $result = $this->service->canUntrainCitizens($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['available_now']);
    }

    public function testGetHoldTimeRemaining(): void
    {
        $dominion = $this->createTestDominion([
            'last_untrained' => (new DateTime())->modify('-30 minutes')->format('Y-m-d H:i:s')
        ]);

        $result = $this->service->getHoldTimeRemaining($dominion->id);

        $this->assertNotNull($result);
        $this->assertGreaterThan(0, $result);
        $this->assertLessThanOrEqual(1800, $result);
    }

    public function testReleaseHeldCitizens(): void
    {
        $dominion = $this->createTestDominion([
            'held_citizens' => 10,
            'citizens' => 100
        ]);

        $result = $this->service->releaseHeldCitizens($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('10', $result['message']);
        $this->assertEquals(10, $result['released']);

        $dominion->refresh();
        $this->assertEquals(110, $dominion->citizens);
        $this->assertEquals(0, $dominion->held_citizens);
    }

    public function testReleaseHeldCitizensNone(): void
    {
        $dominion = $this->createTestDominion(['held_citizens' => 0]);

        $result = $this->service->releaseHeldCitizens($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('No citizens held', $result['message']);
    }

    public function testUntrainWorkers(): void
    {
        $dominion = $this->createTestDominion([
            'last_untrained' => null,
        ]);
        
        $workerUnit = Unit::where('slug', 'workers')->first();
        DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $workerUnit->id,
            'total_quantity' => 10
        ]);

        $result = $this->service->untrain($dominion->id, 'workers', 3);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('rewards', $result);
        $this->assertEquals(3, $result['untrained_count']);

        $dominion->refresh();
        $this->assertNotNull($dominion->last_untrained);
        
        $manpower = DominionManpower::where('dominion_id', $dominion->id)
            ->where('unit_id', $workerUnit->id)
            ->first();
        $this->assertEquals(7, $manpower->total_quantity);
    }

    public function testUntrainInvalidUnitType(): void
    {
        $dominion = $this->createTestDominion();
        $result = $this->service->untrain($dominion->id, 'dragons', 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid unit type', $result['message']);
    }

    public function testUntrainZeroQuantity(): void
    {
        $dominion = $this->createTestDominion();
        $workerUnit = Unit::where('slug', 'workers')->first();
        DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $workerUnit->id,
            'total_quantity' => 0
        ]);

        $result = $this->service->untrain($dominion->id, 'workers', 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No Utility Workers available', $result['message']);
    }

    public function testUntrainCooldownActive(): void
    {
        $dominion = $this->createTestDominion([
            'last_untrained' => (new DateTime())->modify('-30 minutes')->format('Y-m-d H:i:s')
        ]);
        
        $workerUnit = Unit::where('slug', 'workers')->first();
        DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $workerUnit->id,
            'total_quantity' => 10
        ]);

        $result = $this->service->untrain($dominion->id, 'workers', 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('on cooldown', $result['message']);
    }
}
