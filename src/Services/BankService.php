<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\BankRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use DateTime;
use DateTimeZone;
use Exception;

class BankService
{
    public function __construct(
        private BankRepositoryInterface $bankRepository,
        private KingdomRepositoryInterface $kingdomRepository
    ) {}

    public function getTransactions(int $kingdomId, int $page, int $limit): array
    {
        $paginator = $this->bankRepository->getTransactionsPaginated($kingdomId, $page, $limit);

        // Map to existing view requirements for backward compatibility with PaginationService
        return [
            'transactions' => $paginator->items(),
            'pagination' => new PaginationService(
                (int)$paginator->total(),
                (int)$paginator->currentPage(),
                (int)$paginator->perPage(),
                '/bank'
            )
        ];
    }

    public function deposit(int $kingdomId, int $amount): array
    {
        try {
            return Capsule::transaction(function() use ($kingdomId, $amount) {
                $kingdom = $this->kingdomRepository->lockForUpdate($kingdomId);

                if (!$kingdom) {
                    throw new Exception("Kingdom not found.");
                }

                if ($amount > $kingdom->gold * 0.8) {
                    throw new Exception("You cannot deposit more than 80% of your on-hand gold.");
                }

                $this->rechargeDeposits($kingdom);
                if ($kingdom->deposits_today >= 4) {
                    throw new Exception("You have no available deposits. They recharge 6 hours after use.");
                }

                $this->kingdomRepository->update($kingdomId, [
                    'gold' => $kingdom->gold - $amount,
                    'gold_in_bank' => $kingdom->gold_in_bank + $amount,
                    'deposits_today' => $kingdom->deposits_today + 1,
                    'last_deposit_recharge' => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')
                ]);

                $this->bankRepository->logTransaction($kingdomId, 'deposit', $amount);

                return ['success' => true];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function withdraw(int $kingdomId, int $amount): array
    {
        try {
            return Capsule::transaction(function() use ($kingdomId, $amount) {
                $kingdom = $this->kingdomRepository->lockForUpdate($kingdomId);

                if (!$kingdom || $amount > $kingdom->gold_in_bank) {
                    throw new Exception("Insufficient funds in bank.");
                }

                $this->kingdomRepository->update($kingdomId, [
                    'gold' => $kingdom->gold + $amount,
                    'gold_in_bank' => $kingdom->gold_in_bank - $amount
                ]);

                $this->bankRepository->logTransaction($kingdomId, 'withdraw', $amount);

                return ['success' => true];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function rechargeDeposits($kingdom): void
    {
        if ($kingdom->deposits_today > 0 && $kingdom->last_deposit_recharge) {
            $rechargeTime = new DateTime($kingdom->last_deposit_recharge);
            $rechargeTime->modify('+6 hours');
            $now = new DateTime();

            if ($now >= $rechargeTime) {
                $diff = $now->getTimestamp() - (new DateTime($kingdom->last_deposit_recharge))->getTimestamp();
                $rechargeCount = (int)min($kingdom->deposits_today, floor($diff / 21600));

                $this->kingdomRepository->update($kingdom->id, [
                    'deposits_today' => $kingdom->deposits_today - $rechargeCount
                ]);
                
                // Refresh local object for the caller
                $kingdom->deposits_today -= $rechargeCount;
            }
        }
    }
}