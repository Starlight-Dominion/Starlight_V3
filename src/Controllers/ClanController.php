<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;

class ClanController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function home(): string
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        return $this->render('clan/home', ['title' => 'Clan Home']);
    }

    public function bank(): string
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        return $this->render('clan/bank', ['title' => 'Clan Bank']);
    }
}
