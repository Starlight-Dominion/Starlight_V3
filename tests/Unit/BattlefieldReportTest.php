<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\BattlefieldService;
use sdo\Services\TacticalService;
use sdo\Services\LogService;
use sdo\Services\ConfigService;
use sdo\Models\Dominion;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class BattlefieldReportTest extends TestCase
{
    private BattlefieldService $service;
    private $tacticalMock;
    private $logMock;
    private $configMock;

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

        Capsule::schema()->create('battle_logs', function ($table) {
            $table->increments('id');
            $table->integer('attacker_id');
            $table->integer('defender_id');
            $table->string('outcome');
            $table->timestamps();
        });

        $this->tacticalMock = $this->createMock(TacticalService::class);
        $this->logMock = $this->createMock(LogService::class);
        $this->configMock = $this->createMock(ConfigService::class);
        
        $this->service = new BattlefieldService(
            $this->createMock(\sdo\Services\TacticalService::class),
            $this->createMock(\sdo\Services\LogService::class),
            $this->createMock(\sdo\Services\ConfigService::class),
            $this->createMock(\sdo\Services\GameService::class),
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentCombatRepository(),
            new \sdo\Infrastructure\TransactionManager()
        );
    }

    public function testGetBattleLogReturnsObject(): void
    {
        $logId = (int)Capsule::table('battle_logs')->insertGetId([
            'attacker_id' => 1,
            'defender_id' => 2,
            'outcome' => 'victory'
        ]);

        $result = $this->service->getBattleLog($logId);

        $this->assertNotNull($result);
        $this->assertEquals('victory', $result->outcome);
    }

    public function testGetBattleLogReturnsNullIfNotFound(): void
    {
        $result = $this->service->getBattleLog(999);
        $this->assertNull($result);
    }
}
