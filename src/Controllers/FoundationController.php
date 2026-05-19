<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\FoundationService;
use sdo\Services\AuthService;

class FoundationController extends BaseController
{
public function __construct(
GameService $gameService,
AdvisorService $advisorService,
private FoundationService $foundationService,
private AuthService $authService
) {
parent::__construct($gameService, $advisorService);
}

public function index(): string
{
    if (!$this->authService->isLoggedIn($_SESSION)) {
        $this->redirect('/login');
    }

    $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
    $data = $this->foundationService->getFoundationData($kingdom->id);

    return $this->render('foundation/index', [
        'title' => 'Kingdom Foundation',
        'player_level' => $data['player_level'],
        'currentTier' => $data['current_tier'],
        'nextTier' => $data['next_tier'],
        'allTiers' => $data['all_tiers'],
        'upgrades' => $data['upgrades'],
        'message' => $_SESSION['message'] ?? null,
    ]);
}

public function upgrade(): string
{
    header('Content-Type: application/json');
    if (!$this->authService->isLoggedIn($_SESSION)) {
        return json_encode(['success' => false, 'message' => 'Unauthorized']);
    }

    $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

    try {
        $res = $this->foundationService->upgradeFoundation($kingdom->id);
        return json_encode($res);
    } catch (\Exception $e) {
        return json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function purchaseUpgrade(): string
{
    header('Content-Type: application/json');
    if (!$this->authService->isLoggedIn($_SESSION)) {
        return json_encode(['success' => false, 'message' => 'Unauthorized']);
    }

    $key = (string)($_POST['upgrade_key'] ?? '');
    $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

    try {
        $res = $this->foundationService->purchaseUpgrade($kingdom->id, $key);
        return json_encode($res);
    } catch (\Exception $e) {
        return json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
}