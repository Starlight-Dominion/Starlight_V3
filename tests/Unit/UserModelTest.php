<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\User;
use sdo\Models\Kingdom;
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

        Capsule::schema()->create('kingdoms', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('kingdom_name');
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

    public function testKingdomRelationship(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $kingdom = $user->kingdom()->create([
            'kingdom_name' => 'Test Kingdom',
        ]);

        $this->assertInstanceOf(Kingdom::class, $user->kingdom);
        $this->assertEquals('Test Kingdom', $user->kingdom->kingdom_name);
        $this->assertEquals($user->id, $kingdom->user_id);
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
