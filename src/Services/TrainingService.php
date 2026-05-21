<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class TrainingService
{
    public function __construct(private LogService $logService) {}

    public function getUnitConfig(): array
    {
        return Capsule::table('units')->orderBy('cost_credits', 'asc')->get()->keyBy('slug')->toArray();
    }

    public function train(int $dominionId, string $unitSlug, int $quantity): array
    {
        $units = $this->getUnitConfig();
        if (!isset($units[$unitSlug]) || $quantity <= 0) return ['success' => false, 'message' => 'Invalid parameters.'];

        return Capsule::transaction(function() use ($dominionId, $unitSlug, $quantity, $units) {
            $dom = Dominion::lockForUpdate()->find($dominionId);
            $unit = $units[$unitSlug];

            $cost = $unit->cost_credits * $quantity;
            $citizens = $unit->cost_citizens * $quantity;
            $turns = $unit->cost_turns * $quantity;

            if ($dom->credits < $cost || $dom->citizens < $citizens || $dom->turns < $turns) {
                throw new Exception("Insufficient resources for mobilization.");
            }

            $dom->credits -= $cost;
            $dom->citizens -= $citizens;
            $dom->turns -= $turns;
            $dom->save();

            Capsule::table('dominion_manpower')
                ->where('dominion_id', $dominionId)
                ->where('unit_id', $unit->id)
                ->increment('total_quantity', $quantity);

            $this->logService->log(
                $dominionId,
                'training_enlist',
                "Commander enlisted {$quantity} {$unit->name}.",
                $cost,
                ['unit' => $unitSlug, 'quantity' => $quantity]
            );

            return ['success' => true, 'message' => "Enlisted {$quantity} {$unit->name}."];
        });
    }
}
