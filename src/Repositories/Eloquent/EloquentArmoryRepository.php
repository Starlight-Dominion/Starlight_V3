<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\ArmoryItem;
use sdo\Models\ArmoryCategory;
use sdo\Models\ArmoryUnitType;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentArmoryRepository implements ArmoryRepositoryInterface
{
    public function findById(int $id): ?ArmoryItem
    {
        return ArmoryItem::find($id);
    }

    public function findBySlug(string $slug): ?ArmoryItem
    {
        return ArmoryItem::where('slug', $slug)->first();
    }

    public function all(): Collection
    {
        return ArmoryItem::all();
    }

    public function create(array $data): ArmoryItem
    {
        return ArmoryItem::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $item = $this->findById($id);
        return $item ? $item->update($data) : false;
    }

    public function delete(int $id): bool
    {
        return ArmoryItem::where('id', $id)->delete() > 0;
    }

    public function getColumns(): array
    {
        return Capsule::schema()->getColumnListing('armory_items');
    }

    public function allCategories(): Collection
    {
        return ArmoryCategory::orderBy('id', 'asc')->get();
    }

    public function allUnitTypes(): Collection
    {
        return ArmoryUnitType::orderBy('id', 'asc')->get();
    }
}
