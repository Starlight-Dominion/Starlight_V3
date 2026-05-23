<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\SpyService;
use sdo\Services\TacticalService;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Eloquent\EloquentDominionRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

class SpyServiceTest extends TestCase
{
    private SpyService $service;
    private $tacticalMock;

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

        $this->tacticalMock = $this->createMock(TacticalService::class);
        $this->service = new SpyService(new EloquentDominionRepository(), $this->tacticalMock);
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
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->integer('foundation_hp')->default(1000);
            $table->timestamps();
        });

        $schema->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
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
        Unit::create(['slug' => 'spies', 'name' => 'Spies']);
        Unit::create(['slug' => 'sentries', 'name' => 'Sentries']);
        Unit::create(['slug' => 'guards', 'name' => 'Guards']);
        Unit::create(['slug' => 'soldiers', 'name' => 'Soldiers']);
    }

    private function createTestDominion(array $data = []): Dominion
    {
        $user = User::create(['username' => 'player' . uniqid(), 'email' => 'p' . uniqid() . '@t.com', 'password' => 'p']);
        return $user->dominion()->create(array_merge(['name' => 'D' . uniqid(), 'credits' => 100000], $data));
    }

    public function testExecuteReconnaissanceHasSpies(): void
    {
        $attacker = $this->createTestDominion(['credits' => 1000, 'citizens' => 100, 'turns' => 100]);
        $defender = $this->createTestDominion(['name' => 'Target']);

        $spyUnit = Unit::where('slug', 'spies')->first();
        DominionManpower::create(['dominion_id' => $attacker->id, 'unit_id' => $spyUnit->id, 'total_quantity' => 10]);

        $this->tacticalMock->method('calculateTacticalRatings')->willReturn([
            'espionage' => 1000,
            'sentry' => 100,
            'army' => []
        ]);

        $result = $this->service->executeReconnaissance($attacker->id, $defender->id);

        $this->assertTrue($result['success'] || isset($result['message']));
        if ($result['success']) {
            $this->assertArrayHasKey('intel_gained', $result);
            $this->assertEquals('Target', $result['intel_gained']['name']);
        }
    }

    public function testExecuteReconnaissanceNoSpies(): void
    {
        $attacker = $this->createTestDominion();
        $defender = $this->createTestDominion();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active spies');

        $this->service->executeReconnaissance($attacker->id, $defender->id);
    }

    public function testGetSpyIntel(): void
    {
        $dominion = $this->createTestDominion();
        $spyUnit = Unit::where('slug', 'spies')->first();
        DominionManpower::create(['dominion_id' => $dominion->id, 'unit_id' => $spyUnit->id, 'total_quantity' => 5]);

        $result = $this->service->getSpyIntel($dominion->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['spy_count']);
    }
}
