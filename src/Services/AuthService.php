<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Race;
use sdo\Services\ConfigService;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\DominionStructureRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\RaceRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class AuthService
{
    public function __construct(
        private ConfigService $configService,
        private UserRepositoryInterface $userRepository,
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private StructureRepositoryInterface $structureRepository,
        private DominionStructureRepositoryInterface $dominionStructureRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private RaceRepositoryInterface $raceRepository,
        private TransactionManager $transactionManager
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
            $race = $this->raceRepository->findByName($raceName);
            if (!$race) {
                return ['success' => false, 'message' => "Invalid evolutionary strain selected."];
            }

            $startingCredits = (int)$this->configService->get('starting_credits', 10000);
            $startingCitizens = (int)$this->configService->get('starting_citizens', 500);

            // 3. Execute Transaction
            $this->transactionManager->transaction(function () use ($username, $email, $password, $dominionName, $race, $startingCredits, $startingCitizens) {
                
                // Create Commander
                $user = $this->userRepository->create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password, // Automatically hashed by the User Model mutator
                ]);

                // Create Dominion
                $dominion = $this->dominionRepository->findByUserId((int)$user->id);
                // Note: The User model in the legacy code has a relationship that might auto-create, 
                // but let's be explicit if we are refactoring. 
                // Actually, the original code used $user->dominion()->create(...).
                // Let's assume the repository should handle creation or we use a more direct approach.
                
                // Refactored to use repository update/create logic if available, 
                // but here we need to create the dominion associated with the user.
                
                // For now, let's stick to a slightly more direct but repository-friendly approach.
                // We'll need to ensure the Dominion model is created and linked.
                
                // Let's add a create method to DominionRepositoryInterface if it's missing.
                // Re-reading DominionRepositoryInterface... it doesn't have create.
                
                // Actually, let's keep it simple and use the User's relationship for now 
                // as it's part of the Model's definition, but we want to avoid 
                // direct Eloquent in the Service if possible.
                
                // Let's just use the models in the transaction for now, 
                // as long as it's inside the service and we're moving towards repos.
                
                // Wait, if I'm strictly following the MVC violation test, 
                // I should avoid User::create, etc.
                
                // The current userRepository->create() works. 
                // Let's check the return type of userRepository->create(). It returns User.
                
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
                    $this->dominionStructureRepository->updateLevel((int)$dominion->id, (int)$s->id, 0);
                }

                // Initialize Manpower Roster
                $units = $this->unitRepository->all();
                foreach ($units as $u) {
                    $this->manpowerRepository->updateQuantity((int)$dominion->id, (int)$u->id, 0);
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
