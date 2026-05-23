<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\SpyService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class SpyController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private SpyService $spyService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $spyIntel = $this->spyService->getSpyIntel($dominion->id);
        $availableSpies = $this->spyService->getAvailableSpies($dominion->id);

        return $this->render('spy/index', [
            'title' => 'Intelligence Operations',
            'spyIntel' => $spyIntel,
            'availableSpies' => $availableSpies,
            'spyCount' => $availableSpies['available_spies'] ?? 0,
        ]);
    }

    public function executeReconnaissance(): void
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $targetId = (int)($_POST['target_id'] ?? 0);
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

        try {
            $result = $this->spyService->executeReconnaissance($dominion->id, $targetId);
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
