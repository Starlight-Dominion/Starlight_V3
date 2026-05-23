<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminService;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminUnitCRUDTest extends TestCase
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
            $table->string('requirement_slug')->nullable();
            $table->integer('foundation_level_req')->default(0);
        });
    }

    public function testAddAndDeleteUnit(): void
    {
        $adminService = new AdminService();

        // 1. Add Unit
        $unitId = $adminService->addUnit([
            'slug' => 'test_unit',
            'name' => 'Test Unit',
            'description' => 'Test Desc',
            'cost_credits' => 100,
            'cost_citizens' => 1,
            'cost_turns' => 1,
            'power_offense' => 10,
            'power_defense' => 10
        ]);

        $this->assertNotNull($unitId);
        $this->assertEquals(1, Capsule::table('units')->count());

        // 2. Delete Unit
        $res = $adminService->deleteUnit($unitId);
        $this->assertTrue($res);
        $this->assertEquals(0, Capsule::table('units')->count());
    }

    public function testUpdateUnitRequirements(): void
    {
        $adminService = new AdminService();
        $unitId = Capsule::table('units')->insertGetId([
            'slug' => 'soldiers',
            'name' => 'Soldiers',
            'description' => 'D',
            'cost_credits' => 100, 
            'cost_citizens' => 1, 
            'cost_turns' => 1, 
            'power_offense' => 1, 
            'power_defense' => 1
        ]);

        $res = $adminService->updateUnit((int)$unitId, [
            'requirement_slug' => 'guards',
            'foundation_level_req' => 5
        ]);

        $this->assertTrue($res);
        $unit = Capsule::table('units')->where('id', $unitId)->first();
        $this->assertEquals('guards', $unit->requirement_slug);
        $this->assertEquals(5, $unit->foundation_level_req);
    }
}
