<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Controllers\SettingsController;
use sdo\Services\AdvisorService;
use sdo\Services\ApiService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Services\DiscordLinkService;
use sdo\Services\GameService;
use sdo\Services\SettingsService;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;

class SettingsControllerTest extends TestCase
{
    private SettingsController $controller;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $gameService = $this->createMock(GameService::class);
        $advisorService = $this->createMock(AdvisorService::class);
        $configService = $this->createMock(ConfigService::class);
        
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $dominionRepo = $this->createMock(DominionRepositoryInterface::class);
        $unitRepo = $this->createMock(UnitRepositoryInterface::class);
        $structureRepo = $this->createMock(StructureRepositoryInterface::class);

        $this->authService = new class(
            $configService,
            $userRepo,
            $dominionRepo,
            $unitRepo,
            $structureRepo
        ) extends AuthService {
            public function __construct($c, $u, $d, $un, $s)
            {
                parent::__construct($c, $u, $d, $un, $s);
            }

            public function isLoggedIn(array $session): bool
            {
                return false;
            }
        };
        $settingsService = $this->createMock(SettingsService::class);
        $apiService = $this->createMock(ApiService::class);
        $discordLinkService = $this->createMock(DiscordLinkService::class);

        $this->controller = new SettingsController(
            $gameService,
            $advisorService,
            $configService,
            $this->authService,
            $settingsService,
            $apiService,
            $discordLinkService
        );
    }

    public function testCreateDiscordLinkCodeRejectsAnonymousRequests(): void
    {
        $_SESSION = [];

        $json = $this->controller->createDiscordLinkCode();
        $response = json_decode($json, true);

        $this->assertSame(401, http_response_code());
        $this->assertFalse($response['success']);
        $this->assertSame('Unauthorized.', $response['message']);
    }
}
