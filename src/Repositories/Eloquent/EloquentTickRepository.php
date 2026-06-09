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
        if ($this->hasTickSummaryTable()) {
            return Dominion::whereIn('id', $dominionIds)
                ->select(['dominions.*'])
                ->leftJoin('dominion_tick_summaries as dts', 'dts.dominion_id', '=', 'dominions.id')
                ->selectRaw('COALESCE(dts.total_economy_buff, 0) as total_economy_buff')
                ->selectRaw('COALESCE(dts.total_citizen_buff, 0) as total_citizen_buff')
                ->selectRaw('COALESCE(dts.total_unit_production, 0) as total_unit_production')
                ->get();
        }

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

        $driver = Capsule::connection()->getDriverName();
        $hasSummaryTable = $this->hasTickSummaryTable();

        if ($driver === 'mysql' && $hasSummaryTable) {
            return $this->applyTickResultsSetBasedMysql($ids, $baseCredits, $baseCitizens, $baseTurns, $tickTime);
        }

        if ($hasSummaryTable) {
            $economyBuffSql = 'COALESCE((SELECT dts.total_economy_buff FROM dominion_tick_summaries dts WHERE dts.dominion_id = dominions.id), 0)';
            $citizenBuffSql = 'COALESCE((SELECT dts.total_citizen_buff FROM dominion_tick_summaries dts WHERE dts.dominion_id = dominions.id), 0)';
            $unitProductionSql = 'COALESCE((SELECT dts.total_unit_production FROM dominion_tick_summaries dts WHERE dts.dominion_id = dominions.id), 0)';
        } else {
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
        }

        $creditsExpr = 'CASE
            WHEN (((' . (int)$baseCredits . ' + ' . $unitProductionSql . ') * (1 + (' . $economyBuffSql . ' / 100e0)))) < 0 THEN 0
            ELSE FLOOR((((' . (int)$baseCredits . ' + ' . $unitProductionSql . ') * (1 + (' . $economyBuffSql . ' / 100e0)))))
        END';

        $citizensExpr = 'CASE
            WHEN ((' . (int)$baseCitizens . ' + ' . $citizenBuffSql . ')) < 0 THEN 0
            ELSE (' . (int)$baseCitizens . ' + ' . $citizenBuffSql . ')
        END';

        $metrics = Capsule::table('dominions')
            ->whereIntegerInRaw('id', $ids)
            ->selectRaw('COALESCE(SUM(' . $creditsExpr . '), 0) as total_credits')
            ->selectRaw('COALESCE(SUM(' . $citizensExpr . '), 0) as total_citizens')
            ->selectRaw('COUNT(*) * ' . (int)$baseTurns . ' as total_turns')
            ->first();

        Capsule::table('dominions')
            ->whereIntegerInRaw('id', $ids)
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

    private function applyTickResultsSetBasedMysql(array $ids, int $baseCredits, int $baseCitizens, int $baseTurns, string $tickTime): array
    {
        $idsTable = 'tick_ids_tmp';
        $tempTable = 'tick_deltas_tmp';

        Capsule::statement('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $idsTable . ' (
            dominion_id INT UNSIGNED NOT NULL PRIMARY KEY
        ) ENGINE=MEMORY');

        Capsule::statement('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $tempTable . ' (
            dominion_id INT UNSIGNED NOT NULL PRIMARY KEY,
            delta_credits BIGINT NOT NULL,
            delta_citizens INT NOT NULL
        ) ENGINE=MEMORY');

        Capsule::statement('DELETE FROM ' . $idsTable);
        Capsule::statement('DELETE FROM ' . $tempTable);

        try {
            foreach (array_chunk($ids, 5000) as $idChunk) {
                $rows = [];
                foreach ($idChunk as $id) {
                    $rows[] = ['dominion_id' => (int)$id];
                }
                Capsule::table($idsTable)->insert($rows);
            }

            $deltaCreditsExpr = 'CASE
                WHEN (((' . (int)$baseCredits . ' + COALESCE(dts.total_unit_production, 0)) * (1 + (COALESCE(dts.total_economy_buff, 0) / 100e0)))) < 0 THEN 0
                ELSE FLOOR(((' . (int)$baseCredits . ' + COALESCE(dts.total_unit_production, 0)) * (1 + (COALESCE(dts.total_economy_buff, 0) / 100e0))))
            END';

            $deltaCitizensExpr = 'CASE
                WHEN ((' . (int)$baseCitizens . ' + COALESCE(dts.total_citizen_buff, 0))) < 0 THEN 0
                ELSE (' . (int)$baseCitizens . ' + COALESCE(dts.total_citizen_buff, 0))
            END';

            Capsule::table($tempTable)->insertUsing(
                ['dominion_id', 'delta_credits', 'delta_citizens'],
                Capsule::table('dominions as d')
                    ->join($idsTable . ' as ti', 'ti.dominion_id', '=', 'd.id')
                    ->leftJoin('dominion_tick_summaries as dts', 'dts.dominion_id', '=', 'd.id')
                    ->selectRaw('d.id as dominion_id')
                    ->selectRaw($deltaCreditsExpr . ' as delta_credits')
                    ->selectRaw($deltaCitizensExpr . ' as delta_citizens')
            );

            $metrics = Capsule::table($tempTable)
                ->selectRaw('COALESCE(SUM(delta_credits), 0) as total_credits')
                ->selectRaw('COALESCE(SUM(delta_citizens), 0) as total_citizens')
                ->selectRaw('COUNT(*) * ' . (int)$baseTurns . ' as total_turns')
                ->first();

            Capsule::update(
                'UPDATE dominions d
                 JOIN ' . $tempTable . ' t ON t.dominion_id = d.id
                 SET d.credits = d.credits + t.delta_credits,
                     d.citizens = d.citizens + t.delta_citizens,
                     d.turns = d.turns + ?,
                     d.last_tick = ?',
                [(int)$baseTurns, $tickTime]
            );

            return [
                'credits' => (int)($metrics->total_credits ?? 0),
                'citizens' => (int)($metrics->total_citizens ?? 0),
                'turns' => (int)($metrics->total_turns ?? 0),
            ];
        } finally {
            Capsule::statement('DELETE FROM ' . $tempTable);
            Capsule::statement('DELETE FROM ' . $idsTable);
        }
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

    private function hasTickSummaryTable(): bool
    {
        return Capsule::schema()->hasTable('dominion_tick_summaries');
    }
}
