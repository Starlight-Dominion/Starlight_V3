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
        $incomeBreakdown = $this->gameService->getIncomeBreakdown($dominion->id);
        $tactical = $this->tacticalService->getTacticalOverview($dominion->id);

        return $this->render('dashboard/index', [
            'title' => 'Sector Dashboard',
            'income_breakdown' => $incomeBreakdown,
            'tactical' => $tactical
        ]);
    }
}
