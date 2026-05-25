<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Services\GameService;
use sdo\Services\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;
use DateTime;

class TickService
{
    public const BATCH_SIZE = 100;

    public function __construct(
        private ConfigService $configService
    ) {}

    public function processGlobalTick(): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        echo "Starting Global Tick at {$now}...\n";

        $baseCitizens = (int)$this->configService->get('baseline_citizens_per_tick', 50);
        $baseCredits = (int)$this->configService->get('baseline_credits_per_tick', 100);
        $baseTurns = GameService::BASE_TURNS_PER_TICK;

        Dominion::query()
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
            ->chunkById(self::BATCH_SIZE, function ($dominions) use ($baseCitizens, $baseCredits, $baseTurns, $now) {
                Capsule::transaction(function () use ($dominions, $baseCitizens, $baseCredits, $baseTurns, $now) {
                    foreach ($dominions as $dom) {
                        $multiplier = 1 + ((float)($dom->total_economy_buff ?? 0) / 100);
                        $creditsGained = (int)floor($baseCredits * $multiplier);
                        
                        $citizenGained = $baseCitizens + (int)($dom->total_citizen_buff ?? 0);

                        Capsule::table('dominions')
                            ->where('id', $dom->id)
                            ->update([
                                'credits'   => Capsule::raw('credits + ' . (int)$creditsGained),
                                'citizens'  => Capsule::raw('citizens + ' . (int)$citizenGained),
                                'turns'     => Capsule::raw('turns + ' . (int)$baseTurns),
                                'last_tick' => $now
                            ]);
                    }
                });
                echo "Processed " . count($dominions) . " sectors...\n";
            });

        echo "Global Tick completed successfully.\n";
    }
}
