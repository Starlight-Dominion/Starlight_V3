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
            if (User::where('username', $username)->exists()) return ['success' => false, 'message' => "Handle taken."];
            if (User::where('email', $email)->exists()) return ['success' => false, 'message' => "Email in use."];
            if (Dominion::where('name', $dominionName)->exists()) return ['success' => false, 'message' => "Designation claimed."];

            $race = Race::where('name', $raceName)->firstOrFail();

            Capsule::transaction(function () use ($username, $email, $password, $dominionName, $race) {
                $user = User::create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password,
                ]);

                $dominion = $user->dominion()->create([
                    'name'    => $dominionName,
                    'race_id' => $race->id,
                    'credits' => 10000,
                    'citizens' => 500,
                    'turns'    => 100,
                    'foundation_hp' => 1000,
                    'foundation_max_hp' => 1000
                ]);

                // Initialize empty Manpower for all unit types
                $units = Capsule::table('units')->get();
                foreach ($units as $u) {
                    Capsule::table('dominion_manpower')->insert([
                        'dominion_id' => $dominion->id,
                        'unit_id' => $u->id,
                        'total_quantity' => 0,
                        'stabled_quantity' => 0
                    ]);
                }

                // Initialize level 0 for all structures
                $structures = Capsule::table('structures')->get();
                foreach ($structures as $s) {
                    Capsule::table('dominion_structures')->insert([
                        'dominion_id' => $dominion->id,
                        'structure_id' => $s->id,
                        'level' => 0
                    ]);
                }
            });

            return ['success' => true, 'message' => 'Sector initialized.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "System failure: " . $e->getMessage()];
        }
    }

    public function login(string $username, string $password): ?User
    {
        $user = User::with('dominion')->where('username', $username)->first();
        if ($user && password_verify($password, $user->password)) {
            if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
            return $user;
        }
        return null;
    }

    public function logout(): void { session_destroy(); }
}