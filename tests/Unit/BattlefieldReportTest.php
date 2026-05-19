<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\BattlefieldService;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Mockery;

class BattlefieldReportTest extends TestCase
{
    private $combatRepo;
    private $kingdomRepo;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->combatRepo = Mockery::mock(CombatRepositoryInterface::class);
        $this->kingdomRepo = Mockery::mock(KingdomRepositoryInterface::class);
        $this->service = new BattlefieldService($this->combatRepo, $this->kingdomRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetBattleLogDecodesUnits(): void
    {
        $logId = 123;
        $mockLog = (object)[
            'id' => $logId,
            'attacker_units' => json_encode(['soldiers' => 10, 'guards' => 5]),
            'defender_units' => json_encode(['soldiers' => 2, 'guards' => 20]),
            'result' => 'attacker',
            'attacker_loss_percent' => 10,
            'defender_loss_percent' => 50,
            'gold_looted' => 1000,
            'turns_spent' => 3
        ];

        $this->combatRepo->shouldReceive('findLogById')
            ->with($logId)
            ->once()
            ->andReturn($mockLog);

        $result = $this->service->getBattleLog($logId);

        $this->assertIsArray($result->attacker_units);
        $this->assertEquals(10, $result->attacker_units['soldiers']);
        $this->assertIsArray($result->defender_units);
        $this->assertEquals(20, $result->defender_units['guards']);
    }

    public function testGetBattleLogReturnsNullIfNotFound(): void
    {
        $this->combatRepo->shouldReceive('findLogById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->service->getBattleLog(999);

        $this->assertNull($result);
    }
}

class TacticalServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Since TacticalService uses Kingdom::findOrFail, we'd need a real DB or a very complex mock
        // For now, I'll mark this as a reminder to implement integration tests.
    }

    public function testCalculatePowerIncludesEquipment(): void
    {
        $this->markTestSkipped('Requires database integration for Eloquent models.');
    }
}