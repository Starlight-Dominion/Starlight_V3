<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Repositories\Interfaces\BankRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentBankRepository implements BankRepositoryInterface
{
    public function logTransaction(int $kingdomId, string $type, int $amount): void
    {
        Capsule::table('bank_transactions')->insert([
            'kingdom_id' => $kingdomId,
            'transaction_type' => $type,
            'amount' => $amount,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getTransactionsPaginated(int $kingdomId, int $page, int $perPage): LengthAwarePaginator
    {
        return \sdo\Models\BankTransaction::where('kingdom_id', $kingdomId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function resetDailyLimits(int $kingdomId): void
    {
        Capsule::table('dominions')
            ->where('id', $kingdomId)
            ->update([
                'deposits_today' => 0,
                'last_deposit_timestamp' => date('Y-m-d H:i:s')
            ]);
    }
}
