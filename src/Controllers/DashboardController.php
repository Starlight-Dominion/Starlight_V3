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
    private AuthService $authService;
    private MinesService $minesService;
    private TacticalService $tacticalService;

    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        AuthService $authService,
        MinesService $minesService,
        TacticalService $tacticalService
    ) {
        parent::__construct($gameService, $advisorService);
        $this->authService = $authService;
        $this->minesService = $minesService;
        $this->tacticalService = $tacticalService;
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $minesConfig = $this->minesService->getMinesConfig();
        $productionMines = (int)$this->minesService->calculateCurrentProduction($kingdom->toArray());
        $tactical = $this->tacticalService->getTacticalOverview($kingdom->id);

        return $this->render('dashboard/index', [
            'title' => 'Kingdom Dashboard',
            'production_base' => (int)($minesConfig['base_gold_per_tick'] ?? 100),
            'production_mines' => $productionMines,
            'production_total' => (int)($minesConfig['base_gold_per_tick'] ?? 100) + $productionMines,
            'tactical' => $tactical
        ]);
    }
}