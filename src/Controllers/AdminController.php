<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\AdminPlayerService;
use sdo\Services\AdminGameDataService;
use sdo\Services\AdminSystemService;
use sdo\Services\ConfigService;
use sdo\Services\ApiService;
use sdo\Repositories\Interfaces\UserRepositoryInterface;

class AdminController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private AdminPlayerService $playerService,
        private AdminGameDataService $gameDataService,
        private AdminSystemService $systemService,
        private ApiService $apiService,
        private UserRepositoryInterface $userRepository
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    private function checkAdmin(): void
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $user = $this->userRepository->findById((int)$_SESSION['user_id']);
        if (!$this->authService->isAdmin($user)) {
            $_SESSION['message'] = ['success' => false, 'message' => 'Access Denied: High Command Only.'];
            $this->redirect('/dashboard');
        }
    }

    public function index(): string
    {
        $this->checkAdmin();

        return $this->render('admin/index', [
            'title' => 'Command Center',
            'stats' => $this->systemService->getSystemStats()
        ]);
    }

    public function searchDominions(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $query = (string)($_GET['q'] ?? '');
            return ['success' => true, 'results' => $this->playerService->searchDominions($query)];
        });
    }

    public function getAllDominions(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'results' => $this->playerService->getAllDominions()];
        });
    }

    public function updateDominion(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $data = $_POST;
            unset($data['id'], $data['_csrf']);

            $res = $this->playerService->updateDominionStats($id, $data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_DOMINION', "Modified sector ID {$id}", $data);
            return ['success' => $res];
        });
    }

    public function getKingdomProfile(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_GET['id'] ?? 0);
            $profile = $this->playerService->getKingdomFullProfile($id);
            return ['success' => true, 'profile' => $profile];
        });
    }

    public function updateKingdomManpower(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $domId = (int)($_POST['dominion_id'] ?? 0);
            $unitId = (int)($_POST['unit_id'] ?? 0);
            $total = (int)($_POST['total_quantity'] ?? 0);
            $stabled = (int)($_POST['stabled_quantity'] ?? 0);

            $res = $this->playerService->updateKingdomManpower($domId, $unitId, $total, $stabled);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_MANPOWER', "Modified manpower for sector {$domId}, unit {$unitId}", ['total' => $total, 'stabled' => $stabled]);
            return ['success' => $res];
        });
    }

    public function updateKingdomStructure(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $domId = (int)($_POST['dominion_id'] ?? 0);
            $structureId = (int)($_POST['structure_id'] ?? 0);
            $level = (int)($_POST['level'] ?? 0);

            $res = $this->playerService->updateKingdomStructure($domId, $structureId, $level);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_KINGDOM_STRUCTURE', "Modified structure level for sector {$domId}, structure {$structureId}", ['level' => $level]);
            return ['success' => $res];
        });
    }

    public function updateKingdomArmory(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $domId = (int)($_POST['dominion_id'] ?? 0);
            $itemId = (int)($_POST['item_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            $equipped = (bool)($_POST['is_equipped'] ?? false);

            $res = $this->playerService->updateKingdomArmory($domId, $itemId, $quantity, $equipped);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_KINGDOM_ARMORY', "Modified armory for sector {$domId}, item {$itemId}", ['quantity' => $quantity, 'equipped' => $equipped]);
            return ['success' => $res];
        });
    }

    public function getUnits(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'units' => $this->gameDataService->getAllUnits()];
        });
    }

    public function updateUnit(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $data = $_POST;
            unset($data['id'], $data['_csrf']);

            $res = $this->gameDataService->updateUnit($id, $data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_UNIT', "Modified unit ID {$id} ({$data['slug']})", $data);
            return ['success' => $res];
        });
    }

    public function addUnit(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $data = [
                'name' => 'New Unit Class',
                'slug' => 'new_unit_' . time(),
                'description' => 'Awaiting combat doctrine.',
                'cost_credits' => 1000,
                'cost_citizens' => 1,
                'cost_turns' => 1,
                'power_offense' => 1,
                'power_defense' => 1,
                'power_spy_offense' => 0,
                'power_spy_defense' => 0,
                'production_credits' => 0
            ];

            $id = $this->gameDataService->addUnit($data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'ADD_UNIT', "Enlisted new unit class ID {$id}", $data);
            return ['success' => true, 'id' => $id];
        });
    }

    public function deleteUnit(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $res = $this->gameDataService->deleteUnit($id);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'DELETE_UNIT', "Decommissioned unit class ID {$id}");
            return ['success' => $res];
        });
    }

    public function getStructures(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $structures = $this->gameDataService->getAllStructures();
            $detailedStructures = [];

            foreach ($structures as $s) {
                $detailedStructures[] = [
                    'details' => $s,
                    'levels' => $this->gameDataService->getStructureLevels((int)$s['id'])
                ];
            }

            return ['success' => true, 'structures' => $detailedStructures];
        });
    }

    public function updateStructure(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $data = $_POST;
            unset($data['id'], $data['_csrf']);

            $res = $this->gameDataService->updateStructure($id, $data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_STRUCTURE', "Modified structure ID {$id}", $data);
            return ['success' => $res];
        });
    }

    public function addStructure(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $data = [
                'slug' => 'new_building_' . time(),
                'name' => 'New Building',
                'description' => 'Awaiting architectural plans.',
                'upgrade_slots' => 1,
                'max_level' => 10
            ];

            $id = $this->gameDataService->addStructure($data);
            return ['success' => true, 'id' => $id];
        });
    }

    public function deleteStructure(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $res = $this->gameDataService->deleteStructure($id);
            return ['success' => $res];
        });
    }

    public function addStructureLevel(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $sId = (int)($_POST['structure_id'] ?? 0);
            $nextLevel = (int)($_POST['level'] ?? 1);

            $data = [
                'structure_id' => $sId,
                'level' => $nextLevel,
                'cost' => 100000,
                'buff_name' => 'Rank ' . $nextLevel
            ];

            $res = $this->gameDataService->addStructureLevel($data);
            return ['success' => $res];
        });
    }

    public function updateStructureLevel(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $sId = (int)($_POST['structure_id'] ?? 0);
            $level = (int)($_POST['level'] ?? 0);
            
            $data = $_POST;
            unset($data['structure_id'], $data['level'], $data['_csrf']);

            return ['success' => $this->gameDataService->updateStructureLevel($sId, $level, $data)];
        });
    }

    public function getArmoryItems(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return [
                'success' => true, 
                'items' => $this->gameDataService->getAllArmoryItems(),
                'unit_types' => $this->gameDataService->getArmoryUnitTypes(),
                'categories' => $this->gameDataService->getArmoryCategories()
            ];
        });
    }

    public function updateArmoryItem(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $data = $_POST;
            unset($data['id'], $data['_csrf']);

            $res = $this->gameDataService->updateArmoryItem($id, $data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_ARMORY_ITEM', "Calibrated armory asset ID {$id}", $data);
            return ['success' => $res];
        });
    }

    public function addArmoryItem(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $data = [
                'name' => 'New Item',
                'slug' => 'new_item_' . time(),
                'category_id' => (int)($_POST['category_id'] ?? 1),
                'cost' => 1000,
                'unit_type' => (string)($_POST['unit_type'] ?? 'soldiers')
            ];

            $id = $this->gameDataService->addArmoryItem($data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'ADD_ARMORY_ITEM', "Enlisted new armory asset ID {$id}", $data);
            return ['success' => true, 'id' => $id];
        });
    }

    public function deleteArmoryItem(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $res = $this->gameDataService->deleteArmoryItem($id);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'DELETE_ARMORY_ITEM', "Decommissioned armory asset ID {$id}");
            return ['success' => $res];
        });
    }

    public function getRaces(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'races' => $this->gameDataService->getAllRaces()];
        });
    }

    public function updateRace(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $data = $_POST;
            unset($data['id'], $data['_csrf']);

            $res = $this->gameDataService->updateRace($id, $data);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_RACE', "Modified evolutionary strain ID {$id}", $data);
            return ['success' => $res];
        });
    }

    public function getBattleLogs(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'logs' => $this->systemService->getRecentBattleLogs()];
        });
    }

    public function getApiKeys(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'keys' => $this->apiService->getAllKeys()];
        });
    }

    public function issueApiKey(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $userId = (int)($_POST['user_id'] ?? 0);
            $limit = (int)($_POST['rate_limit'] ?? 60);
            $scopes = trim((string)($_POST['scopes'] ?? '*'));

            $key = $this->apiService->issueKey($userId, $limit, $scopes);
            return ['success' => true, 'key' => $key];
        });
    }

    public function updateApiKey(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $data = [];
            if (isset($_POST['rate_limit'])) $data['rate_limit_per_minute'] = (int)$_POST['rate_limit'];
            if (isset($_POST['is_active'])) $data['is_active'] = ($_POST['is_active'] === 'true' || $_POST['is_active'] === '1');
            if (isset($_POST['scopes'])) {
                $scopes = trim((string)$_POST['scopes']);
                $data['scopes'] = $scopes !== '' ? $scopes : '*';
            }

            $this->apiService->updateKey($id, $data);
            return ['success' => true];
        });
    }

    public function deleteApiKey(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $this->apiService->deleteKey($id);
            return ['success' => true];
        });
    }

    public function getApiApplications(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'applications' => $this->apiService->getPendingApplications()];
        });
    }

    public function processApiApplication(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $action = (string)($_POST['action'] ?? '');
            $rateLimit = (int)($_POST['rate_limit'] ?? 60);
            $notes = (string)($_POST['notes'] ?? '');

            return $this->apiService->processApplication($id, $action, $rateLimit, $notes);
        });
    }

    public function impersonate(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $targetUser = $this->userRepository->findById($id);

            if (!$targetUser) {
                throw new \Exception('Identity not found.');
            }

            if ($this->authService->isAdmin($targetUser)) {
                throw new \Exception('Neural recursion prohibited: cannot impersonate other administrators.');
            }

            $_SESSION['impersonator_id'] = $_SESSION['user_id'];
            $_SESSION['user_id'] = $targetUser->id;
            $_SESSION['username'] = $targetUser->username;

            $this->systemService->logAdminAction((int)$_SESSION['impersonator_id'], 'IMPERSONATE', "Started impersonation of commander {$targetUser->username} (ID {$id})");

            return ['success' => true];
        });
    }

    public function stopImpersonating(): void
    {
        if (isset($_SESSION['impersonator_id'])) {
            $admin = $this->userRepository->findById((int)$_SESSION['impersonator_id']);
            if ($admin) {
                $_SESSION['user_id'] = $admin->id;
                $_SESSION['username'] = $admin->username;
                $this->systemService->logAdminAction($admin->id, 'STOP_IMPERSONATE', "Terminated active impersonation.");
            }
            unset($_SESSION['impersonator_id']);
        }

        $this->redirect('/admin');
    }

    public function getApiLogs(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'logs' => $this->apiService->getRecentLogs()];
        });
    }

    public function getAuditLogs(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'logs' => $this->systemService->getAuditLogs()];
        });
    }

    public function getSettings(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            return ['success' => true, 'settings' => $this->configService->getAll()];
        });
    }

    public function updateSetting(): string
    {
        return $this->jsonResponse(function() {
            $this->checkAdmin();
            $key = (string)($_POST['key'] ?? '');
            $value = $_POST['value'] ?? '';

            $this->configService->set($key, $value);
            $this->systemService->logAdminAction((int)$_SESSION['user_id'], 'UPDATE_SETTING', "Modified setting {$key}", ['value' => $value]);
            return ['success' => true];
        });
    }
}
