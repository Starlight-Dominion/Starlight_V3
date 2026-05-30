<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\TickLog;
use Illuminate\Support\Collection;

interface TickRepositoryInterface
{
    public function getAllDominionIds(): array;
    public function getTickData(array $dominionIds): Collection;
    public function applyTickResults(int $dominionId, int $credits, int $citizens, int $turns, string $tickTime): bool;
    public function createTickLog(array $data): TickLog;
    public function updateTickLogByTickId(string $tickId, array $data): bool;
    public function findTickLogByTickId(string $tickId): ?TickLog;
}
