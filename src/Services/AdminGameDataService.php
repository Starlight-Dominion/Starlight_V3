<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use sdo\Repositories\Interfaces\RaceRepositoryInterface;
use Exception;

class AdminGameDataService
{
    public function __construct(
        private UnitRepositoryInterface $unitRepository,
        private StructureRepositoryInterface $structureRepository,
        private ArmoryRepositoryInterface $armoryRepository,
        private RaceRepositoryInterface $raceRepository
    ) {}

    // --- Units ---
    public function getAllUnits(): array
    {
        return $this->unitRepository->all()->toArray();
    }

    public function updateUnit(int $id, array $data): bool
    {
        $unitColumns = $this->unitRepository->getColumns();
        $filteredData = $this->sanitizeData($data, $unitColumns);

        return $this->unitRepository->update($id, $filteredData);
    }

    public function addUnit(array $data): int
    {
        $unit = $this->unitRepository->create($this->sanitizeData($data));
        return (int)$unit->id;
    }

    public function deleteUnit(int $id): bool
    {
        return $this->unitRepository->delete($id);
    }

    // --- Structures ---
    public function getAllStructures(): array
    {
        return $this->structureRepository->all()->toArray();
    }

    public function getStructureLevels(int $structureId): array
    {
        return $this->structureRepository->allLevels($structureId)->toArray();
    }

    public function addStructure(array $data): int
    {
        $structure = $this->structureRepository->create($this->sanitizeData($data));
        return (int)$structure->id;
    }

    public function updateStructure(int $id, array $data): bool
    {
        $columns = $this->structureRepository->getColumns();
        $filteredData = $this->sanitizeData($data, $columns);
        return $this->structureRepository->update($id, $filteredData);
    }

    public function deleteStructure(int $id): bool
    {
        return $this->structureRepository->delete($id);
    }

    public function updateStructureLevel(int $structureId, int $level, array $data): bool
    {
        $columns = $this->structureRepository->getLevelColumns();
        $filteredData = $this->sanitizeData($data, $columns);
        return $this->structureRepository->updateLevel($structureId, $level, $filteredData);
    }

    public function addStructureLevel(array $data): bool
    {
        return $this->structureRepository->addLevel($this->sanitizeData($data));
    }

    // --- Armory ---
    public function getAllArmoryItems(): array
    {
        return $this->armoryRepository->all()->toArray();
    }

    public function updateArmoryItem(int $id, array $data): bool
    {
        $armoryColumns = $this->armoryRepository->getColumns();
        $filteredData = $this->sanitizeData($data, $armoryColumns);
        return $this->armoryRepository->update($id, $filteredData);
    }

    public function addArmoryItem(array $data): int
    {
        $item = $this->armoryRepository->create($this->sanitizeData($data));
        return (int)$item->id;
    }

    public function deleteArmoryItem(int $id): bool
    {
        return $this->armoryRepository->delete($id);
    }

    public function getArmoryUnitTypes(): array
    {
        return $this->armoryRepository->allUnitTypes()->toArray();
    }

    public function getArmoryCategories(): array
    {
        return $this->armoryRepository->allCategories()->toArray();
    }

    // --- Races ---
    public function getAllRaces(): array
    {
        return $this->raceRepository->all()->toArray();
    }

    public function updateRace(int $id, array $data): bool
    {
        $raceColumns = $this->raceRepository->getColumns();
        $filteredData = $this->sanitizeData($data, $raceColumns);
        return $this->raceRepository->update($id, $filteredData);
    }

    private function sanitizeData(array $data, ?array $columns = null): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if ($columns !== null && !in_array($key, $columns)) {
                continue;
            }
            
            // Convert empty strings to null for database compatibility
            $sanitized[$key] = ($value === '') ? null : $value;
        }
        return $sanitized;
    }
}
