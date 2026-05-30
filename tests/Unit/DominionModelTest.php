<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\Dominion;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class DominionModelTest extends TestCase
{
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

        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_bot')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->create('game_settings', function ($table) { $table->string('setting_key')->unique(); $table->text('setting_value')->nullable(); });
        Capsule::schema()->create('races', function ($table) { $table->increments('id'); $table->string('name'); $table->string('slug'); $table->text('description')->nullable(); });
        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('race_id')->unsigned()->nullable();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(10000);
            $table->bigInteger('credits_banked')->default(0);
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
            $table->bigInteger('foundation_hp')->default(1000);
            $table->bigInteger('foundation_max_hp')->default(1000);
            $table->datetime('last_tick')->nullable();
            $table->timestamps();
        });
    }

    public function testUserRelationship(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $dominion = $user->dominion()->create([
            'name' => 'Test Dominion',
        ]);

        $this->assertInstanceOf(User::class, $dominion->user);
        $this->assertEquals('testuser', $dominion->user->username);
    }

    public function testGetPlayerLevelLevel1(): void
    {
        $dominion = Dominion::create([
            'user_id' => 1,
            'name' => 'Test',
            'xp' => 0,
        ]);

        $this->assertEquals(1, $dominion->getPlayerLevel());
    }

    public function testGetPlayerLevelGrowth(): void
    {
        $dominion = Dominion::create([
            'user_id' => 1,
            'name' => 'Test',
            'xp' => 10000,
        ]);

        // floor(sqrt(10000 / 100)) + 1 = floor(10) + 1 = 11
        $this->assertEquals(11, $dominion->getPlayerLevel());
    }

    public function testGetPlayerLevelIntermediate(): void
    {
        $dominion = Dominion::create([
            'user_id' => 1,
            'name' => 'Test',
            'xp' => 300,
        ]);

        // floor(sqrt(300 / 100)) + 1 = floor(1.73) + 1 = 2
        $this->assertEquals(2, $dominion->getPlayerLevel());
    }

    public function testCastsWorkCorrectly(): void
    {
        $dominion = Dominion::create([
            'user_id' => 1,
            'name' => 'Test',
            'credits' => '5000',
            'citizens' => '250',
            'turns' => '50',
            'xp' => '100',
        ]);

        $this->assertIsInt($dominion->credits);
        $this->assertIsInt($dominion->citizens);
        $this->assertIsInt($dominion->turns);
        $this->assertIsInt($dominion->xp);
    }

    public function testDateTimeCasts(): void
    {
        $dominion = Dominion::create([
            'user_id' => 1,
            'name' => 'Test',
        ]);

        $this->assertInstanceOf(\DateTime::class, $dominion->created_at);
        $this->assertInstanceOf(\DateTime::class, $dominion->updated_at);
    }
}
