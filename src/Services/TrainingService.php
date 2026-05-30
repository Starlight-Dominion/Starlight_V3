<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\LogService;
use Exception;

class TrainingService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private TransactionManager $transactionManager,
        private LogService $logService
    ) {}

    public function getUnitConfig(): array
    {
        return $this->unitRepository->all()->keyBy('slug')->toArray();
    }

    public function train(int $dominionId, string $unitSlug, int $quantity): array
    {
        $unit = $this->unitRepository->findBySlug($unitSlug);
        if (!$unit || $quantity <= 0) return ['success' => false, 'message' => 'Invalid parameters.'];

        return $this->transactionManager->transaction(function() use ($dominionId, $unit, $quantity) {
            $dom = $this->dominionRepository->lockForUpdate($dominionId);
            if (!$dom) throw new Exception("Dominion not found.");

            $cost = $unit->cost_credits * $quantity;
            $citizens = $unit->cost_citizens * $quantity;

            if ($dom->credits < $cost || $dom->citizens < $citizens) {
                throw new Exception("Insufficient resources for mobilization.");
            }

            $this->dominionRepository->update($dominionId, [
                'credits' => $dom->credits - $cost,
                'citizens' => $dom->citizens - $citizens
            ]);

            $this->manpowerRepository->updateQuantity($dominionId, (int)$unit->id, $quantity);

            $this->logService->log(
                $dominionId,
                'training_enlist',
                "Commander enlisted {$quantity} {$unit->name}.",
                $cost,
                ['unit' => $unit->slug, 'quantity' => $quantity]
            );

            return ['success' => true, 'message' => "Enlisted {$quantity} {$unit->name}."];
        });
    }
}
