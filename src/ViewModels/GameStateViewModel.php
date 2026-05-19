<?php

declare(strict_types=1);

namespace sdo\ViewModels;

use sdo\Models\Kingdom;
use sdo\Services\GameService;

readonly class GameStateViewModel
{
public string $realmTime;
public int $secondsToNextTick;
public int $level;
public int $xpProgress;
public string $goldFormatted;
public string $bankFormatted;
public string $citizensFormatted;
public string $turnsFormatted;
public string $kingdomName;

public function __construct(
public Kingdom $kingdom,
GameService $gameService,
public string $advice,
public string $username,
public array $advisorHistory = []
) {
$this->realmTime = $gameService->getRealmTime()->format('H:i T');
$this->secondsToNextTick = $gameService->getSecondsToNextTick();
$this->level = $gameService->calculateLevel((int)$kingdom->xp);
$this->xpProgress = $gameService->calculateXpProgress((int)$kingdom->xp);

$this->goldFormatted = number_format((int)$kingdom->gold);
$this->bankFormatted = number_format((int)$kingdom->gold_in_bank);
$this->citizensFormatted = number_format((int)$kingdom->citizens);
$this->turnsFormatted = number_format((int)$kingdom->turns);
$this->kingdomName = (string)$kingdom->kingdom_name;
}
}