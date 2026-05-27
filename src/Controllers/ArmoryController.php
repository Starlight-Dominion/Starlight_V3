<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ArmoryService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Dto\Armory\ArmoryActionRequest;

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

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
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
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $request = new ArmoryActionRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->armoryService->buyItem($dominion->id, $request->item_id, $request->quantity);
        });
    }

    public function sell(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $request = new ArmoryActionRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->armoryService->sellItem($dominion->id, $request->item_id, $request->quantity);
        });
    }

    public function upgrade(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->armoryService->upgradeArmory($dominion->id);
        });
    }

    public function toggleEquip(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            $itemId = (int)($_POST['item_id'] ?? 0);
            return $this->armoryService->toggleEquip($dominion->id, $itemId);
        });
    }

    public function upgradeItem(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $request = new ArmoryActionRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->armoryService->upgradeItem($dominion->id, $request->item_id, $request->quantity);
        });
    }
}
