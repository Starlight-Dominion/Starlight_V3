<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class TrainingService
{
    public function __construct(private LogService $logService) {}

    public function getUnitConfig(): array
    {
        return Unit::orderBy('cost_credits', 'asc')->get()->keyBy('slug')->toArray();
    }

    public function train(int $dominionId, string $unitSlug, int $quantity): array
    {
        $units = $this->getUnitConfig();
        if (!isset($units[$unitSlug]) || $quantity <= 0) return ['success' => false, 'message' => 'Invalid parameters.'];

        return Capsule::transaction(function() use ($dominionId, $unitSlug, $quantity, $units) {
            $dom = Dominion::lockForUpdate()->find($dominionId);
            $unit = (object)$units[$unitSlug]; // Cast to object for property access if keyBy returns array of arrays

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

            $exists = DominionManpower::where('dominion_id', $dominionId)
                ->where('unit_id', $unit->id)
                ->exists();

            if ($exists) {
                DominionManpower::where('dominion_id', $dominionId)
                    ->where('unit_id', $unit->id)
                    ->increment('total_quantity', $quantity);
            } else {
                DominionManpower::create([
                    'dominion_id' => $dominionId,
                    'unit_id' => $unit->id,
                    'total_quantity' => $quantity
                ]);
            }

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
