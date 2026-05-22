<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\AdminService;
use sdo\Services\ConfigService;

class AdminController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private AdminService $adminService,
        private \sdo\Services\ApiService $apiService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    private function checkAdmin(): void
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $user = $this->authService->getCurrentUser();
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
            'stats' => $this->adminService->getSystemStats()
        ]);
    }

    public function searchKingdoms(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $query = (string)($_GET['q'] ?? '');
        $results = $this->adminService->searchKingdoms($query);

        return json_encode(['success' => true, 'results' => $results]);
    }

    public function getAllKingdoms(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode(['success' => true, 'results' => $this->adminService->getAllKingdoms()]);
    }

    public function updateKingdom(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $data = $_POST;
        unset($data['id'], $data['_csrf']);

        try {
            $res = $this->adminService->updateKingdomStats($id, $data);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getUnits(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode(['success' => true, 'units' => $this->adminService->getAllUnits()]);
    }

    public function updateUnit(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $field = (string)($_POST['field'] ?? '');
        $value = $_POST['value']; // Can be string or int

        try {
            $res = $this->adminService->updateUnit($id, [$field => $value]);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addUnit(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $data = [
            'name' => 'New Unit Class',
            'slug' => 'new_unit_' . time(),
            'description' => 'Awaiting combat doctrine.',
            'cost_gold' => 1000,
            'cost_citizens' => 1,
            'cost_turns' => 1,
            'power_offense' => 1,
            'power_defense' => 1
        ];

        try {
            $id = $this->adminService->addUnit($data);
            return json_encode(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteUnit(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);

        try {
            $res = $this->adminService->deleteUnit($id);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getStructures(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $structures = $this->adminService->getAllStructures();
        $detailedStructures = [];

        foreach ($structures as $s) {
            $detailedStructures[] = [
                'details' => $s,
                'levels' => $this->adminService->getStructureLevels((int)$s->id)
            ];
        }

        return json_encode([
            'success' => true,
            'structures' => $detailedStructures
        ]);
    }

    public function updateStructure(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $data = $_POST;
        unset($data['id'], $data['_csrf']);

        try {
            $res = $this->adminService->updateStructure($id, $data);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addStructure(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $data = [
            'slug' => 'new_building_' . time(),
            'name' => 'New Building',
            'description' => 'Awaiting architectural plans.',
            'upgrade_slots' => 1,
            'max_level' => 10
        ];

        try {
            $id = $this->adminService->addStructure($data);
            return json_encode(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteStructure(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);

        try {
            $res = $this->adminService->deleteStructure($id);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addStructureLevel(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $sId = (int)($_POST['structure_id'] ?? 0);
        $nextLevel = (int)($_POST['level'] ?? 1);

        $data = [
            'structure_id' => $sId,
            'level' => $nextLevel,
            'cost' => 100000,
            'buff_name' => 'Rank ' . $nextLevel
        ];

        try {
            $res = $this->adminService->addStructureLevel($data);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateStructureLevel(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $sId = (int)($_POST['structure_id'] ?? 0);
        $level = (int)($_POST['level'] ?? 0);
        
        // Collect all fields from POST except structure_id, level, and _csrf
        $data = $_POST;
        unset($data['structure_id'], $data['level'], $data['_csrf']);

        try {
            $res = $this->adminService->updateStructureLevel($sId, $level, $data);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getArmoryItems(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode([
            'success' => true, 
            'items' => $this->adminService->getAllArmoryItems(),
            'unit_types' => $this->adminService->getArmoryUnitTypes(),
            'categories' => $this->adminService->getArmoryCategories()
        ]);
    }

    public function updateArmoryItem(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $field = (string)($_POST['field'] ?? '');
        $value = $_POST['value'];

        try {
            $res = $this->adminService->updateArmoryItem($id, [$field => $value]);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addArmoryItem(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $data = [
            'name' => 'New Item',
            'slug' => 'new_item_' . time(),
            'category_id' => (int)($_POST['category_id'] ?? 1),
            'cost' => 1000,
            'unit_type' => (string)($_POST['unit_type'] ?? 'soldiers')
        ];

        try {
            $id = $this->adminService->addArmoryItem($data);
            return json_encode(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteArmoryItem(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);

        try {
            $res = $this->adminService->deleteArmoryItem($id);
            return json_encode(['success' => $res]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getBattleLogs(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode(['success' => true, 'logs' => $this->adminService->getRecentBattleLogs()]);
    }

    public function getApiKeys(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode(['success' => true, 'keys' => $this->apiService->getAllKeys()]);
    }

    public function issueApiKey(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $userId = (int)($_POST['user_id'] ?? 0);
        $limit = (int)($_POST['rate_limit'] ?? 60);

        try {
            $key = $this->apiService->issueKey($userId, $limit);
            return json_encode(['success' => true, 'key' => $key]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateApiKey(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $data = [];
        if (isset($_POST['rate_limit'])) $data['rate_limit_per_minute'] = (int)$_POST['rate_limit'];
        if (isset($_POST['is_active'])) $data['is_active'] = ($_POST['is_active'] === 'true' || $_POST['is_active'] === '1');

        try {
            $this->apiService->updateKey($id, $data);
            return json_encode(['success' => true]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteApiKey(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $id = (int)($_POST['id'] ?? 0);

        try {
            $this->apiService->deleteKey($id);
            return json_encode(['success' => true]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getApiLogs(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode(['success' => true, 'logs' => $this->apiService->getRecentLogs()]);
    }

    public function getSettings(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        return json_encode(['success' => true, 'settings' => $this->configService->getAll()]);
    }

    public function updateSetting(): string
    {
        header('Content-Type: application/json');
        $this->checkAdmin();

        $key = (string)($_POST['key'] ?? '');
        $value = $_POST['value'] ?? '';

        try {
            $this->configService->set($key, $value);
            return json_encode(['success' => true]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
