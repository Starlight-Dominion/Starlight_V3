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
        AuthService $authService,
        private FoundationService $foundationService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    /**
     * Render the Structural Engineering Terminal
     */
    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $state = $this->foundationService->getFoundationState($dominion->id);

        return $this->render('foundation/index', [
            'title' => 'Structural Engineering',
            'dominion' => $state['dominion'],
            'structures' => $state['structures'],
            'repair_cost' => $state['repair_cost']
        ]);
    }

    /**
     * Initialize Nano-Repair sequence
     */
    public function repair(): string
    {
        return $this->jsonResponse(fn() => $this->foundationService->repair(
            (int)$this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->id
        ));
    }

    /**
     * Initialize Structural Evolution (Upgrade)
     */
    public function upgrade(): string
    {
        return $this->jsonResponse(fn() => $this->foundationService->upgrade(
            (int)$this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->id,
            (int)($_POST['structure_id'] ?? 0)
        ));
    }
}
