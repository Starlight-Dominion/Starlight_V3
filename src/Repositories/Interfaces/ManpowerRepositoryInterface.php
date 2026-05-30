<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface ManpowerRepositoryInterface
{
    public function getManpowerByDominion(int $dominionId): Collection;
    public function getManpowerBySlugMap(int $dominionId): Collection;
    public function updateQuantity(int $dominionId, int $unitId, int $change): bool;
    public function sumTotalQuantity(): float;
    public function setQuantityWithStable(int $dominionId, int $unitId, int $total, int $stabled): bool;
}
