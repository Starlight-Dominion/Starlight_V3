<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\DominionArmoryItem;
use sdo\Repositories\Interfaces\DominionArmoryRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDominionArmoryRepository implements DominionArmoryRepositoryInterface
{
    public function getInventory(int $dominionId): Collection
    {
        return DominionArmoryItem::where('kingdom_id', $dominionId)->get();
    }

    public function findItem(int $dominionId, int $itemId): ?DominionArmoryItem
    {
        return DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->first();
    }

    public function updateQuantity(int $dominionId, int $itemId, int $change): bool
    {
        $item = $this->findItem($dominionId, $itemId);
        $currentQty = $item ? $item->quantity : 0;
        $newQty = $currentQty + $change;

        if ($newQty <= 0) {
            return $this->removeItem($dominionId, $itemId);
        }

        if (!$item) {
            return DominionArmoryItem::insert([
                'kingdom_id' => $dominionId,
                'item_id' => $itemId,
                'quantity' => $newQty,
                'is_equipped' => false
            ]);
        }

        return DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->update(['quantity' => $newQty]) > 0;
    }

    public function setQuantity(int $dominionId, int $itemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($dominionId, $itemId);
        }

        $exists = DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->exists();

        if (!$exists) {
            return DominionArmoryItem::insert([
                'kingdom_id' => $dominionId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'is_equipped' => false
            ]);
        }

        return DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->update(['quantity' => $quantity]) > 0;
    }

    public function toggleEquip(int $dominionId, int $itemId, bool $isEquipped): bool
    {
        return (bool)DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->update(['is_equipped' => $isEquipped]);
    }

    public function removeItem(int $dominionId, int $itemId): bool
    {
        return (bool)DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->delete();
    }

    public function addItem(int $dominionId, int $itemId, int $quantity, bool $isEquipped = false): bool
    {
        return DominionArmoryItem::insert([
            'kingdom_id' => $dominionId,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'is_equipped' => $isEquipped
        ]);
    }

    public function getEquippedItemsByType(int $dominionId, string $unitTypeSlug): Collection
    {
        return DominionArmoryItem::with('item')
            ->where('kingdom_id', $dominionId)
            ->where('is_equipped', true)
            ->whereHas('item', fn($q) => $q->where('unit_type', $unitTypeSlug))
            ->get();
    }

    public function updateOrCreate(int $dominionId, int $itemId, array $data): bool
    {
        $exists = DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->exists();

        if (!$exists) {
            return DominionArmoryItem::insert(array_merge($data, [
                'kingdom_id' => $dominionId,
                'item_id' => $itemId
            ]));
        }

        return DominionArmoryItem::where('kingdom_id', $dominionId)
            ->where('item_id', $itemId)
            ->update($data) > 0;
    }
}
