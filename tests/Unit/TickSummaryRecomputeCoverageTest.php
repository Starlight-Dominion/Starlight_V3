<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use sdo\Repositories\Eloquent\EloquentStructureRepository;
use sdo\Repositories\Eloquent\EloquentUnitRepository;
use sdo\Support\TickSummaryMaintainer;

class TickSummaryRecomputeCoverageTest extends TestCase
{
    private EloquentUnitRepository $unitRepository;
    private EloquentStructureRepository $structureRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::statement('PRAGMA foreign_keys = ON');

        $this->createTables();
        $this->seedBaselineData();

        $this->unitRepository = new EloquentUnitRepository();
        $this->structureRepository = new EloquentStructureRepository();
    }

    public function testRecomputeOnUnitProductionCreditsUpdate(): void
    {
        $this->assertSummary(50, 15, 40);

        $updated = $this->unitRepository->update(2, ['production_credits' => 9]);

        $this->assertTrue($updated);
        $this->assertSummary(50, 15, 65);
    }

    public function testRecomputeOnUnitDelete(): void
    {
        $this->assertSummary(50, 15, 40);

        $deleted = $this->unitRepository->delete(2);

        $this->assertTrue($deleted);
        $this->assertSummary(50, 15, 20);
    }

    public function testRecomputeOnStructureLevelBuffUpdate(): void
    {
        $this->assertSummary(50, 15, 40);

        $updated = $this->structureRepository->updateLevel(1, 1, [
            'buff_economy' => 25,
            'buff_citizens_per_tick' => 6,
        ]);

        $this->assertTrue($updated);
        $this->assertSummary(65, 16, 40);
    }

    public function testRecomputeOnStructureDelete(): void
    {
        $this->assertSummary(50, 15, 40);

        $deleted = $this->structureRepository->delete(2);

        $this->assertTrue($deleted);
        $this->assertSummary(10, 5, 40);
    }

    private function createTables(): void
    {
        Capsule::schema()->create('dominions', function ($table): void {
            $table->increments('id');
        });

        Capsule::schema()->create('structures', function ($table): void {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
        });

        Capsule::schema()->create('structure_levels', function ($table): void {
            $table->integer('structure_id')->unsigned();
            $table->integer('level');
            $table->integer('buff_economy')->default(0);
            $table->integer('buff_citizens_per_tick')->default(0);
            $table->primary(['structure_id', 'level']);
            $table->foreign('structure_id')->references('id')->on('structures')->onDelete('cascade');
        });

        Capsule::schema()->create('dominion_structures', function ($table): void {
            $table->integer('dominion_id')->unsigned();
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->default(0);
            $table->primary(['dominion_id', 'structure_id']);
            $table->foreign('dominion_id')->references('id')->on('dominions')->onDelete('cascade');
            $table->foreign('structure_id')->references('id')->on('structures')->onDelete('cascade');
        });

        Capsule::schema()->create('units', function ($table): void {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->text('description')->default('');
            $table->integer('cost_credits')->default(0);
            $table->integer('cost_citizens')->default(0);
            $table->integer('cost_turns')->default(0);
            $table->integer('power_offense')->default(0);
            $table->integer('power_defense')->default(0);
            $table->integer('power_spy_offense')->default(0);
            $table->integer('power_spy_defense')->default(0);
            $table->integer('production_credits')->default(0);
        });

        Capsule::schema()->create('dominion_manpower', function ($table): void {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
            $table->foreign('dominion_id')->references('id')->on('dominions')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });

        Capsule::schema()->create('dominion_tick_summaries', function ($table): void {
            $table->integer('dominion_id')->unsigned()->primary();
            $table->integer('total_economy_buff')->default(0);
            $table->integer('total_citizen_buff')->default(0);
            $table->bigInteger('total_unit_production')->default(0);
        });
    }

    private function seedBaselineData(): void
    {
        Capsule::table('dominions')->insert([
            ['id' => 1],
        ]);

        Capsule::table('structures')->insert([
            ['id' => 1, 'slug' => 'economy', 'name' => 'Economy'],
            ['id' => 2, 'slug' => 'housing', 'name' => 'Housing'],
        ]);

        Capsule::table('structure_levels')->insert([
            ['structure_id' => 1, 'level' => 1, 'buff_economy' => 10, 'buff_citizens_per_tick' => 5],
            ['structure_id' => 2, 'level' => 1, 'buff_economy' => 40, 'buff_citizens_per_tick' => 10],
        ]);

        Capsule::table('dominion_structures')->insert([
            ['dominion_id' => 1, 'structure_id' => 1, 'level' => 1],
            ['dominion_id' => 1, 'structure_id' => 2, 'level' => 1],
        ]);

        Capsule::table('units')->insert([
            [
                'id' => 1,
                'slug' => 'workers',
                'name' => 'Workers',
                'description' => '',
                'cost_credits' => 0,
                'cost_citizens' => 0,
                'cost_turns' => 0,
                'power_offense' => 0,
                'power_defense' => 0,
                'power_spy_offense' => 0,
                'power_spy_defense' => 0,
                'production_credits' => 2,
            ],
            [
                'id' => 2,
                'slug' => 'engineers',
                'name' => 'Engineers',
                'description' => '',
                'cost_credits' => 0,
                'cost_citizens' => 0,
                'cost_turns' => 0,
                'power_offense' => 0,
                'power_defense' => 0,
                'power_spy_offense' => 0,
                'power_spy_defense' => 0,
                'production_credits' => 4,
            ],
        ]);

        Capsule::table('dominion_manpower')->insert([
            ['dominion_id' => 1, 'unit_id' => 1, 'total_quantity' => 10],
            ['dominion_id' => 1, 'unit_id' => 2, 'total_quantity' => 5],
        ]);

        TickSummaryMaintainer::recomputeForDominion(1);
    }

    private function assertSummary(int $economy, int $citizens, int $unitProduction): void
    {
        $summary = Capsule::table('dominion_tick_summaries')->where('dominion_id', 1)->first();

        $this->assertNotNull($summary);
        $this->assertSame($economy, (int)$summary->total_economy_buff);
        $this->assertSame($citizens, (int)$summary->total_citizen_buff);
        $this->assertSame($unitProduction, (int)$summary->total_unit_production);
    }
}
