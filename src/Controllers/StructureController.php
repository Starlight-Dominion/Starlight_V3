<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\FoundationService;
use sdo\Services\ConfigService;

class StructureController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private FoundationService $foundationService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $state = $this->foundationService->getFoundationState($dominion->id);
        
        $structureList = [];
        foreach ($state['structures'] as $slug => $data) {
            $structureList[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'level' => $data['current_level'],
                'max_level' => $data['max_level'],
                'description' => $data['description'],
                'status' => $data['current_level'] > 0 ? 'Operational' : 'Awaiting Construction',
                'link' => '/structures/foundation', // Generic management UI
                'can_upgrade' => $data['next_upgrade'] !== null && $dominion->credits >= $data['next_upgrade']->cost
            ];
        }

        return $this->render('structures/index', [
            'title' => 'Dominion Structures',
            'structures' => $structureList,
            'player_level' => $this->gameService->calculateLevel((int)$dominion->xp)
        ]);
    }
}
