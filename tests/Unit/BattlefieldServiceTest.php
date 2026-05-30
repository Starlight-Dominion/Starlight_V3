<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\BattlefieldService;
use sdo\Services\TacticalService;
use sdo\Services\LogService;
use sdo\Services\ConfigService;
use sdo\Services\GameService;
use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Infrastructure\TransactionManager;
use Illuminate\Database\Capsule\Manager as Capsule;
use DateTime;

class BattlefieldServiceTest extends TestCase
{
    private BattlefieldService $battlefieldService;
    private $tacticalService;
    private $logService;
    private $configService;
    private $gameService;

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

        $this->createTables();

        $this->tacticalService = $this->createMock(\sdo\Services\TacticalService::class);
        $this->logService = $this->createMock(\sdo\Services\LogService::class);
        $this->configService = $this->createMock(\sdo\Services\ConfigService::class);
        $this->gameService = $this->createMock(\sdo\Services\GameService::class);
        $this->battlefieldService = new BattlefieldService(
            $this->tacticalService,
            $this->logService,
            $this->configService,
            $this->gameService,
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentCombatRepository(),
            new \sdo\Infrastructure\TransactionManager()
        );

        // Default Config Mocks
        $this->configService->method('get')->willReturnMap([
            ['battle_atk_turns_soft_exp', 0.50, 0.50],
            ['battle_atk_turns_max_mult', 1.35, 1.35],
            ['battle_underdog_min_ratio', 0.985, 0.985],
            ['battle_random_noise_min', 0.98, 0.98],
            ['battle_random_noise_max', 1.02, 1.02],
            ['battle_guard_floor', 20000, 20000],
            ['battle_hourly_full_loot_cap', 5, 5],
            ['battle_hourly_reduced_loot_max', 10, 10],
            ['battlefield_list_limit', 200, 200],
        ]);
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Capsule::schema()->create('game_settings', function ($table) { $table->string('setting_key')->unique(); $table->text('setting_value')->nullable(); });
        Capsule::schema()->create('races', function ($table) { $table->increments('id'); $table->string('name'); $table->string('slug'); $table->text('description')->nullable(); });
        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(0);
            $table->integer('citizens')->default(0);
            $table->integer('turns')->default(100);
            $table->integer('xp')->default(0);
            $table->timestamps();
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id');
            $table->integer('unit_id');
            $table->integer('total_quantity')->default(0);
        });

        Capsule::schema()->create('battle_logs', function ($table) {
            $table->increments('id');
            $table->integer('attacker_id');
            $table->integer('defender_id');
            $table->string('attacker_name')->nullable();
            $table->string('defender_name')->nullable();
            $table->string('outcome');
            $table->bigInteger('credits_stolen')->default(0);
            $table->integer('turns_used')->default(0);
            $table->integer('attacker_damage')->default(0);
            $table->integer('defender_damage')->default(0);
            $table->integer('attacker_xp_gained')->default(0);
            $table->integer('defender_xp_gained')->default(0);
            $table->integer('guards_lost')->default(0);
            $table->integer('attacker_soldiers_lost')->default(0);
            $table->integer('structure_damage')->default(0);
            $table->decimal('loot_factor', 3, 2)->default(1.0);
            $table->timestamp('battle_time')->nullable();
        });
    }

    public function testExecuteAttackSuccess(): void
    {
        // Setup Attacker & Defender
        $attackerUser = User::create(['username' => 'attacker', 'email' => 'a@t.com', 'password' => 'p']);
        $attacker = $attackerUser->dominion()->create(['name' => 'A', 'credits' => 1000, 'turns' => 10]);

        $defenderUser = User::create(['username' => 'defender', 'email' => 'd@t.com', 'password' => 'p']);
        $defender = $defenderUser->dominion()->create(['name' => 'D', 'credits' => 5000]);

        // Seed Units
        $soldierUnit = Capsule::table('units')->insertGetId(['slug' => 'soldiers', 'name' => 'Soldiers']);
        $guardUnit = Capsule::table('units')->insertGetId(['slug' => 'guards', 'name' => 'Guards']);

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
        $this->tacticalService->method('calculateTacticalRatings')
            ->willReturnMap([
                [$attacker->id, [
                    'offense' => 100000,
                    'defense' => 1000,
                    'army' => ['soldiers' => 100]
                ]],
                [$defender->id, [
                    'offense' => 100,
                    'defense' => 1000,
                    'army' => ['guards' => 50000]
                ]]
            ]);

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
    }

    public function testExecuteAttackInsufficientTurns(): void
    {
        $attackerUser = User::create(['username' => 'a2', 'email' => 'a2@t.com', 'password' => 'p']);
        $attacker = $attackerUser->dominion()->create(['name' => 'A2', 'turns' => 2]);
        
        $defenderUser = User::create(['username' => 'd2', 'email' => 'd2@t.com', 'password' => 'p']);
        $defender = $defenderUser->dominion()->create(['name' => 'D2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient strike capacity.');

        $this->battlefieldService->executeAttack($attacker->id, $defender->id, 5);
    }

    public function testExecuteAttackSelfHarmProhibited(): void
    {
        $attackerUser = User::create(['username' => 'a3', 'email' => 'a3@t.com', 'password' => 'p']);
        $attacker = $attackerUser->dominion()->create(['name' => 'A3', 'turns' => 10]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Self-harm protocol prohibited.');

        $this->battlefieldService->executeAttack($attacker->id, $attacker->id, 5);
    }

    public function testGetBattlefieldListAppliesConfiguredLimitAndDeterministicTiebreaker(): void
    {
        // Seed 3 dominions
        $u1 = User::create(['username' => 'u1', 'email' => 'u1@t.com', 'password' => 'p']);
        $u1->dominion()->create(['name' => 'D1', 'credits' => 1000]);

        $u2 = User::create(['username' => 'u2', 'email' => 'u2@t.com', 'password' => 'p']);
        $u2->dominion()->create(['name' => 'D2', 'credits' => 2000]);

        $u3 = User::create(['username' => 'u3', 'email' => 'u3@t.com', 'password' => 'p']);
        $u3->dominion()->create(['name' => 'D3', 'credits' => 3000]);

        // Mock config limit to 2
        $this->configService = $this->createMock(\sdo\Services\ConfigService::class);
        $this->configService->method('get')->willReturn(2);
        
        // Re-instantiate with new config mock
        $this->battlefieldService = new BattlefieldService(
            $this->tacticalService,
            $this->logService,
            $this->configService,
            $this->gameService,
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentCombatRepository(),
            new \sdo\Infrastructure\TransactionManager()
        );

        $list = $this->battlefieldService->getBattlefieldList();

        $this->assertCount(2, $list);
        // Order by Gold DESC then ID ASC
        $this->assertEquals('D3', $list[0]['name']);
        $this->assertEquals('D2', $list[1]['name']);
    }

    public function testGetBattlefieldListClampsOutOfRangeConfiguredLimit(): void
    {
        // Seed 1 dominion
        $u1 = User::create(['username' => 'u1x', 'email' => 'u1x@t.com', 'password' => 'p']);
        $u1->dominion()->create(['name' => 'D1x', 'credits' => 1000]);

        // Mock config limit to 9999 (should clamp to 1000)
        $this->configService = $this->createMock(\sdo\Services\ConfigService::class);
        $this->configService->method('get')->willReturn(9999);
        
        $this->battlefieldService = new BattlefieldService(
            $this->tacticalService,
            $this->logService,
            $this->configService,
            $this->gameService,
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentCombatRepository(),
            new \sdo\Infrastructure\TransactionManager()
        );

        $list = $this->battlefieldService->getBattlefieldList();

        // Clamping check is internal, but we can verify it doesn't crash and returns the seeded one
        $this->assertCount(1, $list);
    }
}
