<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\DominionStructureRepositoryInterface;
use sdo\Repositories\Interfaces\DominionArmoryRepositoryInterface;
use Exception;
use DateTime;

class AdminPlayerService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private UserRepositoryInterface $userRepository,
        private UnitRepositoryInterface $unitRepository,
        private StructureRepositoryInterface $structureRepository,
        private ArmoryRepositoryInterface $armoryRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private DominionStructureRepositoryInterface $dominionStructureRepository,
        private DominionArmoryRepositoryInterface $dominionArmoryRepository
    ) {}

    public function searchDominions(string $query): array
    {
        return $this->dominionRepository->search($query)->toArray();
    }

    public function getAllDominions(int $limit = 50): array
    {
        return $this->dominionRepository->getAll($limit)->toArray();
    }

    public function getKingdomFullProfile(int $dominionId): array
    {
        $dominion = $this->dominionRepository->findFullProfile($dominionId);
        if (!$dominion) throw new Exception("Dominion not found.");

        $armory = $this->dominionArmoryRepository->getInventory($dominionId)->toArray();

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
        $dominion = $this->dominionRepository->findById($dominionId);
        if (!$dominion) throw new Exception("Dominion not found.");
        
        $domColumns = $this->dominionRepository->getColumns();
        $userColumns = $this->userRepository->getColumns();
        
        $domChanges = [];
        $userChanges = [];

        foreach ($stats as $field => $value) {
            // Sanitize empty strings to null for database compatibility (prevents strict mode errors on int/date columns)
            if ($value === '') {
                $value = null;
            }

            if (in_array($field, $domColumns)) {
                $domChanges[$field] = $value;
            } elseif (in_array($field, $userColumns)) {
                if ($field === 'password' && empty($value)) {
                    continue;
                }
                $userChanges[$field] = $value;
            }
        }

        $success = true;
        if (!empty($domChanges)) {
            $success = $success && $this->dominionRepository->update($dominionId, $domChanges);
        }
        if (!empty($userChanges) && $dominion->user_id) {
            $success = $success && $this->userRepository->update((int)$dominion->user_id, $userChanges);
        }

        return $success;
    }

    public function updateKingdomManpower(int $dominionId, int $unitId, int $total, int $stabled): bool
    {
        return $this->manpowerRepository->setQuantityWithStable($dominionId, $unitId, $total, $stabled);
    }

    public function updateKingdomStructure(int $dominionId, int $structureId, int $level): bool
    {
        return $this->dominionStructureRepository->updateOrCreate($dominionId, $structureId, ['level' => $level]);
    }

    public function updateKingdomArmory(int $dominionId, int $itemId, int $quantity, bool $equipped): bool
    {
        return $this->dominionArmoryRepository->updateOrCreate($dominionId, $itemId, [
            'quantity' => $quantity, 
            'is_equipped' => $equipped
        ]);
    }
}
