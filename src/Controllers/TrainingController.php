<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\TrainingService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class TrainingController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private TrainingService $trainingService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        return $this->render('training/index', [
            'title' => 'Training Grounds',
            'units' => $this->trainingService->getUnitConfig(),
            'message' => $_SESSION['message'] ?? null,
        ]);
    }

    public function train(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $unitType = (string)($_POST['unit_type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->trainingService->train($kingdom->id, $unitType, $quantity);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
