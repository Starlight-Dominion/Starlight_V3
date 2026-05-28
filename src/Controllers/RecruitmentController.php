<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;
use sdo\Services\RecruitmentService;
use sdo\Dto\Recruitment\RecruitmentClickRequest;

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
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->recruitmentService->startSession($dominion->id);
        });
    }

    public function click(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $request = new RecruitmentClickRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->recruitmentService->processClick($dominion->id, $request->session_id);
        });
    }
}
