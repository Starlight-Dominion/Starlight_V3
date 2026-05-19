<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\Kingdom;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class KingdomModelTest extends TestCase
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

        Capsule::schema()->create('kingdoms', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('kingdom_name');
            $table->integer('gold')->default(1000);
            $table->integer('citizens')->default(50);
            $table->integer('turns')->default(100);
            $table->integer('miners')->default(0);
            $table->integer('xp')->default(0);
            $table->timestamp('last_tick')->nullable();
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

        $kingdom = $user->kingdom()->create([
            'kingdom_name' => 'Test Kingdom',
        ]);

        $this->assertInstanceOf(User::class, $kingdom->user);
        $this->assertEquals('testuser', $kingdom->user->username);
    }

    public function testGetPlayerLevelLevel1(): void
    {
        $kingdom = Kingdom::create([
            'user_id' => 1,
            'kingdom_name' => 'Test',
            'xp' => 0,
        ]);

        $this->assertEquals(1, $kingdom->getPlayerLevel());
    }

    public function testGetPlayerLevelGrowth(): void
    {
        $kingdom = Kingdom::create([
            'user_id' => 1,
            'kingdom_name' => 'Test',
            'xp' => 10000,
        ]);

        // floor(sqrt(10000 / 100)) + 1 = floor(10) + 1 = 11
        $this->assertEquals(11, $kingdom->getPlayerLevel());
    }

    public function testGetPlayerLevelIntermediate(): void
    {
        $kingdom = Kingdom::create([
            'user_id' => 1,
            'kingdom_name' => 'Test',
            'xp' => 300,
        ]);

        // floor(sqrt(300 / 100)) + 1 = floor(1.73) + 1 = 2
        $this->assertEquals(2, $kingdom->getPlayerLevel());
    }

    public function testCastsWorkCorrectly(): void
    {
        $kingdom = Kingdom::create([
            'user_id' => 1,
            'kingdom_name' => 'Test',
            'gold' => '500',
            'citizens' => '25',
            'turns' => '50',
            'miners' => '10',
            'xp' => '100',
        ]);

        $this->assertIsInt($kingdom->gold);
        $this->assertIsInt($kingdom->citizens);
        $this->assertIsInt($kingdom->turns);
        $this->assertIsInt($kingdom->miners);
        $this->assertIsInt($kingdom->xp);
    }

    public function testDateTimeCasts(): void
    {
        $kingdom = Kingdom::create([
            'user_id' => 1,
            'kingdom_name' => 'Test',
        ]);

        $this->assertInstanceOf(\DateTime::class, $kingdom->created_at);
        $this->assertInstanceOf(\DateTime::class, $kingdom->updated_at);
    }
}
