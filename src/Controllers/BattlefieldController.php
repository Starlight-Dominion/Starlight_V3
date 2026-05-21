<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\BattlefieldService;
use sdo\Services\AuthService;

class BattlefieldController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private BattlefieldService $battlefieldService,
        private AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService);
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
        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        if (!$kingdom) {
            echo json_encode(['success' => false, 'message' => 'Kingdom not found.']);
            return;
        }

        try {
            $result = $this->battlefieldService->executeAttack($kingdom->id, $targetId, $turns);
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
        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $log = $this->battlefieldService->getBattleLog($id);

        // Security check: Must be attacker or defender
        if (!$log || ((int)$log->attacker_id !== $kingdom->id && (int)$log->defender_id !== $kingdom->id)) {
            $this->redirect('/combat/battlefield');
        }

        return $this->render('battlefield/report', [
            'title' => 'Battle Report',
            'log' => $log,
            'attacker' => $this->gameService->getKingdomById((int)$log->attacker_id),
            'defender' => $this->gameService->getKingdomById((int)$log->defender_id),
        ]);
    }
}
