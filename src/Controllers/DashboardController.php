<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\MinesService;
use sdo\Services\TacticalService;

class DashboardController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private AuthService $authService,
        private MinesService $minesService,
        private TacticalService $tacticalService
    ) {
        parent::__construct($gameService, $advisorService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        // Fetch the Dominion record (replaces Kingdom)
        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        // GUARD CLAUSE: If dominion is missing, the neural link is corrupted
        if (!$dominion) {
            session_destroy();
            $this->redirect('/login?error=dominion_not_found');
        }

        $minesConfig = $this->minesService->getMinesConfig();
        
        // Pass the model array to services
        $dominionData = $dominion->toArray();
        $productionMines = (int)$this->minesService->calculateCurrentProduction($dominionData);
        $tactical = $this->tacticalService->getTacticalOverview($dominion->id);

        return $this->render('dashboard/index', [
            'title' => 'Sector Dashboard',
            'production_base' => (int)($minesConfig['base_gold_per_tick'] ?? 100),
            'production_mines' => $productionMines,
            'production_total' => (int)($minesConfig['base_gold_per_tick'] ?? 100) + $productionMines,
            'tactical' => $tactical
        ]);
    }
}