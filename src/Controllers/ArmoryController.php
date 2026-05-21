<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ArmoryService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class ArmoryController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private ArmoryService $armoryService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $data = $this->armoryService->getArmoryData($dominion->id);

        return $this->render('armory/index', [
            'title' => 'Sector Armory',
            'loadouts' => $data['loadouts'],
            'armory_level' => $data['armory_level'],
            'upgrade_cost' => $data['upgrade_cost']
        ]);
    }

    public function buy(): string
    {
        header('Content-Type: application/json');
        
        $validator = \sdo\Infrastructure\Validator::make($_POST, [
            'item_id' => 'required|numeric',
            'quantity' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return json_encode(['success' => false, 'message' => 'Invalid tactical parameters.']);
        }

        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $itemId = (int)$_POST['item_id'];
        $qty = (int)$_POST['quantity'];

        if ($qty <= 0) {
            return json_encode(['success' => false, 'message' => 'Quantity must be positive.']);
        }

        try {
            return json_encode($this->armoryService->buyItem($dominion->id, $itemId, $qty));
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sell(): string
    {
        header('Content-Type: application/json');
        
        $validator = \sdo\Infrastructure\Validator::make($_POST, [
            'item_id' => 'required|numeric',
            'quantity' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return json_encode(['success' => false, 'message' => 'Invalid tactical parameters.']);
        }

        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $itemId = (int)$_POST['item_id'];
        $qty = (int)$_POST['quantity'];

        if ($qty <= 0) {
            return json_encode(['success' => false, 'message' => 'Quantity must be positive.']);
        }

        try {
            return json_encode($this->armoryService->sellItem($dominion->id, $itemId, $qty));
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function upgrade(): string
    {
        header('Content-Type: application/json');
        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            return json_encode($this->armoryService->upgradeArmory($dominion->id));
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}