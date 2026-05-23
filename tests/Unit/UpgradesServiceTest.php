<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\UpgradesService;
use sdo\Services\LogService;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use Illuminate\Database\Capsule\Manager as Capsule;

class UpgradesServiceTest extends TestCase
{
    private UpgradesService $service;
    private $logMock;

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

        $this->createSchema();
        $this->seedData();

        $this->logMock = $this->createMock(LogService::class);
        $this->service = new UpgradesService($this->logMock);
    }

    private function createSchema(): void
    {
        $schema = Capsule::schema();
        
        $schema->create('users', function ($table) {
            $table->increments('id');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $schema->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->bigInteger('credits')->default(10000);
            $table->integer('housing_level')->default(1);
            $table->integer('mercenary_market_level')->default(0);
            $table->timestamps();
        });

        $schema->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
        });

        $schema->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });
    }

    private function seedData(): void
    {
        Unit::create(['slug' => 'guards']);
        Unit::create(['slug' => 'soldiers']);
        Unit::create(['slug' => 'spies']);
        Unit::create(['slug' => 'sentries']);
    }

    private function createTestDominion(array $data = []): Dominion
    {
        $user = User::create(['username' => 'player' . uniqid(), 'email' => 'p' . uniqid() . '@t.com', 'password' => 'p']);
        return $user->dominion()->create(array_merge(['name' => 'D' . uniqid(), 'credits' => 100000], $data));
    }

    public function testGetUpgradeDataReturnsCorrectData(): void
    {
        $dominion = $this->createTestDominion();
        
        $data = $this->service->getUpgradeData($dominion->id);

        $this->assertArrayHasKey('dominion', $data);
        $this->assertArrayHasKey('housing_config', $data);
        $this->assertArrayHasKey('mercenary_market_config', $data);
    }

    public function testUpgradeHousingSuccessfully(): void
    {
        $dominion = $this->createTestDominion(['credits' => 10000, 'housing_level' => 1]);
        
        $result = $this->service->upgradeHousing($dominion->id);

        $this->assertTrue($result['success']);
        $dominion->refresh();
        $this->assertEquals(2, $dominion->housing_level);
        $this->assertEquals(9000, $dominion->credits); // Cost for level 2 is 1000
    }

    public function testUpgradeHousingFailsMaxLevel(): void
    {
        $dominion = $this->createTestDominion(['housing_level' => 5]);
        
        $result = $this->service->upgradeHousing($dominion->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('peak efficiency', $result['message']);
    }

    public function testUpgradeMercenaryMarketSuccessfully(): void
    {
        $dominion = $this->createTestDominion(['credits' => 50000, 'mercenary_market_level' => 0]);
        
        $result = $this->service->upgradeMercenaryMarket($dominion->id);

        $this->assertTrue($result['success']);
        $dominion->refresh();
        $this->assertEquals(1, $dominion->mercenary_market_level);
        $this->assertEquals(45000, $dominion->credits); // Cost for level 1 is 5000

        // Verify units granted
        $guardsUnit = Unit::where('slug', 'guards')->first();
        $manpower = DominionManpower::where('dominion_id', $dominion->id)->where('unit_id', $guardsUnit->id)->first();
        $this->assertEquals(4, $manpower->total_quantity); // Level 1 grants 4 guards
    }
}
