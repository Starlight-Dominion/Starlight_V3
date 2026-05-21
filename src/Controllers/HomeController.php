<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;

class HomeController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService
    ) {
        parent::__construct($gameService, $advisorService, $configService);
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

        // world_stats dummy data - replace with Repository calls later
        return $this->render('home', [
            'title' => 'Shadowreign | Strategic Military RPG',
            'world_stats' => [
                'players' => 1240,
                'battles_24h' => 842
            ]
        ]);
    }
}