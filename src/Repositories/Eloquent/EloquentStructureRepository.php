<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Support\TickSummaryMaintainer;
use Illuminate\Support\Collection;

class EloquentStructureRepository implements StructureRepositoryInterface
{
    public function findById(int $id): ?Structure
    {
        return Structure::find($id);
    }

    public function findBySlug(string $slug): ?Structure
    {
        return Structure::where('slug', $slug)->first();
    }

    public function all(): Collection
    {
        return Structure::all();
    }

    public function create(array $data): Structure
    {
        return Structure::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $structure = $this->findById($id);
        return $structure ? $structure->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $impactedDominionIds = Capsule::table('dominion_structures')
            ->where('structure_id', $id)
            ->pluck('dominion_id')
            ->all();

        $deleted = Structure::where('id', $id)->delete() > 0;
        if ($deleted) {
            TickSummaryMaintainer::recomputeForDominions($impactedDominionIds);
        }

        return $deleted;
    }

    public function findLevel(int $structureId, int $level): ?StructureLevel
    {
        return StructureLevel::where('structure_id', $structureId)
            ->where('level', $level)
            ->first();
    }

    public function allLevels(int $structureId): Collection
    {
        return StructureLevel::where('structure_id', $structureId)
            ->orderBy('level', 'asc')
            ->get();
    }

    public function updateLevel(int $structureId, int $level, array $data): bool
    {
        $updated = StructureLevel::where('structure_id', $structureId)
            ->where('level', $level)
            ->update($data) > 0;

        if ($updated) {
            TickSummaryMaintainer::recomputeForStructureImpact($structureId, $level);
        }

        return $updated;
    }

    public function addLevel(array $data): bool
    {
        $added = StructureLevel::create($data)->exists;
        if ($added && isset($data['structure_id'], $data['level'])) {
            TickSummaryMaintainer::recomputeForStructureImpact((int)$data['structure_id'], (int)$data['level']);
        }

        return $added;
    }

    public function getColumns(): array
    {
        return Capsule::schema()->getColumnListing('structures');
    }

    public function getLevelColumns(): array
    {
        return Capsule::schema()->getColumnListing('structure_levels');
    }
}
