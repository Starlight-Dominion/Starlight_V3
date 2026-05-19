<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;

class PageController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService
    ) {
        parent::__construct($gameService, $advisorService);
    }

    public function about(): string
    {
        return $this->render('pages/about', [
            'title' => 'The Manual - Shadowreign'
        ]);
    }

    public function terms(): string
    {
        return $this->render('pages/terms', [
            'title' => 'Covenant of Shadows - Terms'
        ]);
    }

    public function contact(): string
    {
        return $this->render('pages/contact', [
            'title' => 'Signal the High Command - Contact'
        ]);
    }
}