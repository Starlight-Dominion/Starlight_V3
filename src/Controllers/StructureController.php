<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\FoundationService;

class StructureController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private AuthService $authService,
        private FoundationService $foundationService
    ) {
        parent::__construct($gameService, $advisorService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
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
            'title' => 'Kingdom Structures',
            'structures' => $structureList,
            'player_level' => $dominion->getPlayerLevel()
        ]);
    }
}