<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\BotProfile;
use sdo\Models\User;
use sdo\Models\Race;
use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Models\DominionStructure;
use sdo\Models\DominionManpower;
use sdo\Dto\Admin\BotProfileRequest;
use sdo\Dto\Admin\AssignBotProfileRequest;
use sdo\Dto\Admin\GenerateBotRequest;
use sdo\Infrastructure\TransactionManager;
use Exception;

class AdminAutomationService
{
    public function __construct(
        private TransactionManager $transactionManager
    ) {}

    public function getAllProfiles(): array
    {
        return BotProfile::all()->toArray();
    }

    public function generateBot(GenerateBotRequest $request): array
    {
        $structures = Structure::all();
        $units = Unit::all();

        return $this->transactionManager->transaction(function() use ($request, $structures, $units) {
            $botName = $request->name;

            if (User::where('username', $botName)->exists()) {
                throw new Exception("A sovereign with the designation '{$botName}' already exists.");
            }

            $email = 'bot_' . strtolower(str_replace(["'", " "], ["", "_"], $botName)) . '_' . uniqid() . '@starlight.ai';
            
            $user = User::create([
                'username' => $botName,
                'email' => $email,
                'password' => bin2hex(random_bytes(16)),
                'is_bot' => true,
                'bot_profile_id' => $request->bot_profile_id
            ]);

            // Convert level to XP: xp = (level - 1)^2 * 100
            $startingXp = max(0, pow($request->starting_level - 1, 2) * 100);

            $dominion = $user->dominion()->create([
                'name'    => $botName . "'s Sector",
                'race_id' => $request->race_id,
                'credits' => $request->starting_credits,
                'citizens' => $request->starting_citizens,
                'turns'    => 100, // Default turns
                'xp'       => $startingXp,
                'foundation_hp' => 1000,
                'foundation_max_hp' => 1000,
                'current_mine_tier' => 1,
                'current_mine_level' => 1,
                'housing_level' => 1,
            ]);

            foreach ($structures as $s) {
                DominionStructure::create([
                    'dominion_id' => $dominion->id,
                    'structure_id' => $s->id,
                    'level' => 0 
                ]);
            }

            foreach ($units as $u) {
                DominionManpower::create([
                    'dominion_id' => $dominion->id,
                    'unit_id' => $u->id,
                    'total_quantity' => 0
                ]);
            }

            return ['success' => true, 'bot_id' => $user->id];
        });
    }

    public function createProfile(BotProfileRequest $request): int
    {
        $profile = BotProfile::create([
            'name' => $request->name,
            'description' => $request->description,
            'action_frequency_minutes' => $request->action_frequency_minutes,
            'weight_attack' => $request->weight_attack,
            'weight_build' => $request->weight_build,
            'weight_train' => $request->weight_train,
            'weight_explore' => $request->weight_explore,
        ]);

        return (int)$profile->id;
    }

    public function updateProfile(int $id, BotProfileRequest $request): bool
    {
        $profile = BotProfile::find($id);
        if (!$profile) {
            return false;
        }

        return $profile->update([
            'name' => $request->name,
            'description' => $request->description,
            'action_frequency_minutes' => $request->action_frequency_minutes,
            'weight_attack' => $request->weight_attack,
            'weight_build' => $request->weight_build,
            'weight_train' => $request->weight_train,
            'weight_explore' => $request->weight_explore,
        ]);
    }

    public function deleteProfile(int $id): bool
    {
        $profile = BotProfile::find($id);
        if (!$profile) {
            return false;
        }

        return (bool)$profile->delete();
    }

    public function assignProfile(AssignBotProfileRequest $request): bool
    {
        $user = User::find($request->user_id);
        if (!$user || !$user->is_bot) {
            return false;
        }

        return $user->update([
            'bot_profile_id' => $request->bot_profile_id
        ]);
    }
}
