<?php

namespace Tests\Unit;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use sdo\Services\UpgradesService;
use Exception;

class UpgradesServiceTest extends TestCase
{
    private $pdoMock;
    private $upgradesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->upgradesService = new UpgradesService($this->pdoMock);
    }

    // Helper to mock PDOStatement for fetch (single row)
    private function mockPdoStatementForFetch($row)
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->any())
                 ->method('fetch')
                 ->willReturn($row);
        $stmtMock->expects($this->any())
                 ->method('execute')
                 ->willReturn(true);
        return $stmtMock;
    }

    public function testGetUpgradeDataReturnsCorrectData()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 100000,
            'housing_level' => 1,
            'mercenary_market_level' => 0,
        ];

        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $data = $this->upgradesService->getUpgradeData($kingdomId);

        $this->assertIsArray($data);
        $this->assertEquals($kingdomId, $data['kingdom']['id']);
        $this->assertArrayHasKey('housing_config', $data);
        $this->assertArrayHasKey('mercenary_market_config', $data);
    }

    public function testUpgradeHousingSuccessfully()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 100000,
            'housing_level' => 1,
            'mercenary_market_level' => 0,
        ];

        $this->pdoMock->expects($this->exactly(2)) // 1 for getUpgradeData, 1 for upgrade
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('commit');

        $result = $this->upgradesService->upgradeHousing($kingdomId);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Housing upgraded', $result['message']);
    }

    public function testUpgradeHousingFailsMaxLevel()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 100000,
            'housing_level' => 5, // Max level
            'mercenary_market_level' => 0,
        ];
        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->upgradesService->upgradeHousing($kingdomId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Housing is already at max level', $result['message']);
    }

    public function testUpgradeHousingFailsInsufficientGold()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 0, // Insufficient gold
            'housing_level' => 1,
            'mercenary_market_level' => 0,
        ];
        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->upgradesService->upgradeHousing($kingdomId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }

    public function testUpgradeMercenaryMarketSuccessfully()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 100000,
            'housing_level' => 1,
            'mercenary_market_level' => 0,
            'unit_guards' => 0,
            'unit_soldiers' => 0,
            'unit_spies' => 0,
            'unit_sentries' => 0,
        ];

        $this->pdoMock->expects($this->exactly(2)) // 1 for getUpgradeData, 1 for upgrade
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('commit');

        $result = $this->upgradesService->upgradeMercenaryMarket($kingdomId);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Mercenary Market upgraded', $result['message']);
    }

    public function testUpgradeMercenaryMarketFailsMaxLevel()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 100000,
            'housing_level' => 1,
            'mercenary_market_level' => 5, // Max level
        ];
        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->upgradesService->upgradeMercenaryMarket($kingdomId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Mercenary Market is already at max level', $result['message']);
    }

    public function testUpgradeMercenaryMarketFailsInsufficientGold()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 0, // Insufficient gold
            'housing_level' => 1,
            'mercenary_market_level' => 0,
        ];
        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->upgradesService->upgradeMercenaryMarket($kingdomId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }
}
