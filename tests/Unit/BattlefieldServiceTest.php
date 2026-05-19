<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use sdo\Services\BattlefieldService;

class BattlefieldServiceTest extends TestCase
{
    private PDO $db;
    private BattlefieldService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("CREATE TABLE kingdoms (
            id INTEGER PRIMARY KEY,
            unit_guards INTEGER DEFAULT 0,
            unit_soldiers INTEGER DEFAULT 0,
            unit_spies INTEGER DEFAULT 0,
            unit_sentries INTEGER DEFAULT 0
        )");

        $this->service = new BattlefieldService($this->db);
    }

    public function testCalculateArmyPowerEmpty(): void
    {
        $result = $this->service->calculateArmyPower([
            'guards' => 0,
            'soldiers' => 0,
            'spies' => 0,
            'sentries' => 0,
        ]);

        $this->assertEquals(0, $result['total_offense']);
        $this->assertEquals(0, $result['total_defense']);
        $this->assertEquals(0, $result['weighted_power']);
    }

    public function testCalculateArmyPowerSingleGuard(): void
    {
        $result = $this->service->calculateArmyPower([
            'guards' => 1,
            'soldiers' => 0,
            'spies' => 0,
            'sentries' => 0,
        ]);

        $this->assertEquals(5, $result['total_offense']);
        $this->assertEquals(15, $result['total_defense']);
        $this->assertGreaterThan(0, $result['weighted_power']);
    }

    public function testCalculateArmyPowerMixedUnits(): void
    {
        $result = $this->service->calculateArmyPower([
            'guards' => 10,
            'soldiers' => 5,
            'spies' => 2,
            'sentries' => 3,
        ]);

        $expectedOffense = (10 * 5) + (5 * 10) + (2 * 1) + (3 * 2);
        $expectedDefense = (10 * 15) + (5 * 10) + (2 * 1) + (3 * 25);

        $this->assertEquals($expectedOffense, $result['total_offense']);
        $this->assertEquals($expectedDefense, $result['total_defense']);
    }

    public function testCalculateArmyPowerWeightedFormula(): void
    {
        $result = $this->service->calculateArmyPower([
            'guards' => 100,
            'soldiers' => 0,
            'spies' => 0,
            'sentries' => 0,
        ]);

        $offense = 100 * 5;
        $defense = 100 * 15;
        $expectedWeighted = max(0, pow($offense * 1.1, 0.3) * pow($defense * 0.9, 0.3));

        $this->assertEqualsWithDelta($expectedWeighted, $result['weighted_power'], 0.01);
    }

    public function testSimulateBattleAttackerWins(): void
    {
        $result = $this->service->simulateBattle(
            ['guards' => 100, 'soldiers' => 50, 'spies' => 0, 'sentries' => 0],
            ['guards' => 10, 'soldiers' => 5, 'spies' => 0, 'sentries' => 0]
        );

        $this->assertEquals('attacker', $result['victor']);
        $this->assertGreaterThan(0, $result['attacker_loss_percent']);
        $this->assertGreaterThan(0, $result['defender_loss_percent']);
    }

    public function testSimulateBattleDefenderWins(): void
    {
        $result = $this->service->simulateBattle(
            ['guards' => 10, 'soldiers' => 5, 'spies' => 0, 'sentries' => 0],
            ['guards' => 100, 'soldiers' => 50, 'spies' => 0, 'sentries' => 0]
        );

        $this->assertEquals('defender', $result['victor']);
    }

    public function testSimulateBattleStalemate(): void
    {
        $units = ['guards' => 10, 'soldiers' => 10, 'spies' => 0, 'sentries' => 0];

        $result = $this->service->simulateBattle($units, $units);

        $this->assertEquals('defender', $result['victor']);
    }

    public function testSimulateBattleNoDefender(): void
    {
        $result = $this->service->simulateBattle(
            ['guards' => 10, 'soldiers' => 10, 'spies' => 0, 'sentries' => 0],
            ['guards' => 0, 'soldiers' => 0, 'spies' => 0, 'sentries' => 0]
        );

        $this->assertEquals('attacker', $result['victor']);
        $this->assertEquals(2.0, $result['attacker_loss_percent']);
        $this->assertEquals(90.0, $result['defender_loss_percent']);
    }

    public function testSimulateBattleNoAttacker(): void
    {
        $result = $this->service->simulateBattle(
            ['guards' => 0, 'soldiers' => 0, 'spies' => 0, 'sentries' => 0],
            ['guards' => 10, 'soldiers' => 10, 'spies' => 0, 'sentries' => 0]
        );

        $this->assertEquals('defender', $result['victor']);
        $this->assertEquals(80.0, $result['attacker_loss_percent']);
        $this->assertEquals(2.0, $result['defender_loss_percent']);
    }

    public function testSimulateBattleBothEmpty(): void
    {
        $result = $this->service->simulateBattle(
            ['guards' => 0, 'soldiers' => 0, 'spies' => 0, 'sentries' => 0],
            ['guards' => 0, 'soldiers' => 0, 'spies' => 0, 'sentries' => 0]
        );

        $this->assertEquals('neutral', $result['victor']);
        $this->assertEquals(0.0, $result['attacker_loss_percent']);
        $this->assertEquals(0.0, $result['defender_loss_percent']);
    }

    public function testGetStabilizedUnits(): void
    {
        $this->db->exec("INSERT INTO kingdoms VALUES (1, 10, 20, 5, 3)");

        $result = $this->service->getStabilizedUnits(1);

        $this->assertEquals(10, $result['guards']);
        $this->assertEquals(20, $result['soldiers']);
        $this->assertEquals(5, $result['spies']);
        $this->assertEquals(3, $result['sentries']);
    }

    public function testGetStabilizedUnitsNotFound(): void
    {
        $result = $this->service->getStabilizedUnits(999);

        $this->assertEquals(0, $result['guards']);
        $this->assertEquals(0, $result['soldiers']);
    }

    public function testGetStabilizedUnitsForMultipleKingdoms(): void
    {
        $this->db->exec("INSERT INTO kingdoms VALUES (1, 10, 20, 5, 3)");
        $this->db->exec("INSERT INTO kingdoms VALUES (2, 30, 40, 10, 5)");

        $result = $this->service->getStabilizedUnitsForMultipleKingdoms([1, 2]);

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertEquals(10, $result[1]['guards']);
        $this->assertEquals(30, $result[2]['guards']);
    }

    public function testGetStabilizedUnitsForMultipleKingdomsEmpty(): void
    {
        $result = $this->service->getStabilizedUnitsForMultipleKingdoms([]);

        $this->assertEmpty($result);
    }

    public function testGetPowerComparison(): void
    {
        $result = $this->service->getPowerComparison(
            ['guards' => 50, 'soldiers' => 30, 'spies' => 0, 'sentries' => 10],
            'Army A',
            ['guards' => 20, 'soldiers' => 10, 'spies' => 0, 'sentries' => 5],
            'Army B'
        );

        $this->assertArrayHasKey('comparison', $result);
        $this->assertArrayHasKey('ratio_1', $result);
        $this->assertArrayHasKey('ratio_2', $result);
    }

    public function testGetPowerComparisonBothEmpty(): void
    {
        $result = $this->service->getPowerComparison(
            ['guards' => 0, 'soldiers' => 0, 'spies' => 0, 'sentries' => 0],
            'Army A',
            ['guards' => 0, 'soldiers' => 0, 'spies' => 0, 'sentries' => 0],
            'Army B'
        );

        $this->assertEquals('none', $result['army_1']);
        $this->assertEquals('none', $result['army_2']);
    }
}
