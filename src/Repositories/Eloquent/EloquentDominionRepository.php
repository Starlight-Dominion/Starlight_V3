<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDominionRepository implements DominionRepositoryInterface
{
    public function findById(int $id): ?Dominion
    {
        return Dominion::with('user')->find($id);
    }

    public function findByName(string $name): ?Dominion
    {
        return Dominion::where('name', $name)->first();
    }

    public function findByUserId(int $userId): ?Dominion
    {
        return Dominion::with('user')->where('user_id', $userId)->first();
    }

    public function lockForUpdate(int $id): ?Dominion
    {
        return Dominion::lockForUpdate()->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $dominion = Dominion::find($id);
        if (!$dominion) return false;

        return $dominion->update($data);
    }

    public function incrementStats(int $id, array $stats): bool
    {
        $dominion = Dominion::find($id);
        if (!$dominion) return false;

        foreach ($stats as $column => $amount) {
            $dominion->increment($column, $amount);
        }
        return true;
    }

    public function decrementStats(int $id, array $stats): bool
    {
        $dominion = Dominion::find($id);
        if (!$dominion) return false;

        foreach ($stats as $column => $amount) {
            $dominion->decrement($column, $amount);
        }
        return true;
    }

    public function getBattlefieldList(): Collection
    {
        return Dominion::with('user')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function count(): int
    {
        return Dominion::count();
    }

    public function sum(string $column): float
    {
        return (float)Dominion::sum($column);
    }

    public function search(string $query, int $limit = 20): Collection
    {
        return Dominion::with('user')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhereHas('user', function($q) use ($query) {
                $q->where('username', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();
    }

    public function getAll(int $limit = 50): Collection
    {
        return Dominion::with('user')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();
    }

    public function findFullProfile(int $id): ?Dominion
    {
        return Dominion::with(['user', 'manpower.unit', 'structures.structure', 'race'])
            ->find($id);
    }

    public function getColumns(): array
    {
        return Capsule::schema()->getColumnListing('dominions');
    }
}
