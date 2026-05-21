<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\BankService;
use sdo\Services\AuthService;

class BankController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private BankService $bankService,
        private AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);

        $history = $this->bankService->getTransactions($dominion->id, $page, $limit);

        return $this->render('bank/index', [
            'title' => 'The Iron Bank',
            'transactions' => $history['transactions'],
            'pagination' => $history['pagination']
        ]);
    }

    public function deposit(): string
    {
        header('Content-Type: application/json');
        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $amount = (int)($_POST['amount'] ?? 0);

        try {
            return json_encode($this->bankService->deposit($dominion->id, $amount));
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function withdraw(): string
    {
        header('Content-Type: application/json');
        $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        $amount = (int)($_POST['amount'] ?? 0);

        try {
            return json_encode($this->bankService->withdraw($dominion->id, $amount));
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}