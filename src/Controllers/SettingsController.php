<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\SettingsService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;

class SettingsController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private SettingsService $settingsService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
        
        // Fetch API Status
        $apiService = new \sdo\Services\ApiService();
        $apiApp = $apiService->getUserApplication((int)$_SESSION['user_id']);
        $apiStatus = null;
        if ($apiApp) {
            $apiStatus = [
                'status' => $apiApp->status,
                'keys' => $apiApp->status === 'approved' ? $apiService->getUserKeys((int)$_SESSION['user_id']) : []
            ];
        }

        return $this->render('settings/index', [
            'title' => 'System Settings',
            'user_profile' => [
                'avatar' => $kingdom->user->avatar_path ?? '',
                'email' => $kingdom->user->email,
                'stasis_until' => $kingdom->user->stasis_until?->format('Y-m-d H:i:s'),
                'api_status' => $apiStatus
            ]
        ]);
    }

    public function applyForApi(): string
    {
        return $this->jsonResponse(function() {
            $apiService = new \sdo\Services\ApiService();
            return $apiService->submitApplication(
                (int)$_SESSION['user_id'],
                (string)($_POST['project_name'] ?? ''),
                (string)($_POST['justification'] ?? '')
            );
        });
    }

    private function jsonResponse(callable $action): string
    {
        header('Content-Type: application/json');
        try {
            $res = $action();
            return json_encode($res);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateIdentity(): string
    {
        return $this->jsonResponse(fn() => $this->settingsService->updateIdentity(
            (int)$_SESSION['user_id'],
            (string)($_POST['username'] ?? ''),
            (string)($_POST['email'] ?? '')
        ));
    }

    public function updateCipher(): string
    {
        return $this->jsonResponse(fn() => $this->settingsService->updateCipher(
            (int)$_SESSION['user_id'],
            (string)($_POST['current_password'] ?? ''),
            (string)($_POST['new_password'] ?? ''),
            (string)($_POST['confirm_password'] ?? '')
        ));
    }

    /**
     * Handle multipart avatar upload
     */
    public function updateAvatar(): string
    {
        return $this->jsonResponse(function() {
            if (!isset($_FILES['avatar'])) {
                throw new \Exception("No sigil data provided.");
            }
            return $this->settingsService->processAvatarUpload(
                (int)$_SESSION['user_id'],
                $_FILES['avatar']
            );
        });
    }

    public function toggleStasis(): string
    {
        return $this->jsonResponse(fn() => $this->settingsService->toggleStasis(
            (int)$_SESSION['user_id']
        ));
    }
}