<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Race;
use sdo\Models\Structure;
use sdo\Models\DominionStructure;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use sdo\Services\ConfigService;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class AuthService
{
    public function __construct(
        private ConfigService $configService,
        private UserRepositoryInterface $userRepository,
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private StructureRepositoryInterface $structureRepository
    ) {}

    public function register(string $username, string $email, string $password, string $dominionName, string $raceName): array
    {
        try {
            // 1. Identity Verification
            if ($this->userRepository->findByUsername($username)) {
                return ['success' => false, 'message' => "Identity handle is already claimed."];
            }
            if ($this->userRepository->findByEmail($email)) {
                return ['success' => false, 'message' => "Comms frequency (Email) is in use."];
            }
            if ($this->dominionRepository->findByName($dominionName)) {
                return ['success' => false, 'message' => "Dominion designation is already claimed."];
            }

            // 2. Resolve Race Definition
            $race = Race::where('name', $raceName)->first();
            if (!$race) {
                return ['success' => false, 'message' => "Invalid evolutionary strain selected."];
            }

            $startingCredits = (int)$this->configService->get('starting_credits', 10000);
            $startingCitizens = (int)$this->configService->get('starting_citizens', 500);

            // 3. Execute Transaction
            Capsule::transaction(function () use ($username, $email, $password, $dominionName, $race, $startingCredits, $startingCitizens) {
                
                // Create Commander
                $user = $this->userRepository->create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password, // Automatically hashed by the User Model mutator
                ]);

                // Create Dominion
                $dominion = $user->dominion()->create([
                    'name'    => $dominionName,
                    'race_id' => $race->id,
                    'credits' => $startingCredits,
                    'citizens' => $startingCitizens,
                    'turns'    => 100,
                    'foundation_hp' => 1000,
                    'foundation_max_hp' => 1000
                ]);

                // Initialize Structural Blueprints
                $structures = $this->structureRepository->all();
                foreach ($structures as $s) {
                    DominionStructure::create([
                        'dominion_id' => $dominion->id,
                        'structure_id' => $s->id,
                        'level' => 0
                    ]);
                }

                // Initialize Manpower Roster
                $units = $this->unitRepository->all();
                foreach ($units as $u) {
                    DominionManpower::create([
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
        $user = $this->userRepository->findByUsername($username);
        if ($user && password_verify($password, $user->password)) {
            return $user;
        }
        return null;
    }

    public function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']);
    }

    public function isAdmin(?User $user): bool
    {
        if (!$user) return false;
        
        $hardcodedAdmins = explode(',', $_ENV['ADMIN_USERNAME'] ?? '');
        $trimmedAdmins = array_map('trim', $hardcodedAdmins);
        
        if (in_array($user->username, $trimmedAdmins, true)) {
            return true;
        }

        return (bool)$user->is_admin;
    }
}
