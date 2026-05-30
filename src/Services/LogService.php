<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\LogRepositoryInterface;

class LogService
{
    public function __construct(private LogRepositoryInterface $logRepository) {}

    /**
     * Log a comprehensive game action.
     */
    public function log(
        int $dominionId, 
        string $action, 
        string $description, 
        ?int $amount = null, 
        array $metadata = []
    ): void {
        $this->logRepository->log([
            'dominion_id' => $dominionId,
            'action' => $action,
            'description' => $description,
            'amount' => $amount,
            'metadata' => $metadata
        ]);
    }
}
