<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\DominionStructureRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\LogService;
use Exception;

class FoundationService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private StructureRepositoryInterface $structureRepository,
        private DominionStructureRepositoryInterface $dominionStructureRepository,
        private UnitRepositoryInterface $unitRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private TransactionManager $transactionManager,
        private LogService $logService
    ) {}

    public function getFoundationState(int $dominionId): array
    {
        $dominion = $this->dominionRepository->findById($dominionId);
        if (!$dominion) throw new Exception("Dominion not found.");
        
        $structures = $this->structureRepository->all();
        $progress = $this->dominionStructureRepository->getStructuresByDominion($dominionId)->keyBy('structure_id');

        $manifest = [];
        foreach ($structures as $s) {
            $currentLevel = $progress->has($s->id) ? $progress->get($s->id)->level : 0;
            $nextLevel = $currentLevel + 1;
            
            $currentLevelData = null;
            if ($currentLevel > 0) {
                $currentLevelData = $this->structureRepository->findLevel((int)$s->id, $currentLevel);
            }

            $levelData = $this->structureRepository->findLevel((int)$s->id, $nextLevel);

            $manifest[$s->slug] = [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'current_level' => $currentLevel,
                'max_level' => $s->max_level,
                'current_upgrade' => $currentLevelData,
                'next_upgrade' => $levelData,
                'mod' => $progress->has($s->id) ? $progress->get($s->id)->mod_slot_1 : null
            ];
        }

        return [
            'dominion' => $dominion,
            'structures' => $manifest,
            'repair_cost' => ($dominion->foundation_max_hp - $dominion->foundation_hp) * 10
        ];
    }

    public function repair(int $dominionId): array
    {
        return $this->transactionManager->transaction(function() use ($dominionId) {
            $dominion = $this->dominionRepository->lockForUpdate($dominionId);
            if (!$dominion) throw new Exception("Dominion not found.");

            $needed = $dominion->foundation_max_hp - $dominion->foundation_hp;
            if ($needed <= 0) throw new Exception("Integrity is already at 100%.");

            $cost = $needed * 10;
            if ($dominion->credits < $cost) throw new Exception("Insufficient credits for nano-repair.");

            $this->dominionRepository->update($dominionId, [
                'credits' => $dominion->credits - $cost,
                'foundation_hp' => $dominion->foundation_max_hp
            ]);

            $this->logService->log(
                $dominionId,
                'foundation_repair',
                "Commander repaired foundation integrity.",
                $cost,
                ['hp_restored' => $needed]
            );

            return ['success' => true, 'message' => 'Integrity restored to nominal parameters.'];
        });
    }

    public function upgrade(int $dominionId, int $structureId): array
    {
        return $this->transactionManager->transaction(function() use ($dominionId, $structureId) {
            $dominion = $this->dominionRepository->lockForUpdate($dominionId);
            if (!$dominion) throw new Exception("Dominion not found.");
            
            // 1. Ensure Foundation is repaired before any structural changes
            if ($dominion->foundation_hp < $dominion->foundation_max_hp) {
                throw new Exception("Structural upgrade blocked. Repair integrity to 100% first.");
            }

            // 2. Determine next level
            $current = $this->dominionStructureRepository->findByDominionAndStructure($dominionId, $structureId);
            $nextLevel = ($current ? $current->level : 0) + 1;

            $levelData = $this->structureRepository->findLevel($structureId, $nextLevel);

            if (!$levelData) throw new Exception("Maximum tier reached for this structure.");
            if ($dominion->credits < $levelData->cost) throw new Exception("Insufficient credits.");

            // 3. Apply Upgrade
            $this->dominionRepository->update($dominionId, [
                'credits' => $dominion->credits - $levelData->cost
            ]);
            
            $this->dominionStructureRepository->updateLevel($dominionId, $structureId, $nextLevel);

            // 4. Process Structural Buffs
            
            // Foundation logic
            if ($structureId == 1) { 
                $this->dominionRepository->update($dominionId, [
                    'foundation_max_hp' => $levelData->buff_hp,
                    'foundation_hp' => $levelData->buff_hp
                ]);
            }

            // Unit Rewards (e.g., Mercenary Market)
            $this->grantUnitBuffs($dominionId, $levelData);

            $this->logService->log(
                $dominionId,
                'foundation_upgrade',
                "Commander upgraded structure #{$structureId} to Tier {$nextLevel}.",
                (int)$levelData->cost,
                ['structure_id' => $structureId, 'new_level' => $nextLevel]
            );

            return ['success' => true, 'message' => "Upgrade to Tier {$nextLevel} initialized."];
        });
    }

    private function grantUnitBuffs(int $domId, object $levelData): void
    {
        $mapping = [
            'guards' => $levelData->buff_unit_guards ?? 0,
            'soldiers' => $levelData->buff_unit_soldiers ?? 0,
            'spies' => $levelData->buff_unit_spies ?? 0,
            'sentries' => $levelData->buff_unit_sentries ?? 0
        ];

        foreach ($mapping as $slug => $qty) {
            if ($qty > 0) {
                $unit = $this->unitRepository->findBySlug($slug);
                if ($unit) {
                    $this->manpowerRepository->updateQuantity($domId, (int)$unit->id, $qty);
                }
            }
        }
    }
}
