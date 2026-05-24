<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\User;
use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserModelTest extends TestCase
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

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->dateTime('last_untrained')->nullable();
            $table->timestamps();
        });
    }

    public function testSetPasswordAttributeHashesPassword(): void
    {
        $user = new User();
        $user->password = 'testpassword123';

        $this->assertNotEquals('testpassword123', $user->password);
        $this->assertTrue(password_verify('testpassword123', $user->password));
    }

    public function testSetPasswordAttributeVerifyPassword(): void
    {
        $user = new User();
        $user->password = 'mypassword';

        $this->assertTrue(password_verify('mypassword', $user->password));
        $this->assertFalse(password_verify('wrongpassword', $user->password));
    }

    public function testHiddenPropertyExcludesPassword(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function testDominionRelationship(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $dominion = $user->dominion()->create([
            'name' => 'Test Dominion',
        ]);

        $this->assertInstanceOf(Dominion::class, $user->dominion);
        $this->assertEquals('Test Dominion', $user->dominion->name);
        $this->assertEquals($user->id, $dominion->user_id);
    }

    public function testIsBotCastToBoolean(): void
    {
        $user = User::create([
            'username' => 'botuser',
            'email' => 'bot@example.com',
            'password' => 'password123',
            'is_bot' => true,
        ]);

        $this->assertIsBool($user->is_bot);
        $this->assertTrue($user->is_bot);
    }
}
