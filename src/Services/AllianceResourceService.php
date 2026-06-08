<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Repositories\Interfaces\AllianceRepositoryInterface;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class AllianceResourceService
{
    private array $structureDefinitions = [];

    private const TRACKS = [
        'Economy' => ['command_nexus', 'trade_federation_center', 'mercantile_exchange'],
        'Defense' => ['citadel_shield_array', 'planetary_defense_grid', 'orbital_shield_generator'],
        'Offense' => ['orbital_training_grounds', 'starfighter_academy', 'warforge_arsenal']
        // Simplified tracks for now, can expand later
    ];

    public function __construct(
        private AllianceRepositoryInterface $allianceRepository,
        private UserRepositoryInterface $userRepository,
        private TransactionManager $transactionManager
    ) {
        // Load definitions if they exist, or use default
        $this->structureDefinitions = [
            'command_nexus' => ['name' => 'Command Nexus', 'description' => 'Improves coordination.', 'cost' => 5000000],
            'orbital_training_grounds' => ['name' => 'Orbital Grounds', 'description' => 'Training speed bonus.', 'cost' => 5000000],
            'citadel_shield_array' => ['name' => 'Citadel Shields', 'description' => 'Planetary defense bonus.', 'cost' => 5000000]
        ];
    }

    public function getBankPayload(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user || !$user->alliance_id) throw new Exception("You are not in an alliance.");

        $alliance = $user->alliance;
        $logs = $this->allianceRepository->getBankLogs($alliance->id, 20);

        return [
            'bank_credits' => (int)$alliance->bank_credits,
            'user_credits' => (int)$user->dominion->credits,
            'is_leader' => ($user->id === $alliance->leader_id),
            'can_bank_withdraw' => (bool)($user->allianceRole?->can_bank_withdraw ?? false),
            'logs' => $logs->toArray(),
            'advisor' => ['advice' => "Collective Economic Reserve Alpha-9. Manage your alliance's wealth."]
        ];
    }

    public function donate(int $userId, int $amount, string $comment = ''): void
    {
        if ($amount <= 0) throw new Exception("Invalid donation amount.");

        $this->transactionManager->transaction(function () use ($userId, $amount, $comment) {
            $user = $this->userRepository->findById($userId);
            if (!$user || !$user->alliance_id) throw new Exception("You are not in an alliance.");

            $dominion = $user->dominion;
            if ($dominion->credits < $amount) throw new Exception("Insufficient credits.");

            $alliance = $this->allianceRepository->findById((int)$user->alliance_id);

            $dominion->update(['credits' => $dominion->credits - $amount]);
            $alliance->increment('bank_credits', $amount);

            $this->allianceRepository->logBankAction([
                'alliance_id' => $alliance->id,
                'user_id' => $userId,
                'action_type' => 'deposit',
                'amount' => $amount,
                'comment' => $comment ?: "Donation from " . $user->username
            ]);
        });
    }

    public function withdraw(int $userId, int $amount): void
    {
        if ($amount <= 0) throw new Exception("Invalid withdrawal amount.");

        $this->transactionManager->transaction(function () use ($userId, $amount) {
            $user = $this->userRepository->findById($userId);
            $alliance = $this->allianceRepository->findById((int)$user->alliance_id);

            if (!$alliance || !$user->allianceRole->can_bank_withdraw) {
                throw new Exception("Withdrawal authorization required.");
            }

            if ($alliance->bank_credits < $amount) {
                throw new Exception("Insufficient alliance funds.");
            }

            $alliance->decrement('bank_credits', $amount);
            $user->dominion->increment('credits', $amount);

            $this->allianceRepository->logBankAction([
                'alliance_id' => $alliance->id,
                'user_id' => $userId,
                'action_type' => 'withdrawal',
                'amount' => $amount,
                'comment' => "Withdrawal by " . $user->username
            ]);
        });
    }

    public function getStructuresPayload(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user || !$user->alliance_id) throw new Exception("You are not in an alliance.");

        $alliance = $user->alliance;
        $owned = $this->allianceRepository->getStructures($alliance->id)->pluck('structure_key')->toArray();
        $ownedMap = array_flip($owned);

        $slots = [];
        foreach (self::TRACKS as $title => $track) {
            $currentLevel = 0;
            foreach ($track as $index => $key) {
                if (isset($ownedMap[$key])) $currentLevel = $index + 1;
                else break;
            }

            $isMaxed = ($currentLevel >= count($track));
            $nextKey = !$isMaxed ? $track[$currentLevel] : null;
            $nextDetails = null;
            if ($nextKey && isset($this->structureDefinitions[$nextKey])) {
                $def = $this->structureDefinitions[$nextKey];
                $nextDetails = [
                    'key' => $nextKey,
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'cost' => $def['cost'],
                    'can_afford' => ($alliance->bank_credits >= $def['cost'])
                ];
            }

            $slots[] = [
                'title' => $title,
                'current_level' => $currentLevel,
                'max_level' => count($track),
                'is_maxed' => $isMaxed,
                'next' => $nextDetails
            ];
        }

        return [
            'bank_credits' => (int)$alliance->bank_credits,
            'can_purchase_structures' => (bool)($user->allianceRole?->can_purchase_structures ?? false),
            'slots' => $slots
        ];
    }

    public function purchaseStructure(int $userId, string $key): void
    {
        if (!isset($this->structureDefinitions[$key])) throw new Exception("Invalid structure.");

        $this->transactionManager->transaction(function () use ($userId, $key) {
            $user = $this->userRepository->findById($userId);
            if (!$user->allianceRole->can_purchase_structures) throw new Exception("Unauthorized.");

            $alliance = $this->allianceRepository->findById((int)$user->alliance_id);
            $def = $this->structureDefinitions[$key];

            if ($alliance->bank_credits < $def['cost']) throw new Exception("Insufficient alliance funds.");

            // Check if already owned
            if ($this->allianceRepository->findStructure($alliance->id, $key)) {
                throw new Exception("Structure already deployed.");
            }

            $alliance->decrement('bank_credits', $def['cost']);
            $this->allianceRepository->createStructure([
                'alliance_id' => $alliance->id,
                'structure_key' => $key,
                'level' => 1
            ]);
        });
    }
}
