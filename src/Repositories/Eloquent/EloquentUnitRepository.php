<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Models\Unit;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentUnitRepository implements UnitRepositoryInterface
{
    public function findById(int $id): ?Unit
    {
        return Unit::find($id);
    }

    public function findBySlug(string $slug): ?Unit
    {
        return Unit::where('slug', $slug)->first();
    }

    public function all(): Collection
    {
        return Unit::all();
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $unit = $this->findById($id);
        return $unit ? $unit->update($data) : false;
    }

    public function delete(int $id): bool
    {
        return Unit::where('id', $id)->delete() > 0;
    }

    public function getColumns(): array
    {
        return Capsule::schema()->getColumnListing('units');
    }
}
