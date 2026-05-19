<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BankRepositoryInterface
{
    public function logTransaction(int $kingdomId, string $type, int $amount): void;
    public function getTransactionsPaginated(int $kingdomId, int $page, int $perPage): LengthAwarePaginator;
    public function resetDailyLimits(int $kingdomId): void;
}