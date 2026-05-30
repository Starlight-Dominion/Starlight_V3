<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Models\Race;
use sdo\Repositories\Interfaces\RaceRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentRaceRepository implements RaceRepositoryInterface
{
    public function findByName(string $name): ?Race
    {
        return Race::where('name', $name)->first();
    }

    public function findById(int $id): ?Race
    {
        return Race::find($id);
    }

    public function all(): Collection
    {
        return Race::all();
    }

    public function update(int $id, array $data): bool
    {
        $race = $this->findById($id);
        return $race ? $race->update($data) : false;
    }

    public function getColumns(): array
    {
        return Capsule::schema()->getColumnListing('races');
    }
}
