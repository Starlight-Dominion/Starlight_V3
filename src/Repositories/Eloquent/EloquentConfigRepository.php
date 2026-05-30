<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\GameSetting;
use sdo\Repositories\Interfaces\ConfigRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentConfigRepository implements ConfigRepositoryInterface
{
    public function find(string $key): ?GameSetting
    {
        return GameSetting::find($key);
    }

    public function updateOrCreate(string $key, string $value): bool
    {
        return (bool)GameSetting::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
    }

    public function all(): Collection
    {
        return GameSetting::all();
    }
}
