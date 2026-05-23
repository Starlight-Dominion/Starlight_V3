<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Models\ArmoryItem;
use sdo\Models\ArmoryUnitType;
use sdo\Models\ArmoryCategory;
use sdo\Models\BattleLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class AdminService
{
    public function getSystemStats(): array
    {
        $playerCount = User::count();
        $kingdomCount = Dominion::count();
        
        return [
            'total_users' => $playerCount,
            'total_kingdoms' => $kingdomCount,
            'server_time' => (new \DateTime('now', new \DateTimeZone('America/New_York')))->format('H:i:s T')
        ];
    }

    public function searchDominions(string $query): array
    {
        return Dominion::with('user')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhereHas('user', function($q) use ($query) {
                $q->where('username', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getAllDominions(int $limit = 50): array
    {
        return Dominion::with('user')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function updateDominionStats(int $dominionId, array $stats): bool
    {
        $dominion = Dominion::with('user')->findOrFail($dominionId);
        
        $allowedDominion = ['credits', 'xp', 'turns', 'citizens', 'name'];
        $allowedUser = ['username'];
        
        foreach ($stats as $field => $value) {
            if (in_array($field, $allowedDominion)) {
                $dominion->$field = $value;
            } elseif (in_array($field, $allowedUser) && $dominion->user) {
                $dominion->user->$field = $value;
            }
        }

        return $dominion->push();
    }

    // --- Units Management ---
    public function getAllUnits(): array
    {
        return Unit::all()->toArray();
    }

    public function updateUnit(int $id, array $data): bool
    {
        $unit = Unit::findOrFail($id);
        return $unit->update($data);
    }

    public function addUnit(array $data): int
    {
        $unit = Unit::create($data);
        return (int)$unit->id;
    }

    public function deleteUnit(int $id): bool
    {
        return Unit::where('id', $id)->delete() > 0;
    }

    // --- Overhauled Structures Management ---
    public function getAllStructures(): array
    {
        return Structure::all()->toArray();
    }

    public function getStructureLevels(int $structureId): array
    {
        return StructureLevel::where('structure_id', $structureId)
            ->orderBy('level', 'asc')
            ->get()
            ->toArray();
    }

    public function addStructure(array $data): int
    {
        $structure = Structure::create($data);
        return (int)$structure->id;
    }

    public function updateStructure(int $id, array $data): bool
    {
        $structure = Structure::findOrFail($id);
        return $structure->update($data);
    }

    public function deleteStructure(int $id): bool
    {
        return Structure::where('id', $id)->delete() > 0;
    }

    public function updateStructureLevel(int $structureId, int $level, array $data): bool
    {
        return StructureLevel::where('structure_id', $structureId)
            ->where('level', $level)
            ->update($data) > 0;
    }

    public function addStructureLevel(array $data): bool
    {
        return StructureLevel::create($data)->exists;
    }

    // --- Armory Items Management ---
    public function getAllArmoryItems(): array
    {
        return ArmoryItem::orderBy('unit_type', 'asc')->get()->toArray();
    }

    public function updateArmoryItem(int $id, array $data): bool
    {
        $item = ArmoryItem::findOrFail($id);
        return $item->update($data);
    }

    public function addArmoryItem(array $data): int
    {
        $item = ArmoryItem::create($data);
        return (int)$item->id;
    }

    public function deleteArmoryItem(int $id): bool
    {
        return ArmoryItem::where('id', $id)->delete() > 0;
    }

    public function getArmoryUnitTypes(): array
    {
        return ArmoryUnitType::all()->toArray();
    }

    public function getArmoryCategories(): array
    {
        return ArmoryCategory::all()->toArray();
    }

    // --- Logs Oversight ---
    public function getRecentBattleLogs(int $limit = 50): array
    {
        return BattleLog::orderBy('battle_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
