<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\Structure;
use Illuminate\Support\Collection;

interface StructureRepositoryInterface
{
    public function findById(int $id): ?Structure;
    public function findBySlug(string $slug): ?Structure;
    public function all(): Collection;
    public function create(array $data): Structure;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    
    // Levels management
    public function findLevel(int $structureId, int $level): ?\sdo\Models\StructureLevel;
    public function allLevels(int $structureId): Collection;
    public function updateLevel(int $structureId, int $level, array $data): bool;
    public function addLevel(array $data): bool;
}
