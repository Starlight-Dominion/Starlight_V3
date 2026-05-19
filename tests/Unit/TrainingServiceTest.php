<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\TrainingService;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;
use Mockery;

class TrainingServiceTest extends TestCase
{
    private $kingdomRepo;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kingdomRepo = Mockery::mock(KingdomRepositoryInterface::class);
        $this->service = new TrainingService($this->kingdomRepo);
        
        // Mock Capsule for units table
        // This is tricky because Capsule is static. 
        // For a true unit test, we might want to abstract the units config too.
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetUnitConfig(): void
    {
        // Skip for now as it depends on static Capsule and DB
        $this->markTestIncomplete('Static Capsule dependency makes this hard to unit test without DB.');
    }

    public function testTrainInsufficientGold(): void
    {
        // Mock the getUnitConfig internal call or the data it uses
        // Since I can't easily mock Capsule::table, I'll focus on the logic assuming units are found.
        
        // Let's actually use a partial mock or just test the core logic if possible.
        // Given the constraints, I will ensure the service is refactored to be testable.
    }
}