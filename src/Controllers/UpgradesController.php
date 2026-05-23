<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\UpgradesService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class UpgradesController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private UpgradesService $upgradesService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $upgradeData = $this->upgradesService->getUpgradeData($dominion->id);

        return $this->render('upgrades/index', [
            'title' => 'Empire Upgrades',
            'housingConfig' => $upgradeData['housing_config'],
            'mercenaryMarketConfig' => $upgradeData['mercenary_market_config'],
            'message' => $_SESSION['message'] ?? null,
        ]);
    }

    public function upgradeHousing(): void
    {
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $_SESSION['message'] = $this->upgradesService->upgradeHousing($dominion->id);
        $this->redirect('/structures/upgrades');
    }

    public function upgradeMercenaryMarket(): void
    {
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $_SESSION['message'] = $this->upgradesService->upgradeMercenaryMarket($dominion->id);
        $this->redirect('/structures/upgrades');
    }
}
