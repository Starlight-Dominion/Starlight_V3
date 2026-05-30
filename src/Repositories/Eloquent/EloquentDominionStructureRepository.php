<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\DominionStructure;
use sdo\Repositories\Interfaces\DominionStructureRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDominionStructureRepository implements DominionStructureRepositoryInterface
{
    public function getStructuresByDominion(int $dominionId): Collection
    {
        return DominionStructure::with(['structure', 'levelData'])
            ->where('dominion_id', $dominionId)
            ->get();
    }

    public function findByDominionAndStructure(int $dominionId, int $structureId): ?DominionStructure
    {
        return DominionStructure::where('dominion_id', $dominionId)
            ->where('structure_id', $structureId)
            ->first();
    }

    public function updateLevel(int $dominionId, int $structureId, int $level): bool
    {
        $structure = $this->findByDominionAndStructure($dominionId, $structureId);
        if (!$structure) {
            $structure = new DominionStructure();
            $structure->dominion_id = $dominionId;
            $structure->structure_id = $structureId;
            $structure->level = $level;
            return $structure->save();
        }

        return $structure->update(['level' => $level]);
    }

    public function sumStructureLevelBuff(int $dominionId, string $column): float
    {
        return (float)DominionStructure::join('structure_levels', function($join) {
                $join->on('dominion_structures.structure_id', '=', 'structure_levels.structure_id')
                    ->on('dominion_structures.level', '=', 'structure_levels.level');
            })
            ->where('dominion_structures.dominion_id', $dominionId)
            ->sum("structure_levels.$column");
    }

    public function sumMultipleStructureLevelBuffs(int $dominionId, array $columns): array
    {
        $selects = [];
        foreach ($columns as $alias => $column) {
            $selects[] = "SUM(structure_levels.$column) as $alias";
        }

        $res = DominionStructure::join('structure_levels', function($join) {
                $join->on('dominion_structures.structure_id', '=', 'structure_levels.structure_id')
                    ->on('dominion_structures.level', '=', 'structure_levels.level');
            })
            ->where('dominion_structures.dominion_id', $dominionId)
            ->selectRaw(implode(', ', $selects))
            ->first();

        return $res ? $res->toArray() : [];
    }

    public function updateOrCreate(int $dominionId, int $structureId, array $data): bool
    {
        return (bool)DominionStructure::updateOrCreate(
            ['dominion_id' => $dominionId, 'structure_id' => $structureId],
            $data
        );
    }
}
