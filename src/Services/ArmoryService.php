<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class ArmoryService
{
    public function __construct(private LogService $logService) {}

    public function getArmoryData(int $domId): array
    {
        $dom = Dominion::findOrFail($domId);
        
        $unitTypes = Capsule::table('armory_unit_types')->get();
        $categories = Capsule::table('armory_categories')->get();
        $inventory = Capsule::table('kingdom_armory_items')
            ->where('kingdom_id', $domId)
            ->get()
            ->pluck('quantity', 'item_id');

        $loadouts = [];
        foreach ($unitTypes as $uType) {
            // Map units from manpower table
            $count = Capsule::table('dominion_manpower')
                ->join('units', 'dominion_manpower.unit_id', '=', 'units.id')
                ->where('dominion_manpower.dominion_id', $domId)
                ->where('units.slug', $uType->slug)
                ->value('total_quantity') ?? 0;

            $loadouts[$uType->slug] = [
                'title' => $uType->title,
                'unit_count' => (int)$count,
                'categories' => []
            ];

            $typeCats = $categories->where('unit_type_id', $uType->id);
            foreach ($typeCats as $cat) {
                $items = Capsule::table('armory_items')
                    ->where('category_id', $cat->id)
                    ->get()
                    ->map(function($item) use ($inventory, $dom) {
                        $item->owned_quantity = $inventory[$item->id] ?? 0;
                        $item->unlocked = ($dom->armory_level >= $item->armory_level_req);
                        return $item;
                    })->keyBy('slug');

                $loadouts[$uType->slug]['categories'][$cat->slug] = [
                    'title' => $cat->name,
                    'slots' => $cat->slots,
                    'items' => $items
                ];
            }
        }

        $nextLevel = $dom->armory_level + 1;
        $upgrade = Capsule::table('structure_levels')
            ->where('structure_id', 3) // Assuming Armory ID is 3
            ->where('level', $nextLevel)
            ->first();

        return [
            'loadouts' => $loadouts,
            'armory_level' => $dom->armory_level,
            'upgrade_cost' => $upgrade ? $upgrade->cost : null
        ];
    }

    public function buyItem(int $domId, int $itemId, int $qty): array
    {
        return Capsule::transaction(function() use ($domId, $itemId, $qty) {
            $dom = Dominion::lockForUpdate()->find($domId);
            $item = Capsule::table('armory_items')->where('id', $itemId)->first();

            if (!$item || $dom->armory_level < $item->armory_level_req) {
                throw new Exception("Tech requirement not met.");
            }

            $totalCost = $item->cost * $qty;
            if ($dom->credits < $totalCost) throw new Exception("Insufficient credits.");

            $dom->credits -= $totalCost;
            $dom->save();

            $existing = Capsule::table('kingdom_armory_items')
                ->where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->first();

            if ($existing) {
                Capsule::table('kingdom_armory_items')
                    ->where('kingdom_id', $domId)
                    ->where('item_id', $itemId)
                    ->update(['quantity' => $existing->quantity + $qty]);
            } else {
                Capsule::table('kingdom_armory_items')->insert([
                    'kingdom_id' => $domId,
                    'item_id' => $itemId,
                    'quantity' => $qty
                ]);
            }

            $this->logService->log(
                $domId,
                'armory_buy',
                "Commander purchased $qty x {$item->name} from the armory.",
                $totalCost,
                ['item' => $item->slug, 'quantity' => $qty]
            );

            return ['success' => true, 'message' => "Acquired $qty x {$item->name}."];
        });
    }

    public function sellItem(int $domId, int $itemId, int $qty): array
    {
        return Capsule::transaction(function() use ($domId, $itemId, $qty) {
            $dom = Dominion::lockForUpdate()->find($domId);
            $inv = Capsule::table('kingdom_armory_items')
                ->where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->first();

            if (!$inv || $inv->quantity < $qty) throw new Exception("Insufficient stock.");

            $item = Capsule::table('armory_items')->where('id', $itemId)->first();
            $refund = (int)($item->cost * 0.5 * $qty);

            $dom->credits += $refund;
            $dom->save();

            Capsule::table('kingdom_armory_items')
                ->where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->decrement('quantity', $qty);

            $this->logService->log(
                $domId,
                'armory_sell',
                "Commander decommissioned $qty x {$item->name} for salvage.",
                $refund,
                ['item' => $item->slug, 'quantity' => $qty]
            );

            return ['success' => true, 'message' => "Decommissioned for $refund Credits."];
        });
    }

    public function upgradeArmory(int $domId): array
    {
        return Capsule::transaction(function() use ($domId) {
            $dom = Dominion::lockForUpdate()->find($domId);
            $next = $dom->armory_level + 1;
            
            $levelData = Capsule::table('structure_levels')
                ->where('structure_id', 3)
                ->where('level', $next)
                ->first();

            if (!$levelData) throw new Exception("Maximum tech level reached.");
            if ($dom->credits < $levelData->cost) throw new Exception("Insufficient credits.");

            $dom->credits -= $levelData->cost;
            $dom->armory_level = $next;
            $dom->save();

            $this->logService->log(
                $domId,
                'armory_upgrade',
                "Commander upgraded the Armory to Rank $next.",
                (int)$levelData->cost,
                ['new_level' => $next]
            );

            return ['success' => true, 'message' => "Armory upgraded to Rank $next."];
        });
    }
}
