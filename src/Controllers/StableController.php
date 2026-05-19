<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\StablingService;
use sdo\Services\AuthService;

class StableController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private StablingService $stablingService,
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
        $stableData = $this->stablingService->getStableData($kingdom->id);
        $unitDetails = $this->stablingService->getStableUnitDetails();

        return $this->render('stable/index', [
            'title' => 'Kingdom Stable',
            'stableData' => $stableData,
            'unitDetails' => $unitDetails,
            'message' => $_SESSION['message'] ?? null,
        ]);
    }

    public function stableUnit(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $unitType = (string)($_POST['unit_type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->stablingService->stableUnits($kingdom->id, $unitType, $quantity);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function upgrade(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->stablingService->upgradeStable($kingdom->id);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
