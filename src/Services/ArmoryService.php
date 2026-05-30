<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\ArmoryRepositoryInterface;
use sdo\Repositories\Interfaces\DominionArmoryRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\LogService;
use Exception;

class ArmoryService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private ArmoryRepositoryInterface $armoryRepository,
        private DominionArmoryRepositoryInterface $dominionArmoryRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private StructureRepositoryInterface $structureRepository,
        private TransactionManager $transactionManager,
        private LogService $logService
    ) {}

    public function getArmoryData(int $domId): array
    {
        $dom = $this->dominionRepository->findById($domId);
        if (!$dom) {
            throw new Exception("Dominion not found.");
        }

        $unitTypes = $this->armoryRepository->allUnitTypes();
        $categoriesByUnitType = $this->armoryRepository->allCategories()->groupBy('unit_type_id');
        $itemsByCategory = $this->armoryRepository->all()->groupBy('category_id');
        $inventory = $this->dominionArmoryRepository->getInventory($domId)->keyBy('item_id');

        $manpowerBySlug = $this->manpowerRepository->getManpowerBySlugMap($domId);

        $loadouts = [];
        foreach ($unitTypes as $uType) {
            $count = $manpowerBySlug[$uType->slug] ?? 0;

            $loadouts[$uType->slug] = [
                'title' => $uType->title,
                'unit_count' => (int)$count,
                'categories' => []
            ];

            $typeCats = $categoriesByUnitType[$uType->id] ?? collect();
            foreach ($typeCats as $cat) {
                $items = ($itemsByCategory[$cat->id] ?? collect())
                    ->map(function($item) use ($inventory, $dom) {
                        $invItem = $inventory[$item->id] ?? null;
                        $item->owned_quantity = $invItem ? $invItem->quantity : 0;
                        $item->is_equipped = $invItem ? $invItem->is_equipped : false;
                        $item->unlocked = ($dom->armory_level >= $item->armory_level_req);
                        return $item;
                    })->keyBy('slug');

                $loadouts[$uType->slug]['categories'][$cat->slug] = [
                    'title' => $cat->name,
                    'slots' => $cat->slots,
                    'items' => $items
                ];
            }
        }

        $nextLevel = $dom->armory_level + 1;
        $upgrade = $this->structureRepository->findLevel(3, $nextLevel); // Structure 3 is Armory

        return [
            'loadouts' => $loadouts,
            'armory_level' => $dom->armory_level,
            'upgrade_cost' => $upgrade ? $upgrade->cost : null
        ];
    }

    public function toggleEquip(int $domId, int $itemId): array
    {
        return $this->transactionManager->transaction(function() use ($domId, $itemId) {
            $inv = $this->dominionArmoryRepository->findItem($domId, $itemId);

            if (!$inv || $inv->quantity <= 0) {
                throw new Exception("Item not found in inventory.");
            }

            $newState = !$inv->is_equipped;
            $this->dominionArmoryRepository->toggleEquip($domId, $itemId, $newState);

            $item = $this->armoryRepository->findById($itemId);
            $status = $newState ? "equipped" : "unequipped";

            $this->logService->log(
                $domId,
                'armory_equip',
                "Commander $status {$item->name}.",
                0,
                ['item' => $item->slug, 'is_equipped' => $newState]
            );

            return ['success' => true, 'is_equipped' => $newState, 'message' => "Item $status."];
        });
    }

    public function upgradeItem(int $domId, int $itemId, int $qty): array
    {
        return $this->transactionManager->transaction(function() use ($domId, $itemId, $qty) {
            $dom = $this->dominionRepository->lockForUpdate($domId);
            $targetItem = $this->armoryRepository->findById($itemId);

            if (!$targetItem || !$targetItem->requirement_slug) {
                throw new Exception("Invalid upgrade target.");
            }

            if ($dom->armory_level < $targetItem->armory_level_req) {
                throw new Exception("Tech requirement not met.");
            }

            $reqItem = $this->armoryRepository->findBySlug($targetItem->requirement_slug);
            if (!$reqItem) throw new Exception("Requirement data missing.");

            $invReq = $this->dominionArmoryRepository->findItem($domId, $reqItem->id);

            if (!$invReq || $invReq->quantity < $qty) {
                throw new Exception("Insufficient {$reqItem->name} for upgrade.");
            }

            $totalCost = $targetItem->cost * $qty;
            if ($dom->credits < $totalCost) throw new Exception("Insufficient credits.");

            // Process Upgrade
            $this->dominionRepository->update($domId, ['credits' => $dom->credits - $totalCost]);

            // Consume Req
            $this->dominionArmoryRepository->updateQuantity($domId, $reqItem->id, -$qty);

            // Add Target
            $this->dominionArmoryRepository->updateQuantity($domId, $itemId, $qty);

            $this->logService->log(
                $domId,
                'armory_upgrade_item',
                "Commander upgraded $qty x {$reqItem->name} into {$targetItem->name}.",
                $totalCost,
                ['from' => $reqItem->slug, 'to' => $targetItem->slug, 'quantity' => $qty]
            );

            return ['success' => true, 'message' => "Successfully upgraded $qty items."];
        });
    }

    public function buyItem(int $domId, int $itemId, int $qty): array
    {
        return $this->transactionManager->transaction(function() use ($domId, $itemId, $qty) {
            $dom = $this->dominionRepository->lockForUpdate($domId);
            $item = $this->armoryRepository->findById($itemId);

            if (!$item || $dom->armory_level < $item->armory_level_req) {
                throw new Exception("Tech requirement not met.");
            }

            $totalCost = $item->cost * $qty;
            if ($dom->credits < $totalCost) throw new Exception("Insufficient credits.");

            $this->dominionRepository->update($domId, ['credits' => $dom->credits - $totalCost]);
            $this->dominionArmoryRepository->updateQuantity($domId, $itemId, $qty);

            $this->logService->log(
                $domId,
                'armory_buy',
                "Commander purchased $qty x {$item->name} from the armory.",
                $totalCost,
                ['item' => $item->slug, 'quantity' => $qty]
            );

            return ['success' => true, 'message' => "Acquired $qty x {$item->name}."];
        });
    }

    public function sellItem(int $domId, int $itemId, int $qty): array
    {
        return $this->transactionManager->transaction(function() use ($domId, $itemId, $qty) {
            $dom = $this->dominionRepository->lockForUpdate($domId);
            $inv = $this->dominionArmoryRepository->findItem($domId, $itemId);

            if (!$inv || $inv->quantity < $qty) throw new Exception("Insufficient stock.");

            $item = $this->armoryRepository->findById($itemId);
            $refund = (int)($item->cost * 0.5 * $qty);

            $this->dominionRepository->update($domId, ['credits' => $dom->credits + $refund]);
            $this->dominionArmoryRepository->updateQuantity($domId, $itemId, -$qty);

            $this->logService->log(
                $domId,
                'armory_sell',
                "Commander decommissioned $qty x {$item->name} for salvage.",
                $refund,
                ['item' => $item->slug, 'quantity' => $qty]
            );

            return ['success' => true, 'message' => "Decommissioned for $refund Credits."];
        });
    }

    public function upgradeArmory(int $domId): array
    {
        return $this->transactionManager->transaction(function() use ($domId) {
            $dom = $this->dominionRepository->lockForUpdate($domId);
            $next = $dom->armory_level + 1;
            
            $levelData = $this->structureRepository->findLevel(3, $next);

            if (!$levelData) throw new Exception("Maximum tech level reached.");
            if ($dom->credits < $levelData->cost) throw new Exception("Insufficient credits.");

            $this->dominionRepository->update($domId, [
                'credits' => $dom->credits - $levelData->cost,
                'armory_level' => $next
            ]);

            $this->logService->log(
                $domId,
                'armory_upgrade',
                "Commander upgraded the Armory to Rank $next.",
                (int)$levelData->cost,
                ['new_level' => $next]
            );

            return ['success' => true, 'message' => "Armory upgraded to Rank $next."];
        });
    }
}
