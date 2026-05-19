<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ArmoryService;
use sdo\Services\AuthService;

class ArmoryController extends BaseController
{
    private ArmoryService $armoryService;
    private AuthService $authService;

    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ArmoryService $armoryService,
        AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService);
        $this->armoryService = $armoryService;
        $this->authService = $authService;
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $data = $this->armoryService->getArmoryData($kingdom->id);

        return $this->render('armory/index', [
            'title' => 'The Royal Armory',
            'loadouts' => $data['loadouts'],
            'armory_level' => $data['armory_level'],
            'upgrade_cost' => $data['upgrade_cost'],
            'message' => $_SESSION['message'] ?? null,
        ]);
    }

    public function upgrade(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->armoryService->upgradeArmory($kingdom->id);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function buy(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        try {
            $res = $this->armoryService->buyItem($kingdom->id, $itemId, $qty);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sell(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        try {
            $res = $this->armoryService->sellItem($kingdom->id, $itemId, $qty);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function toggleHide(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $itemId = (int)($_POST['item_id'] ?? 0);

        try {
            $res = $this->armoryService->toggleHideItem($kingdom->id, $itemId);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
