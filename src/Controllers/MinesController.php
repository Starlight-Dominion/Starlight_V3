<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\MinesService;
use sdo\Services\AuthService;
use sdo\Services\UntrainingService;
use sdo\Services\ConfigService;

class MinesController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private MinesService $minesService,
        private UntrainingService $untrainingService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

public function index(): string
{
if (!$this->authService->isLoggedIn($_SESSION)) {
$this->redirect('/login');
}

$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$this->untrainingService->releaseHeldCitizens($kingdom->id);

return $this->render('mines/index', [
'title' => 'The Deep Mines',
'minesConfig' => $this->minesService->getMinesConfig(),
'totalProduction' => $this->minesService->calculateCurrentProduction($kingdom->toArray()),
'message' => $_SESSION['message'] ?? null,
]);
}

public function assign(): void
{
$qty = (int)($_POST['quantity'] ?? 0);
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->assignMiners($kingdom->id, $qty);
$this->redirect('/structures/mines');
}

public function unassign(): void
{
$qty = (int)($_POST['quantity'] ?? 0);
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->unassignMiners($kingdom->id, $qty);
$this->redirect('/structures/mines');
}

public function upgradeCurrentMine(): void
{
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->upgradeCurrentMine($kingdom->id);
$this->redirect('/structures/mines');
}

public function upgradeMineTier(): void
{
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->upgradeMineTier($kingdom->id);
$this->redirect('/structures/mines');
}
}