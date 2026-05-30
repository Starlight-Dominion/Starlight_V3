<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\Race;
use Illuminate\Support\Collection;

interface RaceRepositoryInterface
{
    public function findByName(string $name): ?Race;
    public function findById(int $id): ?Race;
    public function all(): Collection;
    public function update(int $id, array $data): bool;
    public function getColumns(): array;
}
