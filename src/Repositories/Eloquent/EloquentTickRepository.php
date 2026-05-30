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
