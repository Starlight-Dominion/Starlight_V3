<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\ArmoryService;
use sdo\Services\FoundationService;
use sdo\Services\MinesService;

class StructureController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private AuthService $authService,
        private ArmoryService $armoryService,
        private FoundationService $foundationService,
        private MinesService $minesService
    ) {
        parent::__construct($gameService, $advisorService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        
        $foundationData = $this->foundationService->getFoundationData($kingdom->id);
        $armoryData = $this->armoryService->getArmoryData($kingdom->id);
        $stableData = $this->stablingService->getStableData($kingdom->id);
        
        $structures = [
            [
                'name' => 'Foundation',
                'level' => $kingdom->foundation_level,
                'max_level' => 10,
                'description' => $foundationData['current_tier']['description'] ?? 'The base of your kingdom.',
                'status' => 'Stable',
                'link' => '/structures/foundation',
                'can_upgrade' => $foundationData['next_tier'] && $foundationData['player_level'] >= $foundationData['next_tier']['player_level_req']
            ],
            [
                'name' => 'Royal Armory',
                'level' => $kingdom->armory_level,
                'max_level' => 10,
                'description' => 'Unlocks powerful weapons and armor.',
                'status' => $kingdom->armory_level < $kingdom->foundation_level ? 'Upgrade Available' : 'Capped by Foundation',
                'link' => '/structures/armory',
                'can_upgrade' => $kingdom->armory_level < $kingdom->foundation_level && $armoryData['upgrade_cost'] !== null
            ],
            [
                'name' => 'Royal Stable',
                'level' => $kingdom->stable_level,
                'max_level' => 30,
                'description' => 'Houses and activates your trained units.',
                'status' => $kingdom->stable_level < ($kingdom->foundation_level * 3) ? 'Upgrade Available' : 'Capped by Foundation',
                'link' => '/structures/stable',
                'can_upgrade' => $kingdom->stable_level < ($kingdom->foundation_level * 3) && $stableData['upgrade_cost'] !== null
            ],
            [
                'name' => 'Deep Mines',
                'level' => $kingdom->mine_tier,
                'max_level' => 5, // Assuming 5 tiers based on current MinesService
                'description' => 'Extracts gold from the earth.',
                'status' => 'Operational',
                'link' => '/structures/mines',
                'can_upgrade' => true 
            ]
        ];

        return $this->render('structures/index', [
            'title' => 'Kingdom Structures',
            'structures' => $structures,
            'player_level' => $foundationData['player_level']
        ]);
    }
}
