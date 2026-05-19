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

$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 10);

$history = $this->bankService->getTransactions($kingdom->id, $page, $limit);

return $this->render('bank/index', [
'title' => 'The Iron Bank',
'transactions' => $history['transactions'],
'pagination' => $history['pagination'],
'message' => $_SESSION['message'] ?? null,
]);
}

public function deposit(): void
{
$amount = (int)($_POST['amount'] ?? 0);
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->bankService->deposit($kingdom->id, $amount);
$this->redirect('/bank');
}

public function withdraw(): void
{
$amount = (int)($_POST['amount'] ?? 0);
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$_SESSION['message'] = $this->bankService->withdraw($kingdom->id, $amount);
$this->redirect('/bank');
}
}