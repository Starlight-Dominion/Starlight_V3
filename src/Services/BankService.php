<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use DateTime;

class BankService
{
    private const MAX_DEPOSITS = 4;
    private const RECHARGE_SECONDS = 21600; // 6 Hours

    public function getTransactions(int $dominionId, int $page, int $limit): array
    {
        $query = Capsule::table('bank_transactions')
            ->where('kingdom_id', $dominionId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $items = $query->offset($offset)->limit($limit)->get()->toArray();

        return [
            'transactions' => $items,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit]
        ];
    }

    public function deposit(int $domId, int $amount): array
    {
        return Capsule::transaction(function() use ($domId, $amount) {
            $dom = Dominion::lockForUpdate()->find($domId);
            
            // 1. 80% Rule
            if ($amount > ($dom->credits * 0.8)) {
                throw new Exception("Security Protocol: Cannot deposit more than 80% of liquid assets.");
            }

            // 2. Cooldown Recovery
            $this->rechargeSlots($dom);
            if ($dom->deposits_today >= self::MAX_DEPOSITS) {
                throw new Exception("Bank Vault Locked: Daily deposit frequency exceeded. Cooldown active.");
            }

            // 3. Execution
            $dom->credits -= $amount;
            $dom->credits_banked += $amount;
            $dom->deposits_today += 1;
            $dom->last_deposit_timestamp = new DateTime();
            $dom->save();

            Capsule::table('bank_transactions')->insert([
                'kingdom_id' => $domId,
                'transaction_type' => 'deposit',
                'amount' => $amount
            ]);

            return ['success' => true, 'message' => "Assets secured in deep-space vault."];
        });
    }

    private function rechargeSlots(Dominion $dom): void
    {
        if ($dom->deposits_today > 0 && $dom->last_deposit_timestamp) {
            $seconds = time() - $dom->last_deposit_timestamp->getTimestamp();
            $reclaimed = (int)floor($seconds / self::RECHARGE_SECONDS);
            if ($reclaimed > 0) {
                $dom->deposits_today = max(0, $dom->deposits_today - $reclaimed);
            }
        }
    }

    public function withdraw(int $domId, int $amount): array
    {
        return Capsule::transaction(function() use ($domId, $amount) {
            $dom = Dominion::lockForUpdate()->find($domId);
            if ($dom->credits_banked < $amount) throw new Exception("Insufficient vault reserves.");

            $dom->credits_banked -= $amount;
            $dom->credits += $amount;
            $dom->save();

            Capsule::table('bank_transactions')->insert([
                'kingdom_id' => $domId,
                'transaction_type' => 'withdraw',
                'amount' => $amount
            ]);

            return ['success' => true, 'message' => "Credits liquidated for immediate use."];
        });
    }
}