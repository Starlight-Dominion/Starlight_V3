<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\DominionArmoryItem;
use Illuminate\Support\Collection;

interface DominionArmoryRepositoryInterface
{
    public function getInventory(int $dominionId): Collection;
    public function findItem(int $dominionId, int $itemId): ?DominionArmoryItem;
    public function updateQuantity(int $dominionId, int $itemId, int $change): bool;
    public function setQuantity(int $dominionId, int $itemId, int $quantity): bool;
    public function toggleEquip(int $dominionId, int $itemId, bool $isEquipped): bool;
    public function removeItem(int $dominionId, int $itemId): bool;
    public function addItem(int $dominionId, int $itemId, int $quantity, bool $isEquipped = false): bool;
    public function getEquippedItemsByType(int $dominionId, string $unitTypeSlug): Collection;
    public function updateOrCreate(int $dominionId, int $itemId, array $data): bool;
}
