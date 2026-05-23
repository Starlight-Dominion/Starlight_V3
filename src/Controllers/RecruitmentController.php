<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;
use sdo\Services\RecruitmentService;

class RecruitmentController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private RecruitmentService $recruitmentService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        $status = $this->recruitmentService->getStatus($dominion->id);

        return $this->render('combat/recruit', [
            'title' => 'Civilian Mobilization',
            'status' => $status
        ]);
    }

    public function start(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->recruitmentService->startSession($dominion->id);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function click(): string
    {
        header('Content-Type: application/json');
        if (!$this->authService->isLoggedIn($_SESSION)) {
            return json_encode(['success' => false, 'message' => 'Unauthorized']);
        }

        $sessionId = (int)($_POST['session_id'] ?? 0);
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->recruitmentService->processClick($dominion->id, $sessionId);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
