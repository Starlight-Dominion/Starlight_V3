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
        $totalCredits = (float)Dominion::sum('credits');
        $totalCitizens = (float)Dominion::sum('citizens');
        $totalManpower = (float)\sdo\Models\DominionManpower::sum('total_quantity');
        
        return [
            'total_users' => $playerCount,
            'total_kingdoms' => $kingdomCount,
            'total_credits' => $totalCredits,
            'total_citizens' => $totalCitizens,
            'total_manpower' => $totalManpower,
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

    public function getKingdomFullProfile(int $dominionId): array
    {
        $dominion = Dominion::with(['user', 'manpower.unit', 'structures.structure', 'race'])
            ->findOrFail($dominionId);

        // Get armory items separately since relationship name in model is non-standard or missing
        $armory = \sdo\Models\DominionArmoryItem::with('item')
            ->where('kingdom_id', $dominionId)
            ->get()
            ->toArray();

        return [
            'dominion' => $dominion->toArray(),
            'armory' => $armory,
            // Provide all available units/structures for "granting" new assets
            'all_units' => Unit::all()->toArray(),
            'all_structures' => Structure::all()->toArray(),
            'all_armory' => ArmoryItem::all()->toArray()
        ];
    }

    public function updateDominionStats(int $dominionId, array $stats): bool
    {
        $dominion = Dominion::with('user')->findOrFail($dominionId);
        
        $domColumns = Capsule::schema()->getColumnListing('dominions');
        $userColumns = Capsule::schema()->getColumnListing('users');
        
        foreach ($stats as $field => $value) {
            if (in_array($field, $domColumns)) {
                $dominion->$field = $value;
            } elseif (in_array($field, $userColumns) && $dominion->user) {
                if ($field === 'is_admin' || $field === 'is_bot') {
                    $value = (bool)$value;
                }
                if ($field === 'stasis_until' && (empty($value) || $value === 'null')) {
                    $value = null;
                }
                if ($field === 'password' && !empty($value)) {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                $dominion->user->$field = $value;
            }
        }

        return $dominion->push();
    }

    public function updateKingdomManpower(int $dominionId, int $unitId, int $total, int $stabled): bool
    {
        return \sdo\Models\DominionManpower::updateOrCreate(
            ['dominion_id' => $dominionId, 'unit_id' => $unitId],
            ['total_quantity' => $total, 'stabled_quantity' => $stabled]
        )->exists;
    }

    public function updateKingdomStructure(int $dominionId, int $structureId, int $level): bool
    {
        return \sdo\Models\DominionStructure::updateOrCreate(
            ['dominion_id' => $dominionId, 'structure_id' => $structureId],
            ['level' => $level]
        )->exists;
    }

    public function updateKingdomArmory(int $dominionId, int $itemId, int $quantity, bool $equipped): bool
    {
        return \sdo\Models\DominionArmoryItem::updateOrCreate(
            ['kingdom_id' => $dominionId, 'item_id' => $itemId],
            ['quantity' => $quantity, 'is_equipped' => $equipped]
        )->exists;
    }

    // --- Units Management ---
    public function getAllUnits(): array
    {
        return Unit::all()->toArray();
    }

    public function updateUnit(int $id, array $data): bool
    {
        $unit = Unit::findOrFail($id);
        
        // Defensive check: only update columns that actually exist in the units table
        $unitColumns = Capsule::schema()->getColumnListing('units');

        $filteredData = array_filter($data, fn($key) => in_array($key, $unitColumns), ARRAY_FILTER_USE_KEY);

        return $unit->update($filteredData);
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

        // Defensive check: only update columns that actually exist
        $armoryColumns = Capsule::schema()->getColumnListing('armory_items');

        $filteredData = array_filter($data, fn($key) => in_array($key, $armoryColumns), ARRAY_FILTER_USE_KEY);

        return $item->update($filteredData);
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

    // --- Audit Trail ---\n    public function logAdminAction(int $adminId, string $action, string $description, array $metadata = []): void\n    {\n        \sdo\Models\GameLog::create([\n            'dominion_id' => $adminId,\n            'action' => 'ADMIN_' . strtoupper($action),\n            'description' => $description,\n            'metadata' => $metadata\n        ]);\n    }\n\n    public function getAuditLogs(int $limit = 100): array\n    {\n        return \sdo\Models\GameLog::where('action', 'LIKE', 'ADMIN_%')\n            ->orderBy('id', 'desc')\n            ->limit($limit)\n            ->get()\n            ->toArray();\n    }\n\n    // --- Logs Oversight ---
    public function getRecentBattleLogs(int $limit = 50): array
    {
        return BattleLog::orderBy('battle_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
