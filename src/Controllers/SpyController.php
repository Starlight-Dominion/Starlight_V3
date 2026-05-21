<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\SpyService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class SpyController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        private SpyService $spyService,
        private AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService, $configService);
    }

public function index(): string
{
if (!$this->authService->isLoggedIn($_SESSION)) $this->redirect('/login');

$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
$spyIntel = $this->spyService->getSpyIntel($kingdom->id);
$recruitmentOptions = $this->spyService->getSpyRecruitmentOptions($kingdom->id);

return $this->render('spy/index', [
'title' => 'Intelligence Operations',
'spyIntel' => $spyIntel,
'recruitmentOptions' => $recruitmentOptions,
'spyCount' => (int)($kingdom->unit_spies ?? 0),
]);
}

public function executeReconnaissance(): void
{
header('Content-Type: application/json');
if (!$this->authService->isLoggedIn($_SESSION)) {
echo json_encode(['success' => false, 'message' => 'Unauthorized']);
return;
}

$targetId = (int)($_POST['target_id'] ?? 0);
$kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);

try {
$result = $this->spyService->executeReconnaissance($kingdom->id, $targetId);
echo json_encode($result);
} catch (\Exception $e) {
echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
}
}