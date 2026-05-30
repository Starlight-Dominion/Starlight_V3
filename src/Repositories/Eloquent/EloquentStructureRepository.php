<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\Structure;
use sdo\Models\StructureLevel;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
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
        return Structure::where('id', $id)->delete() > 0;
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
        return StructureLevel::where('structure_id', $structureId)
            ->where('level', $level)
            ->update($data) > 0;
    }

    public function addLevel(array $data): bool
    {
        return StructureLevel::create($data)->exists;
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
