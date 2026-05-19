<?php
namespace Shadowreign\Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use sdo\Services\SpyService;

class SpyServiceTest extends TestCase
{
    private PDO $db;
    private SpyService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("CREATE TABLE kingdoms (
            id INTEGER PRIMARY KEY,
            gold INTEGER DEFAULT 0,
            citizens INTEGER DEFAULT 0,
            turns INTEGER DEFAULT 0,
            unit_guards INTEGER DEFAULT 0,
            unit_soldiers INTEGER DEFAULT 0,
            unit_spies INTEGER DEFAULT 0,
            unit_sentries INTEGER DEFAULT 0,
            foundation_level INTEGER DEFAULT 1,
            housing_level INTEGER DEFAULT 1
        )");

        $this->service = new SpyService($this->db);
    }

    public function testGetSpyMissionReturnsSuccess(): void
    {
        $kingdomData = ['unit_spies' => 4];
        $result = $this->service->getSpyMission($kingdomData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('mission_type', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('cost_gold', $result['details']);
    }

    public function testGetSpyMissionZeroSpies(): void
    {
        $kingdomData = ['unit_spies' => 0];
        $result = $this->service->getSpyMission($kingdomData);

        $this->assertFalse($result['success']);
    }

    public function testExecuteReconnaissanceHasSpies(): void
    {
        $this->db->prepare("INSERT INTO kingdoms VALUES (1, 500, 50, 300, 5, 8, 4, 3, 1, 1)")->execute();
        $this->db->prepare("INSERT INTO kingdoms VALUES (2, 1000, 100, 200, 10, 15, 2, 5, 1, 1)")->execute();

        $result = $this->service->executeReconnaissance(1, 2);

        $this->assertArrayHasKey('success', $result);
        if ($result['success']) {
            $this->assertArrayHasKey('intel_gained', $result);
            $this->assertNotNull($result['intel_gained']);
            $this->assertArrayHasKey('gold', $result['intel_gained']);
            $this->assertArrayHasKey('army', $result['intel_gained']);
        }
    }

    public function testExecuteReconnaissanceNoSpies(): void
    {
        $this->db->prepare("INSERT INTO kingdoms VALUES (1, 500, 50, 300, 5, 8, 0, 3, 1, 1)")->execute();

        $result = $this->service->executeReconnaissance(1, 2);

        $this->assertFalse($result['success']);
    }

    public function testGetSpyIntelReturnsStructure(): void
    {
        $this->db->prepare("INSERT INTO kingdoms VALUES (1, 500, 50, 300, 5, 8, 4, 3, 1, 1)")->execute();

        $result = $this->service->getSpyIntel(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('spy_count', $result);
        $this->assertArrayHasKey('available_actions', $result);
        $this->assertEquals(4, $result['spy_count']);
    }

    public function testGetSpyIntelNoSpies(): void
    {
        $this->db->prepare("INSERT INTO kingdoms VALUES (1, 500, 50, 300, 5, 8, 0, 3, 1, 1)")->execute();

        $result = $this->service->getSpyIntel(1);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['spy_count']);
        $this->assertFalse($result['available_actions']['reconnaissance']);
    }

    public function testGetSpyRecruitmentOptions(): void
    {
        $this->db->prepare("INSERT INTO kingdoms VALUES (1, 500, 50, 300, 8, 6, 4, 5, 1, 1)")->execute();

        $result = $this->service->getSpyRecruitmentOptions(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('spies_available_to_deploy', $result);
    }

    public function testGetAvailableSpiesReturnsStructure(): void
    {
        $this->db->prepare("INSERT INTO kingdoms VALUES (1, 500, 50, 300, 8, 6, 4, 5, 1, 1)")->execute();

        $result = $this->service->getAvailableSpies(1);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['available_for_training']);
    }
}
