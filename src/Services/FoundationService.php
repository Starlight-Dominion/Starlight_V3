<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Models\DominionStructure;
use sdo\Models\DominionManpower;
use sdo\Models\Unit;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class FoundationService
{
    public function __construct(private LogService $logService) {}

    public function getFoundationState(int $dominionId): array
    {
        $dominion = Dominion::with(['user', 'race'])->findOrFail($dominionId);
        
        $structures = Structure::all();
        $progress = DominionStructure::where('dominion_id', $dominionId)
            ->get()
            ->keyBy('structure_id');

        $manifest = [];
        foreach ($structures as $s) {
            $currentLevel = $progress->has($s->id) ? $progress->get($s->id)->level : 0;
            $nextLevel = $currentLevel + 1;
            
            $currentLevelData = null;
            if ($currentLevel > 0) {
                $currentLevelData = StructureLevel::where('structure_id', $s->id)
                    ->where('level', $currentLevel)
                    ->first();
            }

            $levelData = StructureLevel::where('structure_id', $s->id)
                ->where('level', $nextLevel)
                ->first();

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
        return Capsule::transaction(function() use ($dominionId) {
            $dominion = Dominion::lockForUpdate()->find($dominionId);
            $needed = $dominion->foundation_max_hp - $dominion->foundation_hp;
            if ($needed <= 0) throw new Exception("Integrity is already at 100%.");

            $cost = $needed * 10;
            if ($dominion->credits < $cost) throw new Exception("Insufficient credits for nano-repair.");

            $dominion->credits -= $cost;
            $dominion->foundation_hp = $dominion->foundation_max_hp;
            $dominion->save();

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
        return Capsule::transaction(function() use ($dominionId, $structureId) {
            $dominion = Dominion::lockForUpdate()->find($dominionId);
            
            // 1. Ensure Foundation is repaired before any structural changes
            if ($dominion->foundation_hp < $dominion->foundation_max_hp) {
                throw new Exception("Structural upgrade blocked. Repair integrity to 100% first.");
            }

            // 2. Determine next level
            $current = DominionStructure::where('dominion_id', $dominionId)
                ->where('structure_id', $structureId)
                ->first();
            
            $nextLevel = ($current ? $current->level : 0) + 1;

            $levelData = StructureLevel::where('structure_id', $structureId)
                ->where('level', $nextLevel)
                ->first();

            if (!$levelData) throw new Exception("Maximum tier reached for this structure.");
            if ($dominion->credits < $levelData->cost) throw new Exception("Insufficient credits.");

            // 3. Apply Upgrade
            $dominion->credits -= $levelData->cost;
            
            DominionStructure::updateOrInsert(
                ['dominion_id' => $dominionId, 'structure_id' => $structureId],
                ['level' => $nextLevel]
            );

            // 4. Process Structural Buffs
            
            // Foundation logic
            if ($structureId == 1) { 
                $dominion->foundation_max_hp = $levelData->buff_hp;
                $dominion->foundation_hp = $levelData->buff_hp;
            }

            // Unit Rewards (e.g., Mercenary Market)
            $this->grantUnitBuffs($dominionId, $levelData);

            $dominion->save();

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
                $unit = Unit::where('slug', $slug)->first();
                if ($unit) {
                    DominionManpower::where('dominion_id', $domId)
                        ->where('unit_id', $unit->id)
                        ->increment('total_quantity', $qty);
                }
            }
        }
    }
}
