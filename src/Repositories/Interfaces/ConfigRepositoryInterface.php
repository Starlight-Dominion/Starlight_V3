<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\GameSetting;
use Illuminate\Support\Collection;

interface ConfigRepositoryInterface
{
    public function find(string $key): ?GameSetting;
    public function updateOrCreate(string $key, string $value): bool;
    public function all(): Collection;
}
