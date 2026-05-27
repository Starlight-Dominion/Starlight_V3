<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Models\ArmoryItem;
use sdo\Models\ArmoryUnitType;
use sdo\Models\ArmoryCategory;
use sdo\Models\Race;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class AdminGameDataService
{
    public function __construct(
        private UnitRepositoryInterface $unitRepository,
        private StructureRepositoryInterface $structureRepository,
        private ArmoryRepositoryInterface $armoryRepository
    ) {}

    // --- Units ---
    public function getAllUnits(): array
    {
        return $this->unitRepository->all()->toArray();
    }

    public function updateUnit(int $id, array $data): bool
    {
        $unit = $this->unitRepository->findById($id);
        if (!$unit) return false;
        
        $unitColumns = Capsule::schema()->getColumnListing('units');
        $filteredData = array_filter($data, fn($key) => in_array($key, $unitColumns), ARRAY_FILTER_USE_KEY);

        return $this->unitRepository->update($id, $filteredData);
    }

    public function addUnit(array $data): int
    {
        $unit = $this->unitRepository->create($data);
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
        $structure = $this->structureRepository->create($data);
        return (int)$structure->id;
    }

    public function updateStructure(int $id, array $data): bool
    {
        $columns = Capsule::schema()->getColumnListing('structures');
        $filteredData = array_filter($data, fn($key) => in_array($key, $columns), ARRAY_FILTER_USE_KEY);
        return $this->structureRepository->update($id, $filteredData);
    }

    public function deleteStructure(int $id): bool
    {
        return $this->structureRepository->delete($id);
    }

    public function updateStructureLevel(int $structureId, int $level, array $data): bool
    {
        $columns = Capsule::schema()->getColumnListing('structure_levels');
        $filteredData = array_filter($data, fn($key) => in_array($key, $columns), ARRAY_FILTER_USE_KEY);
        return $this->structureRepository->updateLevel($structureId, $level, $filteredData);
    }

    public function addStructureLevel(array $data): bool
    {
        return $this->structureRepository->addLevel($data);
    }

    // --- Armory ---
    public function getAllArmoryItems(): array
    {
        return $this->armoryRepository->all()->toArray();
    }

    public function updateArmoryItem(int $id, array $data): bool
    {
        $armoryColumns = Capsule::schema()->getColumnListing('armory_items');
        $filteredData = array_filter($data, fn($key) => in_array($key, $armoryColumns), ARRAY_FILTER_USE_KEY);
        return $this->armoryRepository->update($id, $filteredData);
    }

    public function addArmoryItem(array $data): int
    {
        $item = $this->armoryRepository->create($data);
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
        return Race::all()->toArray();
    }

    public function updateRace(int $id, array $data): bool
    {
        $race = Race::findOrFail($id);
        $raceColumns = Capsule::schema()->getColumnListing('races');
        $filteredData = array_filter($data, fn($key) => in_array($key, $raceColumns), ARRAY_FILTER_USE_KEY);
        return $race->update($filteredData);
    }
}
