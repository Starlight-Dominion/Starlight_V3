<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\GameService;
use sdo\Services\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Models\Dominion;
use sdo\Models\Unit;

class GameServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->createTables();
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
            $table->integer('user_id');
            $table->string('name');
            $table->integer('credits')->default(0);
            $table->integer('xp')->default(0);
        });

        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->integer('production_credits')->default(0);
        });

        Capsule::schema()->create('dominion_manpower', function ($table) {
            $table->integer('dominion_id');
            $table->integer('unit_id');
            $table->integer('total_quantity')->default(0);
        });

        Capsule::schema()->create('dominion_structures', function ($table) {
            $table->integer('dominion_id');
            $table->integer('structure_id');
            $table->integer('level');
        });

        Capsule::schema()->create('structure_levels', function ($table) {
            $table->integer('structure_id');
            $table->integer('level');
            $table->integer('buff_economy')->default(0);
            $table->integer('buff_citizens_per_tick')->default(0);
        });
    }

    public function testIncomeBreakdownCalculation(): void
    {
        $configMock = $this->createMock(ConfigService::class);
        $configMock->method('get')->willReturn(100); 
        
        $service = new GameService(
            $configMock,
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentDominionStructureRepository(),
            new \sdo\Repositories\Eloquent\EloquentRecruitmentRepository()
        );
        
        // 1. Setup Dominion
        $domId = Capsule::table('dominions')->insertGetId([
            'user_id' => 1,
            'name' => 'Test Sector'
        ]);

        // 2. Setup Units with production
        $unitId = Capsule::table('units')->insertGetId([
            'slug' => 'workers',
            'name' => 'Utility Workers',
            'production_credits' => 10
        ]);

        Capsule::table('dominion_manpower')->insert([
            'dominion_id' => $domId,
            'unit_id' => $unitId,
            'total_quantity' => 5
        ]);

        // 3. Setup Structures with economy buff (20%)
        Capsule::table('dominion_structures')->insert([
            'dominion_id' => $domId,
            'structure_id' => 1,
            'level' => 1
        ]);

        Capsule::table('structure_levels')->insert([
            'structure_id' => 1,
            'level' => 1,
            'buff_economy' => 20
        ]);

        // Calculation: (Base 5000 + (5 workers * 50 Legacy CP)) * 1.2 Multiplier = 5250 * 1.2 = 6300
        
        $breakdown = $service->getIncomeBreakdown((int)$domId);
        
        $this->assertEquals(5000, $breakdown['base']);
        $this->assertEquals(250, $breakdown['unit_total']);
        $this->assertCount(1, $breakdown['units']);
        $this->assertEquals('Utility Workers', $breakdown['units'][0]['name']);
        $this->assertEquals(20, $breakdown['bonus_percent']);
        $this->assertEquals(6300, $breakdown['total']);
    }
}
