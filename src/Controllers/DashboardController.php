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

        // Use centralized growth reporting
        $baseCredits = (int)$this->configService->get('baseline_credits_per_tick', 100);
        $totalIncome = $this->gameService->getTotalIncome($dominion->id);
        $bonusCredits = $totalIncome - $baseCredits;

        $tactical = $this->tacticalService->getTacticalOverview($dominion->id);

        return $this->render('dashboard/index', [
            'title' => 'Sector Dashboard',
            'production_base' => $baseCredits,
            'production_mines' => $bonusCredits, // Maps to "Structural Bonus" in the UI
            'production_total' => $totalIncome,
            'tactical' => $tactical
        ]);
    }
}
