<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\BattlefieldService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Dto\Combat\AttackRequest;

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

    public function attack(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }

            $request = new AttackRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

            if (!$dominion) {
                throw new \Exception('Sector not found.');
            }

            return $this->battlefieldService->executeAttack($dominion->id, $request->target_id, $request->turns);
        });
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
