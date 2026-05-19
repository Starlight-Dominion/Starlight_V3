<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\Kingdom;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKingdomRepository implements KingdomRepositoryInterface
{
    public function findById(int $id): ?Kingdom
    {
        return Kingdom::with('user')->find($id);
    }

    public function findByUserId(int $userId): ?Kingdom
    {
        return Kingdom::with('user')->where('user_id', $userId)->first();
    }

    public function lockForUpdate(int $id): ?Kingdom
    {
        return Kingdom::lockForUpdate()->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $kingdom = Kingdom::find($id);
        if (!$kingdom) return false;

        return $kingdom->update($data);
    }

    public function incrementStats(int $id, array $stats): bool
    {
        $kingdom = Kingdom::find($id);
        if (!$kingdom) return false;

        foreach ($stats as $column => $amount) {
            $kingdom->increment($column, $amount);
        }
        return true;
    }

    public function decrementStats(int $id, array $stats): bool
    {
        $kingdom = Kingdom::find($id);
        if (!$kingdom) return false;

        foreach ($stats as $column => $amount) {
            $kingdom->decrement($column, $amount);
        }
        return true;
    }

    public function getBattlefieldList(): Collection
    {
        return Kingdom::with('user')
            ->orderBy('kingdom_name', 'asc')
            ->get();
    }
}