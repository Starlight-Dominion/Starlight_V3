<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\SettingsService;
use sdo\Services\AuthService;

class SettingsController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private SettingsService $settingsService,
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
        
        return $this->render('settings/index', [
            'title' => 'System Settings',
            'user_profile' => [
                'avatar' => $kingdom->user->avatar_path ?? '',
                'email' => $kingdom->user->email,
                'stasis_until' => $kingdom->user->stasis_until?->format('Y-m-d H:i:s')
            ]
        ]);
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

    public function updateAvatar(): string
    {
        return $this->jsonResponse(fn() => $this->settingsService->updateAvatar(
            (int)$_SESSION['user_id'],
            (string)($_POST['avatar_path'] ?? '')
        ));
    }

    public function toggleStasis(): string
    {
        return $this->jsonResponse(fn() => $this->settingsService->toggleStasis(
            (int)$_SESSION['user_id']
        ));
    }
}