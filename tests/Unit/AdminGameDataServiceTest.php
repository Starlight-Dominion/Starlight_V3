<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminGameDataService;
use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Repositories\Eloquent\EloquentUnitRepository;
use sdo\Repositories\Eloquent\EloquentStructureRepository;
use sdo\Repositories\Eloquent\EloquentArmoryRepository;

class AdminGameDataServiceTest extends TestCase
{
    private AdminGameDataService $service;

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
        
        $this->service = new AdminGameDataService(
            new \sdo\Repositories\Eloquent\EloquentUnitRepository(),
            new \sdo\Repositories\Eloquent\EloquentStructureRepository(),
            new \sdo\Repositories\Eloquent\EloquentArmoryRepository(),
            new \sdo\Repositories\Eloquent\EloquentRaceRepository()
        );
    }

    private function createTables(): void
    {
        Capsule::schema()->create('units', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('cost_credits');
            $table->integer('cost_citizens');
            $table->integer('cost_turns');
            $table->integer('power_offense');
            $table->integer('power_defense');
            $table->timestamps();
        });

        Capsule::schema()->create('structures', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('max_level')->default(20);
        });

        Capsule::schema()->create('structure_levels', function ($table) {
            $table->integer('structure_id')->unsigned();
            $table->integer('level');
            $table->bigInteger('cost');
            $table->string('buff_name');
            $table->primary(['structure_id', 'level']);
        });
        
        Capsule::schema()->create('armory_items', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
        });
        
        Capsule::schema()->create('races', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
        });
    }

    public function testUpdateUnit(): void
    {
        $unit = Unit::create([
            'slug' => 'soldiers',
            'name' => 'Soldiers',
            'description' => 'Desc',
            'cost_credits' => 100,
            'cost_citizens' => 1,
            'cost_turns' => 2,
            'power_offense' => 10,
            'power_defense' => 10,
        ]);

        $res = $this->service->updateUnit((int)$unit->id, ['cost_credits' => 999]);

        $this->assertTrue($res);
        $updatedUnit = Unit::find($unit->id);
        $this->assertEquals(999, $updatedUnit->cost_credits);
    }
}
