<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\GameLog;

class LogService
{
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
        GameLog::create([
            'dominion_id' => $dominionId,
            'action' => $action,
            'description' => $description,
            'amount' => $amount,
            'metadata' => $metadata
        ]);
    }
}
