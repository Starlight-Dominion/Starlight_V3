<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Models\Unit;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Support\TickSummaryMaintainer;
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
        if (!$unit) {
            return false;
        }

        $productionChanged = array_key_exists('production_credits', $data)
            && (int)$unit->production_credits !== (int)$data['production_credits'];

        $updated = $unit->update($data);
        if ($updated && $productionChanged) {
            TickSummaryMaintainer::recomputeForUnitImpact($id);
        }

        return $updated;
    }

    public function delete(int $id): bool
    {
        $impactedDominionIds = [];
        if (Capsule::schema()->hasTable('dominion_manpower')) {
            $impactedDominionIds = Capsule::table('dominion_manpower')
                ->where('unit_id', $id)
                ->pluck('dominion_id')
                ->all();
        }

        $deleted = Unit::where('id', $id)->delete() > 0;
        if ($deleted) {
            TickSummaryMaintainer::recomputeForDominions($impactedDominionIds);
        }

        return $deleted;
    }

    public function getColumns(): array
    {
        return Capsule::schema()->getColumnListing('units');
    }
}
