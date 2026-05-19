<?php

namespace Tests\Unit;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use sdo\Services\MinesService;
use sdo\Services\UntrainingService;

class MinesServiceTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;
    private MinesService $service;
    private $untrainingMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->untrainingMock = $this->createMock(UntrainingService::class);
        $this->service = new MinesService($this->pdoMock, $this->untrainingMock);
    }

    private function mockPdoStatementForFetch($row)
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn($row);
        $stmtMock->method('execute')->willReturn(true);
        return $stmtMock;
    }

    public function testAssignMinersSuccess(): void
    {
        $kingdom = [
            'gold' => 1000,
            'citizens' => 50,
            'turns' => 100,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        $result = $this->service->assignMiners(1, 3);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Assigned 3', $result['message']);
    }

    public function testAssignMinersInsufficientGold(): void
    {
        $kingdom = [
            'gold' => 500, // Need 200*3=600
            'citizens' => 50,
            'turns' => 100,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->assignMiners(1, 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }

    public function testAssignMinersInsufficientCitizens(): void
    {
        $kingdom = [
            'gold' => 1000,
            'citizens' => 2, // Need 3 citizens
            'turns' => 100,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->assignMiners(1, 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient citizens', $result['message']);
    }

    public function testAssignMinersInsufficientTurns(): void
    {
        $kingdom = [
            'gold' => 1000,
            'citizens' => 50,
            'turns' => 2, // Need 3 turns
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->assignMiners(1, 3);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient turns', $result['message']);
    }

    public function testAssignMinersZeroQuantity(): void
    {
        $result = $this->service->assignMiners(1, 0);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('must be positive', $result['message']);
    }

    public function testAssignMinersNegativeQuantity(): void
    {
        $result = $this->service->assignMiners(1, -1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('must be positive', $result['message']);
    }

    public function testUnassignMiners(): void
    {
        $this->untrainingMock->expects($this->once())
            ->method('untrain')
            ->with(1, 'miners', 3)
            ->willReturn(['success' => true, 'message' => 'Untrained']);

        $result = $this->service->unassignMiners(1, 3);

        $this->assertTrue($result['success']);
    }

    public function testUpgradeMineTierSuccess(): void
    {
        $kingdom = [
            'id' => 1,
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'xp' => 10000, // Player level 11
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        $result = $this->service->upgradeMineTier(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Upgraded to Mine Tier 2', $result['message']);
    }

    public function testUpgradeMineTierMaxTier(): void
    {
        $kingdom = [
            'current_mine_tier' => 10,
            'current_mine_level' => 150,
            'xp' => 100000,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->upgradeMineTier(1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('highest mine tier', $result['message']);
    }

    public function testUpgradeMineTierLevelRequirement(): void
    {
        $kingdom = [
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'xp' => 0, // Player level 1, need level 5 for tier 2
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->upgradeMineTier(1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('player level', $result['message']);
    }

    public function testUpgradeCurrentMineSuccess(): void
    {
        $kingdom = [
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'gold' => 2000,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        $result = $this->service->upgradeCurrentMine(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('level 2', $result['message']);
    }

    public function testUpgradeCurrentMineMaxLevel(): void
    {
        $kingdom = [
            'current_mine_tier' => 1,
            'current_mine_level' => 150,
            'gold' => 50000,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->upgradeCurrentMine(1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('max level', $result['message']);
    }

    public function testUpgradeCurrentMineInsufficientGold(): void
    {
        $kingdom = [
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'gold' => 500,
        ];

        $this->pdoMock->method('prepare')->willReturn($this->mockPdoStatementForFetch($kingdom));
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->upgradeCurrentMine(1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }

    public function testCalculateCurrentProduction(): void
    {
        $kingdom = [
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'miners' => 10,
        ];

        $result = $this->service->calculateCurrentProduction($kingdom);

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testCalculateCurrentProductionZeroMiners(): void
    {
        $kingdom = [
            'current_mine_tier' => 1,
            'current_mine_level' => 1,
            'miners' => 0,
        ];

        $result = $this->service->calculateCurrentProduction($kingdom);

        $this->assertEquals(0, $result);
    }

    public function testGetMinesConfig(): void
    {
        $config = $this->service->getMinesConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('unlocks', $config);
        $this->assertArrayHasKey('mines', $config);
        $this->assertArrayHasKey('base_gold_per_tick', $config);
    }
}
