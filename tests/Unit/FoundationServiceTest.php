<?php

namespace Tests\Unit;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use sdo\Services\FoundationService;
use Exception;

class FoundationServiceTest extends TestCase
{
    private $pdoMock;
    private $foundationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->foundationService = new FoundationService($this->pdoMock);
    }

    // Helper to mock PDOStatement for fetchColumn
    private function mockPdoStatementForFetchColumn($value)
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->any())
                 ->method('fetchColumn')
                 ->willReturn($value);
        $stmtMock->expects($this->any())
                 ->method('execute')
                 ->willReturn(true);
        return $stmtMock;
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

    public function testGetFoundationDataReturnsCorrectData()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 50000,
            'foundation_level' => 1,
            'foundation_hp' => 100,
            'foundation_upgrade_slot_1' => null,
        ];

        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $data = $this->foundationService->getFoundationData($kingdomId);

        $this->assertIsArray($data);
        $this->assertEquals($kingdomId, $data['kingdom']['id']);
        $this->assertArrayHasKey('current_tier', $data);
        $this->assertArrayHasKey('next_level_cost', $data);
        $this->assertArrayHasKey('upgrades', $data);
        $this->assertArrayHasKey('level_costs', $data);
        $this->assertEquals('Wood', $data['current_tier']['name']);
    }

    public function testUpgradeFoundationSuccessfully()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 50000,
            'foundation_level' => 1,
            'foundation_hp' => 100,
            'foundation_upgrade_slot_1' => null,
        ];

        $this->pdoMock->expects($this->exactly(2)) // 1 for getFoundationData, 1 for upgrade
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('commit');

        $result = $this->foundationService->upgradeFoundation($kingdomId);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Foundation upgraded', $result['message']);
    }

    public function testUpgradeFoundationFailsInsufficientGold()
    {
        $kingdomId = 1;
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 0, // Insufficient gold
            'foundation_level' => 1,
            'foundation_hp' => 100,
            'foundation_upgrade_slot_1' => null,
        ];
        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->foundationService->upgradeFoundation($kingdomId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }

    public function testPurchaseUpgradeSuccessfully()
    {
        $kingdomId = 1;
        $upgradeKey = 'moat';
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 50000,
            'foundation_level' => 1,
            'foundation_hp' => 100,
            'foundation_upgrade_slot_1' => null,
        ];

        $this->pdoMock->expects($this->exactly(2)) // 1 for getFoundationData, 1 for purchase
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('commit');

        $result = $this->foundationService->purchaseUpgrade($kingdomId, $upgradeKey);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Successfully purchased', $result['message']);
    }

    public function testPurchaseUpgradeFailsSlotFilled()
    {
        $kingdomId = 1;
        $upgradeKey = 'moat';
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 50000,
            'foundation_level' => 1,
            'foundation_hp' => 100,
            'foundation_upgrade_slot_1' => 'moat', // Slot already filled
        ];

        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->foundationService->purchaseUpgrade($kingdomId, $upgradeKey);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Upgrade slot is already filled', $result['message']);
    }

    public function testPurchaseUpgradeFailsInsufficientGold()
    {
        $kingdomId = 1;
        $upgradeKey = 'moat';
        $mockKingdom = [
            'id' => $kingdomId,
            'gold' => 0, // Insufficient gold
            'foundation_level' => 1,
            'foundation_hp' => 100,
            'foundation_upgrade_slot_1' => null,
        ];
        $this->pdoMock->expects($this->once())
                      ->method('prepare')
                      ->willReturn($this->mockPdoStatementForFetch($mockKingdom));
        
        $this->pdoMock->expects($this->once())
                      ->method('beginTransaction');
        $this->pdoMock->expects($this->once())
                      ->method('rollBack');

        $result = $this->foundationService->purchaseUpgrade($kingdomId, $upgradeKey);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }
}
