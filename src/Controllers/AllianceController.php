<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Services\AllianceService;
use sdo\Services\AllianceResourceService;
use sdo\Services\AllianceForumService;

class AllianceController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private AllianceService $allianceService,
        private AllianceResourceService $resourceService,
        private AllianceForumService $forumService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    private function ensureAuthenticated(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        if (!$dominion) {
            session_destroy();
            $this->redirect('/login?error=dominion_not_found');
        }
    }

    /**
     * Page View Actions
     */
    public function index(): string
    {
        return $this->hubPage();
    }

    public function hubPage(): string
    {
        $this->ensureAuthenticated();
        $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
        
        if (!$user->alliance_id) {
            return $this->render('alliance/unaligned', ['title' => 'Sector Group']);
        }

        $payload = $this->allianceService->getAllianceHubPayload((int)$_SESSION['user_id']);
        return $this->render('alliance/hub', ['payload' => $payload, 'title' => 'Alliance Hub']);
    }

    public function treasuryPage(): string
    {
        $this->ensureAuthenticated();
        try {
            $payload = $this->resourceService->getBankPayload((int)$_SESSION['user_id']);
            return $this->render('alliance/bank', ['payload' => $payload, 'title' => 'Alliance Treasury']);
        } catch (\Exception $e) {
            return $this->render('alliance/unaligned', ['error' => $e->getMessage()]);
        }
    }

    public function structuresPage(): string
    {
        $this->ensureAuthenticated();
        try {
            $payload = $this->resourceService->getStructuresPayload((int)$_SESSION['user_id']);
            return $this->render('alliance/structures', ['payload' => $payload, 'title' => 'Alliance Strategic Assets']);
        } catch (\Exception $e) {
            return $this->render('alliance/unaligned', ['error' => $e->getMessage()]);
        }
    }

    public function forumPage(): string
    {
        $this->ensureAuthenticated();
        try {
            $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
            $payload = ['threads' => $this->forumService->getThreads((int)$user->alliance_id)];
            return $this->render('alliance/forum', ['payload' => $payload, 'title' => 'Alliance Comm-Link']);
        } catch (\Exception $e) {
            return $this->render('alliance/unaligned', ['error' => $e->getMessage()]);
        }
    }

    public function threadPage(array $vars): string
    {
        $this->ensureAuthenticated();
        try {
            $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
            $payload = ['thread' => $this->forumService->getThread((int)$vars['id'], (int)$user->alliance_id)];
            return $this->render('alliance/thread', ['payload' => $payload, 'title' => 'Alliance Transmission']);
        } catch (\Exception $e) {
            return $this->render('alliance/unaligned', ['error' => $e->getMessage()]);
        }
    }

    public function managementPage(): string
    {
        $this->ensureAuthenticated();
        try {
            $payload = $this->allianceService->getAllianceHubPayload((int)$_SESSION['user_id']);
            return $this->render('alliance/management', ['payload' => $payload, 'title' => 'Alliance Command']);
        } catch (\Exception $e) {
            return $this->render('alliance/unaligned', ['error' => $e->getMessage()]);
        }
    }

    /**
     * API Actions
     */
    public function hub(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            return $this->allianceService->getAllianceHubPayload((int)$_SESSION['user_id']);
        });
    }

    public function list(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $search = $_GET['search'] ?? '';
            return ['alliances' => $this->allianceService->getAlliancesList($search)];
        });
    }

    public function create(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $this->allianceService->createAlliance((int)$_SESSION['user_id'], $_POST);
            return ['success' => true, 'message' => 'Alliance founded.'];
        });
    }

    public function leave(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $this->allianceService->leaveAlliance((int)$_SESSION['user_id']);
            return ['success' => true, 'message' => 'Left alliance.'];
        });
    }

    public function apply(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $allianceId = (int)($_POST['alliance_id'] ?? 0);
            $message = $_POST['message'] ?? '';
            $this->allianceService->applyToAlliance((int)$_SESSION['user_id'], $allianceId, $message);
            return ['success' => true, 'message' => 'Application submitted.'];
        });
    }

    public function processApplication(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $appId = (int)($_POST['application_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $this->allianceService->processApplication((int)$_SESSION['user_id'], $appId, $action);
            return ['success' => true, 'message' => 'Application processed.'];
        });
    }

    public function kick(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $this->allianceService->kickMember((int)$_SESSION['user_id'], $targetUserId);
            return ['success' => true, 'message' => 'Member removed.'];
        });
    }

    // Bank API
    public function bank(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            return $this->resourceService->getBankPayload((int)$_SESSION['user_id']);
        });
    }

    public function donate(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $amount = (int)($_POST['amount'] ?? 0);
            $comment = $_POST['comment'] ?? '';
            $this->resourceService->donate((int)$_SESSION['user_id'], $amount, $comment);
            return ['success' => true, 'message' => 'Contribution received.'];
        });
    }

    public function withdraw(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $amount = (int)($_POST['amount'] ?? 0);
            $this->resourceService->withdraw((int)$_SESSION['user_id'], $amount);
            return ['success' => true, 'message' => 'Funds withdrawn.'];
        });
    }

    // Structures API
    public function structures(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            return $this->resourceService->getStructuresPayload((int)$_SESSION['user_id']);
        });
    }

    public function purchaseStructure(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $key = $_POST['structure_key'] ?? '';
            $this->resourceService->purchaseStructure((int)$_SESSION['user_id'], $key);
            return ['success' => true, 'message' => 'Structure deployed.'];
        });
    }

    // Forum API
    public function forum(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
            if (!$user->alliance_id) throw new \Exception('Not in an alliance.');
            return ['threads' => $this->forumService->getThreads((int)$user->alliance_id)];
        });
    }

    public function thread(array $vars): string
    {
        return $this->jsonResponse(function() use ($vars) {
            $this->ensureAuthenticated();
            $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
            return ['thread' => $this->forumService->getThread((int)$vars['id'], (int)$user->alliance_id)];
        });
    }

    public function createThread(): string
    {
        return $this->jsonResponse(function() {
            $this->ensureAuthenticated();
            $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
            if (!$user->alliance_id) throw new \Exception('Not in an alliance.');
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $threadId = $this->forumService->createThread((int)$user->id, (int)$user->alliance_id, $title, $content);
            return ['success' => true, 'thread_id' => $threadId];
        });
    }

    public function reply(array $vars): string
    {
        return $this->jsonResponse(function() use ($vars) {
            $this->ensureAuthenticated();
            $user = $this->gameService->getDominionByUserId((int)$_SESSION['user_id'])->user;
            if (!$user->alliance_id) throw new \Exception('Not in an alliance.');
            $content = $_POST['content'] ?? '';
            $this->forumService->createPost((int)$user->id, (int)$vars['id'], (int)$user->alliance_id, $content);
            return ['success' => true];
        });
    }
}
