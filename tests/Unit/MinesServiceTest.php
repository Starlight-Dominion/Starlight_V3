<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use sdo\Services\MinesService;
use sdo\Services\UntrainingService;
use Illuminate\Database\Capsule\Manager as Capsule;

class MinesServiceTest extends TestCase
{
    private MinesService $service;
    private $untrainingMock;

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
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(1);
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

        $this->untrainingMock = $this->createMock(UntrainingService::class);
        $this->service = new MinesService($this->untrainingMock);
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

    public function testAssignMinersSuccess(): void
    {
        $dominion = $this->createTestDominion([
            'credits' => 1000,
            'citizens' => 50,
            'turns' => 100,
        ]);

        $result = $this->service->assignMiners($dominion->id, 3);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Assigned 3', $result['message']);

        $dominion->refresh();
        $this->assertEquals(400, $dominion->credits); // 1000 - (200 * 3)
        
        $workerUnit = Unit::where('slug', 'workers')->first();
        $manpower = DominionManpower::where('dominion_id', $dominion->id)
            ->where('unit_id', $workerUnit->id)
            ->first();
        $this->assertEquals(3, $manpower->total_quantity);
    }

    public function testAssignMinersInsufficientCredits(): void
    {
        $dominion = $this->createTestDominion([
            'credits' => 500, // Need 200*3=600
            'citizens' => 50,
            'turns' => 100,
        ]);

        $result = $this->service->assignMiners($dominion->id, 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient credits', $result['message']);
    }

    public function testAssignMinersInsufficientCitizens(): void
    {
        $dominion = $this->createTestDominion([
            'credits' => 1000,
            'citizens' => 2, // Need 3 citizens
            'turns' => 100,
        ]);

        $result = $this->service->assignMiners($dominion->id, 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient citizens', $result['message']);
    }

    public function testAssignMinersInsufficientTurns(): void
    {
        $dominion = $this->createTestDominion([
            'credits' => 1000,
            'citizens' => 50,
            'turns' => 2, // Need 3 turns
        ]);

        $result = $this->service->assignMiners($dominion->id, 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient strike capacity', $result['message']);
    }

    public function testUnassignMiners(): void
    {
        $dominion = $this->createTestDominion();
        
        $this->untrainingMock->expects($this->once())
            ->method('untrain')
            ->with($dominion->id, 'workers', 3)
            ->willReturn(['success' => true, 'message' => 'Untrained']);

        $result = $this->service->unassignMiners($dominion->id, 3);

        $this->assertTrue($result['success']);
    }

    public function testUpgradeMineTierSuccess(): void
    {
        $dominion = $this->createTestDominion([
            'current_mine_tier' => 1,
            'current_mine_level' => 10,
            'xp' => 10000, // Player level 11
        ]);

        $result = $this->service->upgradeMineTier($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Upgraded to Extraction Tier 2', $result['message']);

        $dominion->refresh();
        $this->assertEquals(2, $dominion->current_mine_tier);
        $this->assertEquals(5, $dominion->current_mine_level); // 10 / 2
    }

    public function testUpgradeMineTierMaxTier(): void
    {
        $dominion = $this->createTestDominion([
            'current_mine_tier' => 10,
            'xp' => 100000,
        ]);

        $result = $this->service->upgradeMineTier($dominion->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Maximum extraction depth', $result['message']);
    }

    public function testUpgradeMineTierLevelRequirement(): void
    {
        $dominion = $this->createTestDominion([
            'current_mine_tier' => 1,
            'xp' => 0, // Player level 1, need level 5 for tier 2
        ]);

        $result = $this->service->upgradeMineTier($dominion->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Commander level', $result['message']);
    }

    public function testUpgradeCurrentMineSuccess(): void
    {
        $dominion = $this->createTestDominion([
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'credits' => 5000,
        ]);

        $result = $this->service->upgradeCurrentMine($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Level 2', $result['message']);

        $dominion->refresh();
        $this->assertEquals(2, $dominion->current_mine_level);
        $this->assertLessThan(5000, $dominion->credits);
    }

    public function testCalculateCurrentProduction(): void
    {
        $dominion = $this->createTestDominion([
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
        ]);
        
        $workerUnit = Unit::where('slug', 'workers')->first();
        DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $workerUnit->id,
            'total_quantity' => 10
        ]);

        $result = $this->service->calculateCurrentProduction($dominion);

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }
}
