<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;

class PageController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function about(): string
    {
        return $this->render('pages/about', [
            'title' => 'Command Manual - Starlight Dominion'
        ]);
    }

    public function rules(): string
    {
        $rules = $this->configService->get('official_rules', 'Rules transmission lost.');
        return $this->render('pages/rules', [
            'title' => 'Official Rules - Starlight Dominion',
            'content' => $rules
        ]);
    }

    public function terms(): string
    {
        return $this->render('pages/terms', [
            'title' => 'Sector Laws - Starlight Dominion'
        ]);
    }

    public function contact(): string
    {
        return $this->render('pages/contact', [
            'title' => 'Signal Uplink - Starlight Dominion'
        ]);
    }
}