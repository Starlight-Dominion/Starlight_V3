<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private KingdomRepositoryInterface $kingdomRepository
    ) {}

    public function register(string $username, string $email, string $password, string $kingdomName): bool
    {
        try {
            Capsule::transaction(function () use ($username, $email, $password, $kingdomName) {
                $user = $this->userRepository->create([
                    'username' => $username,
                    'email' => $email,
                    'password' => $password, // Password hashing handled by User model mutator
                ]);

                // We use Eloquent directly here for the relationship creation as defined in Repos
                $user->kingdom()->create([
                    'kingdom_name' => $kingdomName,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function login(string $username, string $password): ?User
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user && password_verify($password, $user->password)) {
            // Security: Prevent Session Fixation
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            
            $user->makeHidden('password');
            return $user;
        }

        return null;
    }

    public function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']);
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->isLoggedIn($_SESSION)) return null;
        return $this->userRepository->findById((int)$_SESSION['user_id']);
    }

    public function logout(): void
    {
        session_destroy();
    }
}