<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\BattlefieldService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class BattlefieldController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private BattlefieldService $battlefieldService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        return $this->render('battlefield/index', [
            'title' => 'The Battlefield',
            'players' => $this->battlefieldService->getBattlefieldList()
        ]);
    }

    public function attack(): void
    {
        header('Content-Type: application/json');

        if (!$this->authService->isLoggedIn($_SESSION)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $targetId = (int)($_POST['target_id'] ?? 0);
        $turns = (int)($_POST['turns'] ?? 1);
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

        if (!$dominion) {
            echo json_encode(['success' => false, 'message' => 'Sector not found.']);
            return;
        }

        try {
            $result = $this->battlefieldService->executeAttack($dominion->id, $targetId, $turns);
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function report(array $vars): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $id = (int)($vars['id'] ?? 0);
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $log = $this->battlefieldService->getBattleLog($id);

        // Security check: Must be attacker or defender
        if (!$log || ((int)$log->attacker_id !== $dominion->id && (int)$log->defender_id !== $dominion->id)) {
            $this->redirect('/combat/battlefield');
        }

        return $this->render('battlefield/report', [
            'title' => 'Battle Report',
            'log' => $log,
            'attacker' => $this->gameService->getDominionById((int)$log->attacker_id),
            'defender' => $this->gameService->getDominionById((int)$log->defender_id),
        ]);
    }
}
