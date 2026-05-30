<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface DominionStructureRepositoryInterface
{
    public function getStructuresByDominion(int $dominionId): Collection;
    public function findByDominionAndStructure(int $dominionId, int $structureId): ?\sdo\Models\DominionStructure;
    public function updateLevel(int $dominionId, int $structureId, int $level): bool;
    public function sumStructureLevelBuff(int $dominionId, string $column): float;
    public function sumMultipleStructureLevelBuffs(int $dominionId, array $columns): array;
    public function updateOrCreate(int $dominionId, int $structureId, array $data): bool;
}
