<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\TacticalService;
use sdo\Services\ConfigService;

class DashboardController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private TacticalService $tacticalService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

        if (!$dominion) {
            session_destroy();
            $this->redirect('/login?error=dominion_not_found');
        }

        // Calculate production from structural multipliers
        $multiplier = $this->gameService->getEconomyMultiplier($dominion->id);
        $totalIncome = (int)floor(GameService::BASE_INCOME * $multiplier);
        $bonusCredits = $totalIncome - GameService::BASE_INCOME;

        $tactical = $this->tacticalService->getTacticalOverview($dominion->id);

        return $this->render('dashboard/index', [
            'title' => 'Sector Dashboard',
            'production_base' => GameService::BASE_INCOME,
            'production_mines' => $bonusCredits, // Maps to "Structural Bonus" in the UI
            'production_total' => $totalIncome,
            'tactical' => $tactical
        ]);
    }
}
