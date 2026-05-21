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

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $status = $this->recruitmentService->getStatus($kingdom->id);

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

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->recruitmentService->startSession($kingdom->id);
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
        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

        try {
            $res = $this->recruitmentService->processClick($kingdom->id, $sessionId);
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
