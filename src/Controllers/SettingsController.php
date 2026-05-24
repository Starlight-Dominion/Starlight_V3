<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\SettingsService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Services\ApiService;
use sdo\Services\DiscordLinkService;

class SettingsController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private SettingsService $settingsService,
        private ApiService $apiService,
        private DiscordLinkService $discordLinkService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(array $vars = []): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);
        
        // Fetch API Status
        $apiApp = $this->apiService->getUserApplication((int)$_SESSION['user_id']);
        $apiStatus = null;
        if ($apiApp) {
            $apiStatus = [
                'status' => $apiApp->status,
                'keys' => $apiApp->status === 'approved' ? $this->apiService->getUserKeys((int)$_SESSION['user_id']) : []
            ];
        }

        $activeDiscordLink = $this->discordLinkService->getActiveLinkForUser((int)$_SESSION['user_id']);

        return $this->render('settings/index', [
            'title' => 'System Settings',
            'user_profile' => [
                'avatar' => $dominion->user->avatar_path ?? '',
                'email' => $dominion->user->email,
                'stasis_until' => $dominion->user->stasis_until?->format('Y-m-d H:i:s'),
                'api_status' => $apiStatus,
                'discord_link' => $activeDiscordLink ? [
                    'discord_user_id' => $activeDiscordLink->discord_user_id,
                    'linked_at' => $activeDiscordLink->linked_at?->format('Y-m-d H:i:s'),
                ] : null,
            ]
        ]);
    }

    public function createDiscordLinkCode(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            header('Content-Type: application/json');
            http_response_code(401);
            return json_encode(['success' => false, 'message' => 'Unauthorized.']);
        }

        return $this->jsonResponse(fn() => $this->discordLinkService->createChallengeForUser(
            (int)$_SESSION['user_id']
        ));
    }

    public function applyForApi(): string
    {
        return $this->jsonResponse(function() {
            return $this->apiService->submitApplication(
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
