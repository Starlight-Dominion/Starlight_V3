<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use sdo\Controllers\ApiController;
use sdo\Models\DiscordAccountLink;
use sdo\Models\User;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\BattlefieldService;
use sdo\Services\ConfigService;
use sdo\Services\DiscordLinkService;
use sdo\Services\FoundationService;
use sdo\Services\GameService;

class DiscordLinkApiStatusTest extends TestCase
{
    private ApiController $controller;

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

        $this->createTables();

        $gameService = $this->createMock(GameService::class);
        $advisorService = $this->createMock(AdvisorService::class);
        $configService = $this->createMock(ConfigService::class);
        $authService = $this->createMock(AuthService::class);
        $battlefieldService = $this->createMock(BattlefieldService::class);
        $foundationService = $this->createMock(FoundationService::class);
        $discordLinkService = new DiscordLinkService();

        $this->controller = new ApiController(
            $gameService,
            $advisorService,
            $configService,
            $authService,
            $battlefieldService,
            $foundationService,
            $discordLinkService,
        );
    }

    private function createTables(): void
    {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Capsule::schema()->create('discord_account_links', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('discord_user_id', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->dateTime('linked_at');
            $table->dateTime('unlinked_at')->nullable();
            $table->timestamps();
        });
    }

    public function testDiscordLinkStatusReturnsLinkedData(): void
    {
        $user = User::create([
            'username' => 'cmdr',
            'email' => 'cmdr@example.com',
            'password' => 'pass12345',
        ]);

        DiscordAccountLink::create([
            'user_id' => $user->id,
            'discord_user_id' => '12345',
            'is_active' => true,
            'linked_at' => date('Y-m-d H:i:s'),
        ]);

        $_GET['discord_user_id'] = '12345';

        $json = $this->controller->discordLinkStatus();
        $response = json_decode($json, true);

        $this->assertTrue($response['success']);
        $this->assertTrue($response['data']['linked']);
        $this->assertSame((string)$user->id, $response['data']['sdo_user_id']);
    }

    public function testDiscordLinkStatusReturnsNotLinkedData(): void
    {
        $_GET['discord_user_id'] = '99999';

        $json = $this->controller->discordLinkStatus();
        $response = json_decode($json, true);

        $this->assertTrue($response['success']);
        $this->assertFalse($response['data']['linked']);
        $this->assertNull($response['data']['sdo_user_id']);
    }

    public function testDiscordLinkStatusRequiresDiscordUserID(): void
    {
        unset($_GET['discord_user_id']);

        $json = $this->controller->discordLinkStatus();
        $response = json_decode($json, true);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Missing discord_user_id', $response['message']);
    }
}
