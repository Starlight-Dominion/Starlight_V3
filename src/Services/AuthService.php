<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Kingdom;
use sdo\Models\Race;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class AuthService
{
    /**
     * Register a new sovereign identity and initialize their dominion.
     * Returns an array with 'success' boolean and optional 'message'.
     */
    public function register(
        string $username, 
        string $email, 
        string $password, 
        string $dominionName, 
        string $raceName
    ): array {
        try {
            // 1. Explicit Duplicate Checks (Fail Loudly with exact reasons)
            if (User::where('username', $username)->exists()) {
                return ['success' => false, 'message' => "The Identity Handle '{$username}' is already registered."];
            }
            if (User::where('email', $email)->exists()) {
                return ['success' => false, 'message' => "The Comms Frequency '{$email}' is already in use."];
            }
            if (Kingdom::where('kingdom_name', $dominionName)->exists()) {
                return ['success' => false, 'message' => "The Dominion Designation '{$dominionName}' is already claimed by another sector."];
            }

            // 2. Auto-Seed Fallback (Protects against missing seeders)
            $race = Race::where('name', $raceName)->first();
            if (!$race) {
                $race = Race::create([
                    'name' => $raceName,
                    'description' => 'System initialized evolutionary strain.',
                    'bonus_value' => 0
                ]);
            }

            // 3. ACID Transaction for Data Integrity
            Capsule::transaction(function () use ($username, $email, $password, $dominionName, $race) {
                // Persist Identity
                $user = User::create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password, // Mutator handles hashing
                    'is_bot'   => false,
                    'is_admin' => false
                ]);

                // Initialize Dominion
                $user->kingdom()->create([
                    'kingdom_name' => $dominionName,
                    'race_id'      => $race->id,
                    'gold'         => 10000,
                    'citizens'     => 500,
                    'turns'        => 100,
                    'xp'           => 0
                ]);
            });

            return ['success' => true, 'message' => 'Sector initialized.'];

        } catch (Exception $e) {
            // Log deep system errors (like SQL syntax issues) for admins
            error_log("CRITICAL AUTH FAILURE: " . $e->getMessage());
            return ['success' => false, 'message' => "Core system error: " . $e->getMessage()];
        }
    }

    /**
     * Verify credentials and establish a secure neural link (session).
     */
    public function login(string $username, string $password): ?User
    {
        $user = User::where('username', $username)->first();

        if ($user && password_verify($password, $user->password)) {
            // Security: Prevent Session Fixation
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            
            // Exclude sensitive data from memory-resident model
            $user->makeHidden('password');
            return $user;
        }

        return null;
    }

    public function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}