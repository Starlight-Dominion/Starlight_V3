<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\DominionManpower;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Support\TickSummaryMaintainer;
use Illuminate\Support\Collection;

class EloquentManpowerRepository implements ManpowerRepositoryInterface
{
    public function getManpowerByDominion(int $dominionId): Collection
    {
        return DominionManpower::with('unit')
            ->where('dominion_id', $dominionId)
            ->get();
    }

    public function getManpowerBySlugMap(int $dominionId): Collection
    {
        return DominionManpower::join('units', 'dominion_manpower.unit_id', '=', 'units.id')
            ->where('dominion_manpower.dominion_id', $dominionId)
            ->pluck('dominion_manpower.total_quantity', 'units.slug');
    }

    public function updateQuantity(int $dominionId, int $unitId, int $change): bool
    {
        $manpower = DominionManpower::where('dominion_id', $dominionId)
            ->where('unit_id', $unitId)
            ->first();

        if (!$manpower) {
            if ($change > 0) {
                $manpower = new DominionManpower();
                $manpower->dominion_id = $dominionId;
                $manpower->unit_id = $unitId;
                $manpower->total_quantity = $change;
                $saved = $manpower->save();
                if ($saved) {
                    TickSummaryMaintainer::recomputeForDominion($dominionId);
                }
                return $saved;
            }
            return false;
        }

        $newQty = $manpower->total_quantity + $change;
        if ($newQty < 0) return false;

        $updated = $manpower->update(['total_quantity' => $newQty]);
        if ($updated) {
            TickSummaryMaintainer::recomputeForDominion($dominionId);
        }

        return $updated;
    }

    public function sumTotalQuantity(): float
    {
        return (float)DominionManpower::sum('total_quantity');
    }

    public function setQuantityWithStable(int $dominionId, int $unitId, int $total, int $stabled): bool
    {
        $model = \sdo\Models\DominionManpower::updateOrCreate(
            ['dominion_id' => $dominionId, 'unit_id' => $unitId],
            ['total_quantity' => $total, 'stabled_quantity' => $stabled]
        );

        if ($model->wasRecentlyCreated || $model->wasChanged()) {
            TickSummaryMaintainer::recomputeForDominion($dominionId);
        }

        return $model->exists;
    }
}
