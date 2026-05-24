<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use sdo\Models\DiscordAccountLink;
use sdo\Models\DiscordLinkChallenge;
use sdo\Models\User;
use sdo\Services\DiscordLinkService;

class DiscordLinkServiceTest extends TestCase
{
    private DiscordLinkService $service;

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

        $redis = $this->createMock(Client::class);
        $this->service = new DiscordLinkService($redis);
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

        Capsule::schema()->create('discord_account_links', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->unique();
            $table->string('discord_user_id', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->dateTime('linked_at');
            $table->dateTime('unlinked_at')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('discord_link_challenges', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function testCreateChallengeForUser(): void
    {
        $user = User::create([
            'username' => 'cmdr',
            'email' => 'cmdr@example.com',
            'password' => 'password123',
        ]);

        $result = $this->service->createChallengeForUser((int)$user->id);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('SDO-', $result['link_code']);

        $stored = DiscordLinkChallenge::first();
        $this->assertNotNull($stored);
        $this->assertSame((int)$user->id, (int)$stored->user_id);
        $this->assertNotNull($stored->expires_at);
    }

    public function testProcessLinkRequestedCreatesAccountLink(): void
    {
        $user = User::create([
            'username' => 'cmdr2',
            'email' => 'cmdr2@example.com',
            'password' => 'password123',
        ]);

        $challenge = $this->service->createChallengeForUser((int)$user->id);

        $requestEnvelope = [
            'type' => 'action',
            'source' => 'bot',
            'schema_version' => 1,
            'correlation_id' => 'corr-link-1',
            'occurred_at' => gmdate('Y-m-d\\TH:i:s\\Z'),
            'payload' => [
                'action_name' => 'account_link.requested',
                'discord_user_id' => '123456789',
                'link_code' => $challenge['link_code'],
            ],
            'destination_kind' => 'internal',
            'discord_user_id' => '123456789',
        ];

        $result = $this->service->processActionEnvelope($requestEnvelope);

        $this->assertIsArray($result);
        $this->assertSame('account_link.completed', $result['payload']['action_name']);
        $this->assertTrue($result['payload']['linked']);

        $link = DiscordAccountLink::where('discord_user_id', '123456789')->first();
        $this->assertNotNull($link);
        $this->assertSame((int)$user->id, (int)$link->user_id);
    }

    public function testProcessLinkRequestedRejectsInvalidCode(): void
    {
        User::create([
            'username' => 'cmdr3',
            'email' => 'cmdr3@example.com',
            'password' => 'password123',
        ]);

        $requestEnvelope = [
            'type' => 'action',
            'source' => 'bot',
            'schema_version' => 1,
            'correlation_id' => 'corr-link-invalid',
            'occurred_at' => gmdate('Y-m-d\\TH:i:s\\Z'),
            'payload' => [
                'action_name' => 'account_link.requested',
                'discord_user_id' => '99887766',
                'link_code' => 'SDO-INVALID',
            ],
            'destination_kind' => 'internal',
            'discord_user_id' => '99887766',
        ];

        $result = $this->service->processActionEnvelope($requestEnvelope);

        $this->assertIsArray($result);
        $this->assertSame('account_link.rejected', $result['payload']['action_name']);
        $this->assertSame('invalid_or_expired_code', $result['payload']['reason']);
        $this->assertFalse($result['payload']['linked']);
    }

    public function testProcessUnlinkRequestedRemovesLink(): void
    {
        $user = User::create([
            'username' => 'cmdr4',
            'email' => 'cmdr4@example.com',
            'password' => 'password123',
        ]);

        DiscordAccountLink::create([
            'user_id' => $user->id,
            'discord_user_id' => '7777',
            'is_active' => true,
            'linked_at' => date('Y-m-d H:i:s'),
        ]);

        $requestEnvelope = [
            'type' => 'action',
            'source' => 'bot',
            'schema_version' => 1,
            'correlation_id' => 'corr-unlink',
            'occurred_at' => gmdate('Y-m-d\\TH:i:s\\Z'),
            'payload' => [
                'action_name' => 'account_link.removal_requested',
                'discord_user_id' => '7777',
            ],
            'destination_kind' => 'internal',
            'discord_user_id' => '7777',
        ];

        $result = $this->service->processActionEnvelope($requestEnvelope);
        $this->assertSame('account_link.removal_completed', $result['payload']['action_name']);
        $this->assertFalse($result['payload']['linked']);

        $link = DiscordAccountLink::where('discord_user_id', '7777')->first();
        $this->assertNotNull($link);
        $this->assertFalse((bool)$link->is_active);
    }

    public function testProcessLinkRequestedReactivatesInactiveLink(): void
    {
        $user = User::create([
            'username' => 'cmdr5',
            'email' => 'cmdr5@example.com',
            'password' => 'password123',
        ]);

        DiscordAccountLink::create([
            'user_id' => $user->id,
            'discord_user_id' => '2222',
            'is_active' => false,
            'linked_at' => date('Y-m-d H:i:s', time() - 3600),
            'unlinked_at' => date('Y-m-d H:i:s', time() - 1800),
        ]);

        $challenge = $this->service->createChallengeForUser((int)$user->id);
        $requestEnvelope = [
            'type' => 'action',
            'source' => 'bot',
            'schema_version' => 1,
            'correlation_id' => 'corr-link-reactivate',
            'occurred_at' => gmdate('Y-m-d\\TH:i:s\\Z'),
            'payload' => [
                'action_name' => 'account_link.requested',
                'discord_user_id' => '2222',
                'link_code' => $challenge['link_code'],
            ],
            'destination_kind' => 'internal',
            'discord_user_id' => '2222',
        ];

        $result = $this->service->processActionEnvelope($requestEnvelope);

        $this->assertIsArray($result);
        $this->assertSame('account_link.completed', $result['payload']['action_name']);
        $this->assertTrue($result['payload']['linked']);

        $linkRows = DiscordAccountLink::where('user_id', $user->id)->get();
        $this->assertCount(1, $linkRows);

        $link = $linkRows->first();
        $this->assertNotNull($link);
        $this->assertTrue((bool)$link->is_active);
        $this->assertNull($link->unlinked_at);
        $this->assertSame('2222', $link->discord_user_id);
    }
}
