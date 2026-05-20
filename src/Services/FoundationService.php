<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class FoundationService
{
    public function getFoundationState(int $dominionId): array
    {
        $dominion = Dominion::with(['user', 'race'])->findOrFail($dominionId);
        
        // Fetch all structure types and the dominion's current levels
        $structures = Capsule::table('structures')->get();
        $progress = Capsule::table('dominion_structures')
            ->where('dominion_id', $dominionId)
            ->get()
            ->keyBy('structure_id');

        $manifest = [];
        foreach ($structures as $s) {
            $currentLevel = $progress->has($s->id) ? $progress->get($s->id)->level : 0;
            $nextLevel = $currentLevel + 1;
            
            $levelData = Capsule::table('structure_levels')
                ->where('structure_id', $s->id)
                ->where('level', $nextLevel)
                ->first();

            $manifest[$s->slug] = [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'current_level' => $currentLevel,
                'max_level' => $s->max_level,
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
            if ($dominion->gold < $cost) throw new Exception("Insufficient credits for nano-repair.");

            $dominion->gold -= $cost;
            $dominion->foundation_hp = $dominion->foundation_max_hp;
            $dominion->save();

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
            $current = Capsule::table('dominion_structures')
                ->where('dominion_id', $dominionId)
                ->where('structure_id', $structureId)
                ->first();
            
            $nextLevel = ($current ? $current->level : 0) + 1;

            $levelData = Capsule::table('structure_levels')
                ->where('structure_id', $structureId)
                ->where('level', $nextLevel)
                ->first();

            if (!$levelData) throw new Exception("Maximum tier reached for this structure.");
            if ($dominion->gold < $levelData->cost) throw new Exception("Insufficient credits.");

            // 3. Apply Upgrade
            $dominion->gold -= $levelData->cost;
            
            Capsule::table('dominion_structures')->updateOrInsert(
                ['dominion_id' => $dominionId, 'structure_id' => $structureId],
                ['level' => $nextLevel]
            );

            // 4. If Foundation upgraded, increase max HP
            if ($structureId == 1) { // Foundation ID
                $dominion->foundation_max_hp = $levelData->buff_hp;
                $dominion->foundation_hp = $levelData->buff_hp;
            }

            $dominion->save();
            return ['success' => true, 'message' => "Upgrade to Tier {$nextLevel} initialized."];
        });
    }
}