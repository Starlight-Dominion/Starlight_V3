<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;

class HomeController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private UserRepositoryInterface $userRepository,
        private CombatRepositoryInterface $combatRepository
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    /**
     * Entry point for the landing page.
     */
    public function index(): string
    {
        // If already logged in, redirect to Dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $playerCount = $this->userRepository->count();
        $tickInterval = (int)$this->configService->get('tick_interval_seconds', 900);
        
        $battles24h = $this->combatRepository->countRecentBattles(24);

        return $this->render('home', [
            'title' => 'Starlight Dominion | Strategic Sector Command',
            'world_stats' => [
                'players' => $playerCount,
                'battles_24h' => $battles24h,
                'tick_interval' => $tickInterval
            ]
        ]);
    }
}
