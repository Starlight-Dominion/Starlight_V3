<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

interface CombatRepositoryInterface
{
    public function logBattle(array $data): int;
    public function getLogsByKingdom(int $kingdomId, int $limit = 10): array;
    public function findLogById(int $id): ?object;
    public function countRecentBattles(int $hours): int;
    public function countRecentBattlesBetween(int $attackerId, int $defenderId, int $hours): int;
}