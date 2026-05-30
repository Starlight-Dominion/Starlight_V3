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
        if (!$item) {
            if ($change > 0) {
                return $this->addItem($dominionId, $itemId, $change);
            }
            return false;
        }

        $newQty = $item->quantity + $change;
        if ($newQty <= 0) {
            return $this->removeItem($dominionId, $itemId);
        }

        return $item->update(['quantity' => $newQty]);
    }

    public function setQuantity(int $dominionId, int $itemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($dominionId, $itemId);
        }

        $item = $this->findItem($dominionId, $itemId);
        if ($item) {
            return $item->update(['quantity' => $quantity]);
        }

        return $this->addItem($dominionId, $itemId, $quantity);
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
        $item = new DominionArmoryItem();
        $item->kingdom_id = $dominionId;
        $item->item_id = $itemId;
        $item->quantity = $quantity;
        $item->is_equipped = $isEquipped;
        return $item->save();
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
        return (bool)DominionArmoryItem::updateOrCreate(
            ['kingdom_id' => $dominionId, 'item_id' => $itemId],
            $data
        );
    }
}
