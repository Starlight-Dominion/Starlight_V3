<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Models\RecruitmentSession;
use sdo\Services\RecruitmentService;
use sdo\Services\ConfigService;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;

class RecruitmentServiceTest extends TestCase
{
    private RecruitmentService $service;
    private $configMock;
    private $logMock;

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
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(10000);
            $table->integer('citizens')->default(500);
            $table->integer('turns')->default(100);
            $table->timestamps();
        });

        Capsule::schema()->create('recruitment_sessions', function ($table) {
            $table->increments('id');
            $table->integer('dominion_id')->unsigned();
            $table->integer('clicks_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });

        $this->configMock = $this->createMock(ConfigService::class);
        $this->logMock = $this->createMock(LogService::class);
        $this->service = new RecruitmentService($this->configMock, $this->logMock);
    }

    private function createTestDominion(): Dominion
    {
        $user = User::create([
            'username' => 'testuser' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        return $user->dominion()->create([
            'name' => 'Test Dominion' . uniqid(),
        ]);
    }

    public function testProcessClickSuccess(): void
    {
        $dominion = $this->createTestDominion();
        $session = RecruitmentSession::create([
            'dominion_id' => $dominion->id,
            'clicks_count' => 0,
            'is_active' => true
        ]);

        $this->configMock->method('get')->willReturn(150);

        $result = $this->service->processClick($dominion->id, $session->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);

        $dominion->refresh();
        $this->assertEquals(501, $dominion->citizens);

        $session->refresh();
        $this->assertEquals(1, $session->clicks_count);
    }

    public function testProcessClickCompletion(): void
    {
        $dominion = $this->createTestDominion();
        $session = RecruitmentSession::create([
            'dominion_id' => $dominion->id,
            'clicks_count' => 149,
            'is_active' => true
        ]);

        $this->configMock->method('get')->willReturn(150);

        $result = $this->service->processClick($dominion->id, $session->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(150, $result['count']);

        $session->refresh();
        $this->assertFalse($session->is_active);
        $this->assertNotNull($session->completed_at);
    }
}
