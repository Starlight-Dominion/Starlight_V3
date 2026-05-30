<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\ArmoryItem;
use Illuminate\Support\Collection;

interface ArmoryRepositoryInterface
{
    public function findById(int $id): ?ArmoryItem;
    public function findBySlug(string $slug): ?ArmoryItem;
    public function all(): Collection;
    public function create(array $data): ArmoryItem;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function getColumns(): array;
    
    public function allCategories(): Collection;
    public function allUnitTypes(): Collection;
}
