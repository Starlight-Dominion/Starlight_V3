<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\Dominion;
use sdo\Models\TickLog;
use sdo\Repositories\Interfaces\TickRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as Capsule;

class EloquentTickRepository implements TickRepositoryInterface
{
    public function getAllDominionIds(): array
    {
        return Dominion::pluck('id')->toArray();
    }

    public function getTickData(array $dominionIds): Collection
    {
        return Dominion::whereIn('id', $dominionIds)
            ->select(['dominions.*'])
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(sl.buff_economy)')
                    ->from('dominion_structures as ds')
                    ->join('structure_levels as sl', function ($join) {
                        $join->on('ds.structure_id', '=', 'sl.structure_id')
                             ->on('ds.level', '=', 'sl.level');
                    })
                    ->whereColumn('ds.dominion_id', 'dominions.id');
            }, 'total_economy_buff')
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(sl.buff_citizens_per_tick)')
                    ->from('dominion_structures as ds')
                    ->join('structure_levels as sl', function ($join) {
                        $join->on('ds.structure_id', '=', 'sl.structure_id')
                             ->on('ds.level', '=', 'sl.level');
                    })
                    ->whereColumn('ds.dominion_id', 'dominions.id');
            }, 'total_citizen_buff')
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(dm.total_quantity * u.production_credits)')
                    ->from('dominion_manpower as dm')
                    ->join('units as u', 'dm.unit_id', '=', 'u.id')
                    ->whereColumn('dm.dominion_id', 'dominions.id');
            }, 'total_unit_production')
            ->get();
    }

    public function applyTickResults(int $dominionId, int $credits, int $citizens, int $turns, string $tickTime): bool
    {
        return Capsule::table('dominions')
            ->where('id', $dominionId)
            ->update([
                'credits'   => Capsule::raw('credits + ' . (int)$credits),
                'citizens'  => Capsule::raw('citizens + ' . (int)$citizens),
                'turns'     => Capsule::raw('turns + ' . (int)$turns),
                'last_tick' => $tickTime
            ]) > 0;
    }

    public function applyTickResultsSetBased(array $dominionIds, int $baseCredits, int $baseCitizens, int $baseTurns, string $tickTime): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $dominionIds), static fn (int $id): bool => $id > 0)));
        if (empty($ids)) {
            return [
                'credits' => 0,
                'citizens' => 0,
                'turns' => 0,
            ];
        }

        $economyBuffSql = "COALESCE((
            SELECT SUM(sl.buff_economy)
            FROM dominion_structures ds
            JOIN structure_levels sl
              ON ds.structure_id = sl.structure_id
             AND ds.level = sl.level
            WHERE ds.dominion_id = dominions.id
        ), 0)";

        $citizenBuffSql = "COALESCE((
            SELECT SUM(sl.buff_citizens_per_tick)
            FROM dominion_structures ds
            JOIN structure_levels sl
              ON ds.structure_id = sl.structure_id
             AND ds.level = sl.level
            WHERE ds.dominion_id = dominions.id
        ), 0)";

        $unitProductionSql = "COALESCE((
            SELECT SUM(dm.total_quantity * u.production_credits)
            FROM dominion_manpower dm
            JOIN units u ON dm.unit_id = u.id
            WHERE dm.dominion_id = dominions.id
        ), 0)";

        $creditsExpr = 'CASE
            WHEN (((' . (int)$baseCredits . ' + ' . $unitProductionSql . ') * (1 + (' . $economyBuffSql . ' / 100.0)))) < 0 THEN 0
            ELSE CAST((((' . (int)$baseCredits . ' + ' . $unitProductionSql . ') * (1 + (' . $economyBuffSql . ' / 100.0)))) AS INTEGER)
        END';

        $citizensExpr = 'CASE
            WHEN ((' . (int)$baseCitizens . ' + ' . $citizenBuffSql . ')) < 0 THEN 0
            ELSE (' . (int)$baseCitizens . ' + ' . $citizenBuffSql . ')
        END';

        $metrics = Capsule::table('dominions')
            ->whereIn('id', $ids)
            ->selectRaw('COALESCE(SUM(' . $creditsExpr . '), 0) as total_credits')
            ->selectRaw('COALESCE(SUM(' . $citizensExpr . '), 0) as total_citizens')
            ->selectRaw('COUNT(*) * ' . (int)$baseTurns . ' as total_turns')
            ->first();

        Capsule::table('dominions')
            ->whereIn('id', $ids)
            ->update([
                'credits' => Capsule::raw('credits + (' . $creditsExpr . ')'),
                'citizens' => Capsule::raw('citizens + (' . $citizensExpr . ')'),
                'turns' => Capsule::raw('turns + ' . (int)$baseTurns),
                'last_tick' => $tickTime,
            ]);

        return [
            'credits' => (int)($metrics->total_credits ?? 0),
            'citizens' => (int)($metrics->total_citizens ?? 0),
            'turns' => (int)($metrics->total_turns ?? 0),
        ];
    }

    public function createTickLog(array $data): TickLog
    {
        return TickLog::create($data);
    }

    public function updateTickLogByTickId(string $tickId, array $data): bool
    {
        $log = $this->findTickLogByTickId($tickId);
        return $log ? $log->update($data) : false;
    }

    public function findTickLogByTickId(string $tickId): ?TickLog
    {
        // Eloquent supports metadata->tick_id for JSON columns in modern Laravel/Eloquent
        return TickLog::where('metadata->tick_id', $tickId)->first();
    }
}
