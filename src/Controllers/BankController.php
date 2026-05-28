<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\BankService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Dto\Bank\BankTransactionRequest;

class BankController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private BankService $bankService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
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
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $request = new BankTransactionRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->bankService->deposit($dominion->id, $request->amount);
        });
    }

    public function withdraw(): string
    {
        return $this->jsonResponse(function() {
            if (!$this->authService->isLoggedIn($_SESSION)) {
                throw new \Exception('Unauthorized');
            }
            $request = new BankTransactionRequest($_POST);
            $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
            return $this->bankService->withdraw($dominion->id, $request->amount);
        });
    }
}
