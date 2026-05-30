<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\DominionManpower;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
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
                return $manpower->save();
            }
            return false;
        }

        $newQty = $manpower->total_quantity + $change;
        if ($newQty < 0) return false;

        return $manpower->update(['total_quantity' => $newQty]);
    }

    public function sumTotalQuantity(): float
    {
        return (float)DominionManpower::sum('total_quantity');
    }

    public function setQuantityWithStable(int $dominionId, int $unitId, int $total, int $stabled): bool
    {
        return (bool)\sdo\Models\DominionManpower::updateOrCreate(
            ['dominion_id' => $dominionId, 'unit_id' => $unitId],
            ['total_quantity' => $total, 'stabled_quantity' => $stabled]
        );
    }
}
