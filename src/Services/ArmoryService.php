<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\ArmoryItem;
use sdo\Models\ArmoryUnitType;
use sdo\Models\ArmoryCategory;
use sdo\Models\DominionArmoryItem;
use sdo\Models\DominionManpower;
use sdo\Models\StructureLevel;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class ArmoryService
{
    public function __construct(private LogService $logService) {}

    public function getArmoryData(int $domId): array
    {
        $dom = Dominion::findOrFail($domId);

        $unitTypes = ArmoryUnitType::all();
        $categoriesByUnitType = ArmoryCategory::all()->groupBy('unit_type_id');
        $itemsByCategory = ArmoryItem::all()->groupBy('category_id');
        $inventory = DominionArmoryItem::where('kingdom_id', $domId)
            ->get()
            ->keyBy('item_id');

        $manpowerBySlug = DominionManpower::join('units', 'dominion_manpower.unit_id', '=', 'units.id')
            ->where('dominion_manpower.dominion_id', $domId)
            ->pluck('dominion_manpower.total_quantity', 'units.slug');

        $loadouts = [];
        foreach ($unitTypes as $uType) {
            $count = $manpowerBySlug[$uType->slug] ?? 0;

            $loadouts[$uType->slug] = [
                'title' => $uType->title,
                'unit_count' => (int)$count,
                'categories' => []
            ];

            $typeCats = $categoriesByUnitType[$uType->id] ?? collect();
            foreach ($typeCats as $cat) {
                $items = ($itemsByCategory[$cat->id] ?? collect())
                    ->map(function($item) use ($inventory, $dom) {
                        $invItem = $inventory[$item->id] ?? null;
                        $item->owned_quantity = $invItem ? $invItem->quantity : 0;
                        $item->is_equipped = $invItem ? $invItem->is_equipped : false;
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
        $upgrade = StructureLevel::where('structure_id', 3) 
            ->where('level', $nextLevel)
            ->first();

        return [
            'loadouts' => $loadouts,
            'armory_level' => $dom->armory_level,
            'upgrade_cost' => $upgrade ? $upgrade->cost : null
        ];
    }

    public function toggleEquip(int $domId, int $itemId): array
    {
        return Capsule::transaction(function() use ($domId, $itemId) {
            $inv = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->first();

            if (!$inv || $inv->quantity <= 0) {
                throw new Exception("Item not found in inventory.");
            }

            $newState = !$inv->is_equipped;
            
            DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->update(['is_equipped' => $newState]);

            $item = ArmoryItem::find($itemId);
            $status = $newState ? "equipped" : "unequipped";

            $this->logService->log(
                $domId,
                'armory_equip',
                "Commander $status {$item->name}.",
                0,
                ['item' => $item->slug, 'is_equipped' => $newState]
            );

            return ['success' => true, 'is_equipped' => $newState, 'message' => "Item $status."];
        });
    }

    public function upgradeItem(int $domId, int $itemId, int $qty): array
    {
        return Capsule::transaction(function() use ($domId, $itemId, $qty) {
            $dom = Dominion::lockForUpdate()->find($domId);
            $targetItem = ArmoryItem::find($itemId);

            if (!$targetItem || !$targetItem->requirement_slug) {
                throw new Exception("Invalid upgrade target.");
            }

            if ($dom->armory_level < $targetItem->armory_level_req) {
                throw new Exception("Tech requirement not met.");
            }

            $reqItem = ArmoryItem::where('slug', $targetItem->requirement_slug)->first();
            if (!$reqItem) throw new Exception("Requirement data missing.");

            $invReq = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $reqItem->id)
                ->first();

            if (!$invReq || $invReq->quantity < $qty) {
                throw new Exception("Insufficient {$reqItem->name} for upgrade.");
            }

            $totalCost = $targetItem->cost * $qty;
            if ($dom->credits < $totalCost) throw new Exception("Insufficient credits.");

            // Process Upgrade
            $dom->credits -= $totalCost;
            $dom->save();

            // Consume Req via Query Builder
            DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $reqItem->id)
                ->decrement('quantity', $qty);
            
            $remaining = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $reqItem->id)
                ->value('quantity');

            if ($remaining <= 0) {
                DominionArmoryItem::where('kingdom_id', $domId)
                    ->where('item_id', $reqItem->id)
                    ->delete();
            }

            // Add Target via Query Builder
            $exists = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->exists();

            if ($exists) {
                DominionArmoryItem::where('kingdom_id', $domId)
                    ->where('item_id', $itemId)
                    ->increment('quantity', $qty);
            } else {
                DominionArmoryItem::insert([
                    'kingdom_id' => $domId,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'is_equipped' => false
                ]);
            }

            $this->logService->log(
                $domId,
                'armory_upgrade_item',
                "Commander upgraded $qty x {$reqItem->name} into {$targetItem->name}.",
                $totalCost,
                ['from' => $reqItem->slug, 'to' => $targetItem->slug, 'quantity' => $qty]
            );

            return ['success' => true, 'message' => "Successfully upgraded $qty items."];
        });
    }

    public function buyItem(int $domId, int $itemId, int $qty): array
    {
        return Capsule::transaction(function() use ($domId, $itemId, $qty) {
            $dom = Dominion::lockForUpdate()->find($domId);
            $item = ArmoryItem::find($itemId);

            if (!$item || $dom->armory_level < $item->armory_level_req) {
                throw new Exception("Tech requirement not met.");
            }

            $totalCost = $item->cost * $qty;
            if ($dom->credits < $totalCost) throw new Exception("Insufficient credits.");

            $dom->credits -= $totalCost;
            $dom->save();

            $exists = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->exists();

            if ($exists) {
                DominionArmoryItem::where('kingdom_id', $domId)
                    ->where('item_id', $itemId)
                    ->increment('quantity', $qty);
            } else {
                DominionArmoryItem::insert([
                    'kingdom_id' => $domId,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'is_equipped' => false
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
            $inv = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->first();

            if (!$inv || $inv->quantity < $qty) throw new Exception("Insufficient stock.");

            $item = ArmoryItem::find($itemId);
            $refund = (int)($item->cost * 0.5 * $qty);

            $dom->credits += $refund;
            $dom->save();

            DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->decrement('quantity', $qty);
            
            $remaining = DominionArmoryItem::where('kingdom_id', $domId)
                ->where('item_id', $itemId)
                ->value('quantity');

            if ($remaining <= 0) {
                DominionArmoryItem::where('kingdom_id', $domId)
                    ->where('item_id', $itemId)
                    ->delete();
            }

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
            
            $levelData = StructureLevel::where('structure_id', 3)
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
