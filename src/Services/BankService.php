<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\BankRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\LogService;
use Exception;
use DateTime;

class BankService
{
    private const MAX_DEPOSITS = 6;
    private const RECHARGE_SECONDS = 14400; // 4 Hours (6 slots in 24h)

    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private BankRepositoryInterface $bankRepository,
        private TransactionManager $transactionManager,
        private LogService $logService
    ) {}

    public function getTransactions(int $dominionId, int $page, int $limit): array
    {
        $paginator = $this->bankRepository->getTransactionsPaginated($dominionId, $page, $limit);

        return [
            'transactions' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage()
            ]
        ];
    }

    public function deposit(int $domId, int $amount): array
    {
        return $this->transactionManager->transaction(function() use ($domId, $amount) {
            $dom = $this->dominionRepository->lockForUpdate($domId);
            if (!$dom) throw new Exception("Dominion not found.");
            
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
            $this->dominionRepository->update($domId, [
                'credits' => $dom->credits - $amount,
                'credits_banked' => $dom->credits_banked + $amount,
                'deposits_today' => $dom->deposits_today + 1,
                'last_deposit_timestamp' => new DateTime()
            ]);

            $this->bankRepository->logTransaction($domId, 'deposit', $amount);

            // Comprehensive Logging
            $this->logService->log(
                $domId,
                'bank_deposit',
                "Commander secured " . number_format($amount) . " credits in the deep-space vault.",
                $amount,
                ['credits_remaining' => $dom->credits - $amount, 'banked_total' => $dom->credits_banked + $amount]
            );

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
        return $this->transactionManager->transaction(function() use ($domId, $amount) {
            $dom = $this->dominionRepository->lockForUpdate($domId);
            if (!$dom) throw new Exception("Dominion not found.");
            
            if ($dom->credits_banked < $amount) throw new Exception("Insufficient vault reserves.");

            $this->dominionRepository->update($domId, [
                'credits_banked' => $dom->credits_banked - $amount,
                'credits' => $dom->credits + $amount
            ]);

            $this->bankRepository->logTransaction($domId, 'withdraw', $amount);

            // Comprehensive Logging
            $this->logService->log(
                $domId,
                'bank_withdraw',
                "Commander liquidated " . number_format($amount) . " credits from the vault.",
                $amount,
                ['credits_total' => $dom->credits + $amount, 'banked_remaining' => $dom->credits_banked - $amount]
            );

            return ['success' => true, 'message' => "Credits liquidated for immediate use."];
        });
    }
}
