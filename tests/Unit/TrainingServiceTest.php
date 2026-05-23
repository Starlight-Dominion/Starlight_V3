<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\TrainingService;
use sdo\Services\LogService;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use Illuminate\Database\Capsule\Manager as Capsule;

class TrainingServiceTest extends TestCase
{
    private TrainingService $service;
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
        $this->service = new TrainingService($this->logMock);
    }

    private function createSchema(): void
    {
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
    }

    private function seedData(): void
    {
        Unit::create([
            'slug' => 'soldiers',
            'name' => 'Soldiers',
            'cost_credits' => 100,
            'cost_citizens' => 1,
            'cost_turns' => 1,
            'power_offense' => 10,
            'power_defense' => 5
        ]);
    }

    public function testGetUnitConfig(): void
    {
        $config = $this->service->getUnitConfig();
        $this->assertArrayHasKey('soldiers', $config);
        $this->assertEquals(100, $config['soldiers']['cost_credits']);
    }

    public function testTrainSuccess(): void
    {
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $dominion = $user->dominion()->create(['name' => 'D', 'credits' => 1000, 'citizens' => 100, 'turns' => 100]);

        $result = $this->service->train($dominion->id, 'soldiers', 5);

        $this->assertTrue($result['success']);
        $dominion->refresh();
        $this->assertEquals(500, $dominion->credits); // 1000 - (100 * 5)
        $this->assertEquals(95, $dominion->citizens);
        $this->assertEquals(95, $dominion->turns);

        $workerUnit = Unit::where('slug', 'soldiers')->first();
        $manpower = DominionManpower::where('dominion_id', $dominion->id)
            ->where('unit_id', $workerUnit->id)
            ->first();
        $this->assertEquals(5, $manpower->total_quantity);
    }

    public function testTrainInsufficientCredits(): void
    {
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $dominion = $user->dominion()->create(['name' => 'D', 'credits' => 50]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient resources");

        $this->service->train($dominion->id, 'soldiers', 1);
    }
}
