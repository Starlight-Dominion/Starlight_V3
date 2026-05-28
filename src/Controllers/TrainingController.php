<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\TrainingService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Dto\Combat\TrainingRequest;

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
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }

            $request = new TrainingRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

            return $this->trainingService->train($dominion->id, $request->unit_type, $request->quantity);
        });
    }
}
