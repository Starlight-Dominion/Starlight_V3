<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class AdminService
{
    private function getPdo(): \PDO
    {
        return Capsule::connection()->getPdo();
    }

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

    public function searchKingdoms(string $query): array
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

    public function getAllKingdoms(int $limit = 50): array
    {
        return Dominion::with('user')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function updateKingdomStats(int $kingdomId, array $stats): bool
    {
        $dominion = Dominion::with('user')->findOrFail($kingdomId);
        
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
        return Capsule::table('units')->get()->toArray();
    }

    public function updateUnit(int $id, array $data): bool
    {
        return Capsule::table('units')->where('id', $id)->update($data) > 0;
    }

    public function addUnit(array $data): int
    {
        return Capsule::table('units')->insertGetId($data);
    }

    public function deleteUnit(int $id): bool
    {
        return Capsule::table('units')->where('id', $id)->delete() > 0;
    }

    // --- Overhauled Structures Management ---
    public function getAllStructures(): array
    {
        return Capsule::table('structures')->get()->toArray();
    }

    public function getStructureLevels(int $structureId): array
    {
        return Capsule::table('structure_levels')->where('structure_id', $structureId)->orderBy('level', 'asc')->get()->toArray();
    }

    public function addStructure(array $data): int
    {
        return Capsule::table('structures')->insertGetId($data);
    }

    public function updateStructure(int $id, array $data): bool
    {
        return Capsule::table('structures')->where('id', $id)->update($data) > 0;
    }

    public function deleteStructure(int $id): bool
    {
        return Capsule::table('structures')->where('id', $id)->delete() > 0;
    }

    public function updateStructureLevel(int $structureId, int $level, array $data): bool
    {
        return Capsule::table('structure_levels')
            ->where('structure_id', $structureId)
            ->where('level', $level)
            ->update($data) > 0;
    }

    public function addStructureLevel(array $data): bool
    {
        return Capsule::table('structure_levels')->insert($data);
    }

    // --- Armory Items Management ---
    public function getAllArmoryItems(): array
    {
        return Capsule::table('armory_items')->orderBy('unit_type', 'asc')->get()->toArray();
    }

    public function updateArmoryItem(int $id, array $data): bool
    {
        return Capsule::table('armory_items')->where('id', $id)->update($data) > 0;
    }

    public function addArmoryItem(array $data): int
    {
        return Capsule::table('armory_items')->insertGetId($data);
    }

    public function deleteArmoryItem(int $id): bool
    {
        return Capsule::table('armory_items')->where('id', $id)->delete() > 0;
    }

    public function getArmoryUnitTypes(): array
    {
        return Capsule::table('armory_unit_types')->get()->toArray();
    }

    public function getArmoryCategories(): array
    {
        return Capsule::table('armory_categories')->get()->toArray();
    }

    // --- Logs Oversight ---
    public function getRecentBattleLogs(int $limit = 50): array
    {
        return Capsule::table('battle_logs')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
