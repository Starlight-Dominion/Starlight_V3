<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use sdo\Services\TickService;
use sdo\Services\UntrainingService;
use sdo\Services\MinesService;

class TickServiceTest extends TestCase
{
    private PDO $db;
    private TickService $service;
    private $untrainingMock;
    private $minesMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("CREATE TABLE kingdoms (
            id INTEGER PRIMARY KEY,
            gold INTEGER DEFAULT 0,
            citizens INTEGER DEFAULT 0,
            turns INTEGER DEFAULT 0,
            miners INTEGER DEFAULT 0,
            xp INTEGER DEFAULT 0,
            foundation_level INTEGER DEFAULT 1,
            housing_level INTEGER DEFAULT 1,
            current_mine_tier INTEGER DEFAULT 1,
            current_mine_level INTEGER DEFAULT 1,
            base_gold_per_tick INTEGER DEFAULT 100,
            last_tick DATETIME,
            deposits_today INTEGER DEFAULT 0,
            last_deposit_recharge DATETIME
        )");

        $this->untrainingMock = $this->createMock(UntrainingService::class);
        $this->minesMock = $this->createMock(MinesService::class);
        $this->service = new TickService($this->db, $this->untrainingMock, $this->minesMock);
    }

    public function testProcessGlobalTickUpdatesKingdoms(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, gold, citizens, turns, miners, housing_level, current_mine_tier, current_mine_level) 
            VALUES (1, 0, 0, 0, 5, 1, 1, 1)");

        $this->untrainingMock->expects($this->once())
            ->method('releaseHeldCitizens')
            ->with(0);

        $this->minesMock->method('calculateCurrentProduction')
            ->willReturn(25.0);

        $this->service->processGlobalTick();

        $row = $this->db->query("SELECT gold, citizens, turns FROM kingdoms WHERE id = 1")->fetch();

        $this->assertGreaterThan(0, $row['gold']);
        $this->assertGreaterThan(0, $row['citizens']);
        $this->assertEquals(10, $row['turns']);
    }

    public function testProcessGlobalTickBatchesCorrectly(): void
    {
        $this->untrainingMock->method('releaseHeldCitizens');
        $this->minesMock->method('calculateCurrentProduction')->willReturn(0.0);

        for ($i = 1; $i <= 250; $i++) {
            $this->db->exec("INSERT INTO kingdoms (id, gold, citizens, turns) VALUES ({$i}, 0, 0, 0)");
        }

        $this->service->processGlobalTick();

        $count = $this->db->query("SELECT COUNT(*) FROM kingdoms WHERE turns = 10")->fetchColumn();
        $this->assertEquals(250, $count);
    }

    public function testProcessGlobalTickReleasesHeldCitizens(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, gold, citizens, turns) VALUES (1, 0, 0, 0)");

        $this->untrainingMock->expects($this->once())
            ->method('releaseHeldCitizens')
            ->with(0);

        $this->minesMock->method('calculateCurrentProduction')->willReturn(0.0);

        $this->service->processGlobalTick();
    }

    public function testProcessGlobalTickMineProduction(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, gold, citizens, turns, miners, current_mine_tier, current_mine_level) 
            VALUES (1, 0, 0, 0, 10, 1, 1)");

        $this->untrainingMock->method('releaseHeldCitizens');
        $this->minesMock->method('calculateCurrentProduction')
            ->willReturn(50.0);

        $this->service->processGlobalTick();

        $row = $this->db->query("SELECT gold FROM kingdoms WHERE id = 1")->fetch();
        $this->assertEquals(150, $row['gold']);
    }

    public function testProcessGlobalTickHousingBonus(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, gold, citizens, turns, housing_level) 
            VALUES (1, 0, 0, 0, 3)");

        $this->untrainingMock->method('releaseHeldCitizens');
        $this->minesMock->method('calculateCurrentProduction')->willReturn(0.0);

        $this->service->processGlobalTick();

        $row = $this->db->query("SELECT citizens FROM kingdoms WHERE id = 1")->fetch();
        // Housing level 3 = 75 citizens_per_tick in config/housing.php
        $this->assertEquals(75, $row['citizens']);
    }

    public function testProcessGlobalTickUpdatesTimestamp(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, gold, citizens, turns) VALUES (1, 0, 0, 0)");

        $this->untrainingMock->method('releaseHeldCitizens');
        $this->minesMock->method('calculateCurrentProduction')->willReturn(0.0);

        $this->service->processGlobalTick();

        $row = $this->db->query("SELECT last_tick FROM kingdoms WHERE id = 1")->fetch();
        $this->assertNotNull($row['last_tick']);
    }

    public function testProcessGlobalTickEmptyDatabase(): void
    {
        $this->untrainingMock->method('releaseHeldCitizens');
        $this->minesMock->method('calculateCurrentProduction')->willReturn(0.0);

        $this->service->processGlobalTick();

        $count = $this->db->query("SELECT COUNT(*) FROM kingdoms")->fetchColumn();
        $this->assertEquals(0, $count);
    }
}
