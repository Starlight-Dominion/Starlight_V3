<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use DateTime;

class AdminPlayerService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private UserRepositoryInterface $userRepository,
        private UnitRepositoryInterface $unitRepository,
        private StructureRepositoryInterface $structureRepository,
        private ArmoryRepositoryInterface $armoryRepository
    ) {}

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

        $armory = \sdo\Models\DominionArmoryItem::with('item')
            ->where('kingdom_id', $dominionId)
            ->get()
            ->toArray();

        return [
            'dominion' => $dominion->toArray(),
            'armory' => $armory,
            'all_units' => $this->unitRepository->all()->toArray(),
            'all_structures' => $this->structureRepository->all()->toArray(),
            'all_armory' => $this->armoryRepository->all()->toArray()
        ];
    }

    public function updateDominionStats(int $dominionId, array $stats): bool
    {
        $dominion = Dominion::with('user')->findOrFail($dominionId);
        
        $domColumns = Capsule::schema()->getColumnListing('dominions');
        $userColumns = Capsule::schema()->getColumnListing('users');
        
        $normalize = function($model, $field, $value) {
            $casts = $model->getCasts();
            $type = $casts[$field] ?? null;

            if ($type === 'datetime' && (empty($value) || $value === 'null')) {
                return null;
            }
            if ($type === 'boolean') {
                return ($value === 'true' || $value === '1' || $value === 1 || $value === true);
            }
            return $value;
        };

        foreach ($stats as $field => $value) {
            if (in_array($field, $domColumns)) {
                $dominion->$field = $normalize($dominion, $field, $value);
            } elseif (in_array($field, $userColumns) && $dominion->user) {
                if ($field === 'password') {
                    if (!empty($value)) {
                        $dominion->user->password = $value;
                    }
                    continue;
                }
                $dominion->user->$field = $normalize($dominion->user, $field, $value);
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
}
