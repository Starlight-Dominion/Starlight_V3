<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\AdminPlayerService;
use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Models\ArmoryItem;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminPlayerServiceTest extends TestCase
{
    private AdminPlayerService $service;

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
        
        $this->service = new AdminPlayerService(
            $this->createMock(DominionRepositoryInterface::class),
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(UnitRepositoryInterface::class),
            $this->createMock(StructureRepositoryInterface::class),
            $this->createMock(ArmoryRepositoryInterface::class)
        );
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->dateTime('last_untrained')->nullable();
            $table->integer('xp')->default(0);
            $table->timestamps();
        });
        
        Capsule::schema()->create('races', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
        });
    }

    public function testSearchDominions(): void
    {
        $user1 = User::create(['username' => 'alpha', 'email' => 'a@t.com', 'password' => 'p']);
        $user1->dominion()->create(['name' => 'First Dominion']);
        
        $user2 = User::create(['username' => 'beta', 'email' => 'b@t.com', 'password' => 'p']);
        $user2->dominion()->create(['name' => 'Second Realm']);

        $results = $this->service->searchDominions('First');
        $this->assertCount(1, $results);
        $this->assertEquals('First Dominion', $results[0]['name']);

        $results = $this->service->searchDominions('alpha');
        $this->assertCount(1, $results);
        $this->assertEquals('alpha', $results[0]['user']['username']);
    }

    public function testUpdateDominionStats(): void
    {
        $user = User::create(['username' => 'player', 'email' => 'p@t.com', 'password' => 'p']);
        $dominion = $user->dominion()->create(['name' => 'D', 'credits' => 100]);

        $res = $this->service->updateDominionStats($dominion->id, ['credits' => 5000, 'xp' => 1000]);

        $this->assertTrue($res);
        $updated = Dominion::find($dominion->id);
        $this->assertEquals(5000, $updated->credits);
        $this->assertEquals(1000, $updated->xp);
    }
}
