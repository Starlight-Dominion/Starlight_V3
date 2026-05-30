<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\Dominion;
use Illuminate\Support\Collection;

interface DominionRepositoryInterface
{
    public function findById(int $id): ?Dominion;
    public function findByName(string $name): ?Dominion;
    public function findByUserId(int $userId): ?Dominion;
    public function lockForUpdate(int $id): ?Dominion;
    public function update(int $id, array $data): bool;
    public function incrementStats(int $id, array $stats): bool;
    public function decrementStats(int $id, array $stats): bool;
    public function getBattlefieldList(): Collection;
    public function count(): int;
    public function sum(string $column): float;
    public function search(string $query, int $limit = 20): Collection;
    public function getAll(int $limit = 50): Collection;
    public function findFullProfile(int $id): ?Dominion;
    public function getColumns(): array;
}
