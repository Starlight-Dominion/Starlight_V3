<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\Unit;
use Illuminate\Support\Collection;

interface UnitRepositoryInterface
{
    public function findById(int $id): ?Unit;
    public function findBySlug(string $slug): ?Unit;
    public function all(): Collection;
    public function create(array $data): Unit;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
