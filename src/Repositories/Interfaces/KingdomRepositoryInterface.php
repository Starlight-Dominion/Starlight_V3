<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\Kingdom;
use Illuminate\Support\Collection;

interface KingdomRepositoryInterface
{
    public function findById(int $id): ?Kingdom;
    public function findByUserId(int $userId): ?Kingdom;
    public function lockForUpdate(int $id): ?Kingdom;
    public function update(int $id, array $data): bool;
    public function incrementStats(int $id, array $stats): bool;
    public function decrementStats(int $id, array $stats): bool;
    public function getBattlefieldList(): Collection;
}