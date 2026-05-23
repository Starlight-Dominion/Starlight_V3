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

$dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
$this->untrainingService->releaseHeldCitizens($dominion->id);

return $this->render('mines/index', [
'title' => 'The Deep Mines',
'minesConfig' => $this->minesService->getMinesConfig(),
'totalProduction' => $this->minesService->calculateCurrentProduction($dominion),
'message' => $_SESSION['message'] ?? null,
]);
}

public function assign(): void
{
$qty = (int)($_POST['quantity'] ?? 0);
$dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->assignMiners($dominion->id, $qty);
$this->redirect('/structures/mines');
}

public function unassign(): void
{
$qty = (int)($_POST['quantity'] ?? 0);
$dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->unassignMiners($dominion->id, $qty);
$this->redirect('/structures/mines');
}

public function upgradeCurrentMine(): void
{
$dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->upgradeCurrentMine($dominion->id);
$this->redirect('/structures/mines');
}

public function upgradeMineTier(): void
{
$dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->minesService->upgradeMineTier($dominion->id);
$this->redirect('/structures/mines');
}
}