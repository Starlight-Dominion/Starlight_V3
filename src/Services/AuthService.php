<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Race;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class AuthService
{
    public function register(string $username, string $email, string $password, string $dominionName, string $raceName): array
    {
        try {
            // 1. Identity Verification
            if (User::where('username', $username)->exists()) {
                return ['success' => false, 'message' => "Identity handle is already claimed."];
            }
            if (User::where('email', $email)->exists()) {
                return ['success' => false, 'message' => "Comms frequency (Email) is in use."];
            }
            if (Dominion::where('name', $dominionName)->exists()) {
                return ['success' => false, 'message' => "Dominion designation is already claimed."];
            }

            // 2. Resolve Race Definition
            $race = Race::where('name', $raceName)->first();
            if (!$race) {
                return ['success' => false, 'message' => "Invalid evolutionary strain selected."];
            }

            // 3. Execute Transaction
            Capsule::transaction(function () use ($username, $email, $password, $dominionName, $race) {
                
                // Create Commander
                $user = User::create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password, // Automatically hashed by the User Model mutator
                ]);

                // Create Dominion
                $dominion = $user->dominion()->create([
                    'name'    => $dominionName,
                    'race_id' => $race->id,
                    'credits' => 10000,
                    'citizens' => 500,
                    'turns'    => 100,
                    'foundation_hp' => 1000,
                    'foundation_max_hp' => 1000
                ]);

                // Initialize Structural Blueprints
                $structures = Capsule::table('structures')->get();
                foreach ($structures as $s) {
                    Capsule::table('dominion_structures')->insert([
                        'dominion_id' => $dominion->id,
                        'structure_id' => $s->id,
                        'level' => 0
                    ]);
                }

                // Initialize Manpower Roster
                $units = Capsule::table('units')->get();
                foreach ($units as $u) {
                    Capsule::table('dominion_manpower')->insert([
                        'dominion_id' => $dominion->id,
                        'unit_id' => $u->id,
                        'total_quantity' => 0
                    ]);
                }
            });

            return ['success' => true, 'message' => 'Sector initialized successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "System failure: " . $e->getMessage()];
        }
    }

    public function login(string $username, string $password): ?User
    {
        $user = User::with('dominion')->where('username', $username)->first();
        if ($user && password_verify($password, $user->password)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            return $user;
        }
        return null;
    }

    public function logout(): void 
    { 
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy(); 
        }
    }

    public function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']);
    }

    public function getCurrentUser(): ?User
    {
        if (!isset($_SESSION['user_id'])) return null;
        return User::find((int)$_SESSION['user_id']);
    }
}