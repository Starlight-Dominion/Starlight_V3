<?php
declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use sdo\Models\Dominion;
use sdo\Models\DominionManpower;
use sdo\Models\Unit;
use sdo\Services\TacticalService;

class TacticalServiceTest extends TestCase
{
    private TacticalService $service;

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

        $this->createSchema();
        $this->seedUnits();

        $this->service = new TacticalService(
            new \sdo\Repositories\Eloquent\EloquentDominionRepository(),
            new \sdo\Repositories\Eloquent\EloquentManpowerRepository(),
            new \sdo\Repositories\Eloquent\EloquentDominionStructureRepository(),
            new \sdo\Repositories\Eloquent\EloquentDominionArmoryRepository()
        );
    }

    private function createSchema(): void
    {
        $schema = Capsule::schema();

        $schema->create('dominions', function ($table): void {
            $table->increments('id');
            $table->integer('strength_points')->default(0);
            $table->integer('constitution_points')->default(0);
            $table->integer('dexterity_points')->default(0);
            $table->integer('charisma_points')->default(0);
            $table->integer('foundation_hp')->default(1000);
            $table->integer('foundation_max_hp')->default(1000);
            $table->timestamps();
        });

        $schema->create('units', function ($table): void {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->integer('power_offense')->default(0);
            $table->integer('power_defense')->default(0);
            $table->integer('power_spy_offense')->default(0);
            $table->integer('power_spy_defense')->default(0);
        });

        $schema->create('dominion_manpower', function ($table): void {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });

        $schema->create('dominion_structures', function ($table): void {
            $table->integer('dominion_id')->unsigned();
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->unsigned();
        });

        $schema->create('structure_levels', function ($table): void {
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->unsigned();
            $table->integer('buff_offense')->default(0);
            $table->integer('buff_defense')->default(0);
        });

        $schema->create('armory_items', function ($table): void {
            $table->increments('id');
            $table->string('unit_type');
            $table->integer('attack_bonus')->default(0);
            $table->integer('defense_bonus')->default(0);
        });

        $schema->create('kingdom_armory_items', function ($table): void {
            $table->integer('kingdom_id')->unsigned();
            $table->integer('item_id')->unsigned();
            $table->integer('quantity')->default(0);
            $table->boolean('is_equipped')->default(false);
        });
    }

    private function seedUnits(): void
    {
        Unit::create(['slug' => 'soldiers', 'name' => 'Soldiers', 'power_offense' => 10]);
        Unit::create(['slug' => 'guards', 'name' => 'Guards', 'power_defense' => 10]);
        Unit::create(['slug' => 'spies', 'name' => 'Spies', 'power_spy_offense' => 10]);
        Unit::create(['slug' => 'sentries', 'name' => 'Sentries', 'power_spy_defense' => 10]);
    }

    public function testCalculateTacticalRatingsIncludesDeterministicSpyAndSentryValues(): void
    {
        $dominion = Dominion::create([
            'dexterity_points' => 10,
            'charisma_points' => 20,
        ]);

        $spies = Unit::where('slug', 'spies')->firstOrFail();
        $sentries = Unit::where('slug', 'sentries')->firstOrFail();

        DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $spies->id,
            'total_quantity' => 50,
        ]);
        DominionManpower::create([
            'dominion_id' => $dominion->id,
            'unit_id' => $sentries->id,
            'total_quantity' => 40,
        ]);

        $ratings = $this->service->calculateTacticalRatings($dominion->id);

        $this->assertArrayHasKey('espionage', $ratings);
        $this->assertArrayHasKey('sentry', $ratings);
        $this->assertSame(550, $ratings['espionage']);
        $this->assertSame(480, $ratings['sentry']);
    }
}
