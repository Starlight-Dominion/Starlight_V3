<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\UpgradesService;
use sdo\Services\AuthService;

class UpgradesController extends BaseController
{
public function __construct(
GameService $gameService,
AdvisorService $advisorService,
private UpgradesService $upgradesService,
private AuthService $authService
) {
parent::__construct($gameService, $advisorService);
}

public function index(): string
{
if (!$this->authService->isLoggedIn($_SESSION)) $this->redirect('/login');

$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$upgradeData = $this->upgradesService->getUpgradeData($kingdom->id);

return $this->render('upgrades/index', [
'title' => 'Empire Upgrades',
'housingConfig' => $upgradeData['housing_config'],
'mercenaryMarketConfig' => $upgradeData['mercenary_market_config'],
'message' => $_SESSION['message'] ?? null,
]);
}

public function upgradeHousing(): void
{
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->upgradesService->upgradeHousing($kingdom->id);
$this->redirect('/structures/upgrades');
}

public function upgradeMercenaryMarket(): void
{
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->upgradesService->upgradeMercenaryMarket($kingdom->id);
$this->redirect('/structures/upgrades');
}
}