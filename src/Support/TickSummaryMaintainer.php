<?php

declare(strict_types=1);

namespace sdo\Support;

use Illuminate\Database\Capsule\Manager as Capsule;

final class TickSummaryMaintainer
{
    public static function recomputeForDominion(int $dominionId): void
    {
        if ($dominionId <= 0) {
            return;
        }

        if (!self::hasSummaryTable()) {
            return;
        }

        $structure = Capsule::table('dominion_structures as ds')
            ->join('structure_levels as sl', function ($join): void {
                $join->on('ds.structure_id', '=', 'sl.structure_id')
                    ->on('ds.level', '=', 'sl.level');
            })
            ->where('ds.dominion_id', $dominionId)
            ->selectRaw('COALESCE(SUM(sl.buff_economy), 0) as total_economy_buff')
            ->selectRaw('COALESCE(SUM(sl.buff_citizens_per_tick), 0) as total_citizen_buff')
            ->first();

        $manpower = Capsule::table('dominion_manpower as dm')
            ->join('units as u', 'dm.unit_id', '=', 'u.id')
            ->where('dm.dominion_id', $dominionId)
            ->selectRaw('COALESCE(SUM(dm.total_quantity * u.production_credits), 0) as total_unit_production')
            ->first();

        Capsule::table('dominion_tick_summaries')->updateOrInsert(
            ['dominion_id' => $dominionId],
            [
                'total_economy_buff' => (int)($structure->total_economy_buff ?? 0),
                'total_citizen_buff' => (int)($structure->total_citizen_buff ?? 0),
                'total_unit_production' => (int)($manpower->total_unit_production ?? 0),
            ]
        );
    }

    public static function recomputeForDominions(array $dominionIds): void
    {
        if (!self::hasSummaryTable()) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $dominionIds), static fn (int $id): bool => $id > 0)));
        foreach ($ids as $id) {
            self::recomputeForDominion($id);
        }
    }

    public static function recomputeForStructureImpact(int $structureId, ?int $level = null): void
    {
        if ($structureId <= 0) {
            return;
        }

        if (!self::hasSummaryTable()) {
            return;
        }

        $query = Capsule::table('dominion_structures')
            ->where('structure_id', $structureId);

        if ($level !== null) {
            $query->where('level', $level);
        }

        $dominionIds = $query->pluck('dominion_id')->all();
        self::recomputeForDominions($dominionIds);
    }

    public static function recomputeForUnitImpact(int $unitId): void
    {
        if ($unitId <= 0) {
            return;
        }

        if (!self::hasSummaryTable()) {
            return;
        }

        $dominionIds = Capsule::table('dominion_manpower')
            ->where('unit_id', $unitId)
            ->pluck('dominion_id')
            ->all();

        self::recomputeForDominions($dominionIds);
    }

    private static function hasSummaryTable(): bool
    {
        return Capsule::schema()->hasTable('dominion_tick_summaries');
    }
}
