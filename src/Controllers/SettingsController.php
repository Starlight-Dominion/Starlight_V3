<?php

declare(strict_types=1);

namespace sdo\Controllers;

use Exception;
use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;

class SettingsController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        private UserRepositoryInterface $userRepository,
        private KingdomRepositoryInterface $kingdomRepository
    ) {
        parent::__construct($gameService, $advisorService);
    }

    public function index(): string
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $kingdom = $this->kingdomRepository->findByUserId((int)$_SESSION['user_id']);

        if (!$kingdom) {
            return $this->render('errors/unauthorized', ['title' => 'Kingdom Not Found']);
        }

        $level = $this->gameService->calculateLevel((int)$kingdom->xp);

        $holdRemaining = 0;
        if ($kingdom->last_untrained) {
            $cooldownEnd = new \DateTime($kingdom->last_untrained . ' + 1 hour');
            $now = new \DateTime();

            if ($now < $cooldownEnd) {
                $holdRemaining = (int)$cooldownEnd->getTimestamp() - (int)$now->getTimestamp();
            }
        }

        return $this->render('settings/index', [
            'title' => 'Settings',
            'kingdom' => $kingdom->toArray(),
            'level' => $level,
            'held_citizens' => (int)($kingdom->held_citizens ?? 0),
            'hold_remaining' => $holdRemaining,
            'miners' => (int)($kingdom->miners ?? 0),
        ]);
    }

    public function updateProfile(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $newUsername = preg_replace('/[^\p{L}\p{N}_-]/u', '', $_POST['username'] ?? '');
        $usernameLength = strlen($newUsername);

        if ($usernameLength < 3 || $usernameLength > 30) {
            $this->redirectBack(['Username must be between 3 and 30 characters.']);
        }

        try {
            $existing = $this->userRepository->findByUsername($newUsername);
            if ($existing && $existing->id !== $userId) {
                $this->redirectBack(['That username is already taken.']);
            }

            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            $user = $this->userRepository->findById($userId);

            if (!empty($newPass)) {
                if (strlen($newPass) < 8) {
                    throw new Exception("New password must be at least 8 characters.");
                }
                if ($newPass !== $confirmPass) {
                    throw new Exception("Password confirmation does not match.");
                }
                if (!password_verify($currentPass, $user->password)) {
                    throw new Exception("Current password is incorrect.");
                }

                $this->userRepository->update($userId, ['password' => $newPass]);
            }

            $this->userRepository->update($userId, ['username' => $newUsername]);
            $_SESSION['username'] = $newUsername;

            $_SESSION['message'] = "Profile updated successfully.";
            $this->redirect('/settings');

        } catch (Exception $e) {
            $this->redirectBack([$e->getMessage()]);
        }
    }
}