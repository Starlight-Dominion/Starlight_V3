<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\BattlefieldService;
use sdo\Services\TacticalService;
use sdo\Services\LogService;
use sdo\Services\ConfigService;
use sdo\Models\Dominion;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class BattlefieldServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private BattlefieldService $battlefieldService;
    private $tacticalService;
    private $logService;
    private $configService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Boot Eloquent with SQLite In-Memory
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // 2. Build Schema
        $schema = Capsule::schema();
        
        $schema->create('users', function($table) {
            $table->increments('id');
            $table->string('username');
            $table->timestamps();
        });

        $schema->create('dominions', function($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
            $table->integer('credits')->default(0);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->timestamps();
        });

        $schema->create('units', function($table) {
            $table->increments('id');
            $table->string('slug');
        });

        $schema->create('dominion_manpower', function($table) {
            $table->integer('dominion_id');
            $table->integer('unit_id');
            $table->integer('total_quantity');
        });

        $schema->create('battle_logs', function($table) {
            $table->increments('id');
            $table->integer('attacker_id');
            $table->integer('defender_id');
            $table->string('attacker_name');
            $table->string('defender_name');
            $table->string('outcome');
            $table->bigInteger('credits_stolen');
            $table->integer('turns_used');
            $table->bigInteger('attacker_damage');
            $table->bigInteger('defender_damage');
            $table->integer('attacker_xp_gained');
            $table->integer('guards_lost');
            $table->integer('attacker_soldiers_lost');
            $table->decimal('loot_factor', 3, 2);
            $table->timestamp('battle_time');
        });

        $this->tacticalService = Mockery::mock(TacticalService::class);
        $this->logService = Mockery::mock(LogService::class);
        $this->configService = Mockery::mock(ConfigService::class);
        $this->battlefieldService = new BattlefieldService($this->tacticalService, $this->logService, $this->configService);

        // Default Config Mocks
        $this->configService->shouldReceive('get')->with('battle_atk_turns_soft_exp', 0.50)->andReturn(0.50)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_atk_turns_max_mult', 1.35)->andReturn(1.35)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_underdog_min_ratio', 0.985)->andReturn(0.985)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_random_noise_min', 0.98)->andReturn(0.98)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_random_noise_max', 1.02)->andReturn(1.02)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_guard_floor', 20000)->andReturn(20000)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_hourly_full_loot_cap', 5)->andReturn(5)->byDefault();
        $this->configService->shouldReceive('get')->with('battle_hourly_reduced_loot_max', 10)->andReturn(10)->byDefault();
    }

    public function testExecuteAttackSuccess(): void
    {
        // Setup Attacker
        $attackerUser = new User(['username' => 'Attacker']);
        $attackerUser->save();
        $attacker = new Dominion([
            'user_id' => $attackerUser->id,
            'name' => 'Attacker Kingdom',
            'credits' => 1000,
            'turns' => 10,
            'xp' => 100
        ]);
        $attacker->save();

        // Setup Defender
        $defenderUser = new User(['username' => 'Defender']);
        $defenderUser->save();
        $defender = new Dominion([
            'user_id' => $defenderUser->id,
            'name' => 'Defender Kingdom',
            'credits' => 5000,
            'turns' => 100,
            'xp' => 500
        ]);
        $defender->save();

        // Setup Units
        $soldierUnit = Capsule::table('units')->insertGetId(['slug' => 'soldiers']);
        $guardUnit = Capsule::table('units')->insertGetId(['slug' => 'guards']);

        Capsule::table('dominion_manpower')->insert([
            'dominion_id' => $attacker->id,
            'unit_id' => $soldierUnit,
            'total_quantity' => 100
        ]);

        Capsule::table('dominion_manpower')->insert([
            'dominion_id' => $defender->id,
            'unit_id' => $guardUnit,
            'total_quantity' => 50000 // Above GUARD_FLOOR
        ]);

        // Mock Tactical Ratings
        $this->tacticalService->shouldReceive('calculateTacticalRatings')
            ->with($attacker->id)
            ->andReturn([
                'offense' => 100000,
                'defense' => 1000,
                'army' => ['soldiers' => 100]
            ]);

        $this->tacticalService->shouldReceive('calculateTacticalRatings')
            ->with($defender->id)
            ->andReturn([
                'offense' => 100,
                'defense' => 1000,
                'army' => ['guards' => 50000]
            ]);

        $this->logService->shouldReceive('log')->twice();

        $result = $this->battlefieldService->executeAttack($attacker->id, $defender->id, 5);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('battle_id', $result);
        $this->assertEquals('Dominion Victorious.', $result['message']);

        // Verify state changes
        $attacker->refresh();
        $defender->refresh();

        $this->assertEquals(5, $attacker->turns);
        $this->assertGreaterThan(1000, $attacker->credits); // Stole credits
        $this->assertLessThan(5000, $defender->credits);
        $this->assertGreaterThan(100, $attacker->xp);

        // Verify log exists
        $log = Capsule::table('battle_logs')->find($result['battle_id']);
        $this->assertNotNull($log);
        $this->assertEquals('victory', $log->outcome);
        $this->assertGreaterThan(0, $log->credits_stolen);
    }

    public function testExecuteAttackInsufficientTurns(): void
    {
        $attacker = new Dominion(['user_id' => 1, 'name' => 'A', 'turns' => 1]);
        $attacker->save();
        $defender = new Dominion(['user_id' => 2, 'name' => 'B']);
        $defender->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient strike capacity.");

        $this->battlefieldService->executeAttack($attacker->id, $defender->id, 5);
    }

    public function testExecuteAttackSelfHarmProhibited(): void
    {
        $attacker = new Dominion(['user_id' => 1, 'name' => 'A', 'turns' => 10]);
        $attacker->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Self-harm protocol prohibited.");

        $this->battlefieldService->executeAttack($attacker->id, $attacker->id, 5);
    }
}
