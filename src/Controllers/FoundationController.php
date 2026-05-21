<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\FoundationService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class FoundationController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        private FoundationService $foundationService,
        private AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService, $configService);
    }

    /**
     * Render the Structural Engineering Terminal
     */
    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $state = $this->foundationService->getFoundationState($dominion->id);

        return $this->render('foundation/index', [
            'title' => 'Structural Engineering',
            'dominion' => $state['dominion'],
            'structures' => $state['structures'],
            'repair_cost' => $state['repair_cost']
        ]);
    }

    /**
     * Generic JSON response helper for structural actions
     */
    private function jsonResponse(callable $action): string
    {
        header('Content-Type: application/json');
        try {
            $res = $action();
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Initialize Nano-Repair sequence
     */
    public function repair(): string
    {
        return $this->jsonResponse(fn() => $this->foundationService->repair(
            (int)$this->gameService->getKingdomByUserId((int)$_SESSION['user_id'])->id
        ));
    }

    /**
     * Initialize Structural Evolution (Upgrade)
     */
    public function upgrade(): string
    {
        return $this->jsonResponse(fn() => $this->foundationService->upgrade(
            (int)$this->gameService->getKingdomByUserId((int)$_SESSION['user_id'])->id,
            (int)($_POST['structure_id'] ?? 0)
        ));
    }
}