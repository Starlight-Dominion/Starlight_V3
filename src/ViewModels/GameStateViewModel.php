<?php
declare(strict_types=1);

namespace sdo\ViewModels;

use sdo\Models\Dominion;
use sdo\Services\GameService;

readonly class GameStateViewModel
{
    public string $realmTime;
    public int $secondsToNextTick;
    public int $level;
    public int $xpProgress;
    public string $kingdomName;
    public string $raceName;

    public function __construct(
        public Dominion $dominion,
        GameService $gameService,
        public string $advice,
        public string $username,
        public array $advisorHistory = []
    ) {
        $this->realmTime = $gameService->getRealmTime()->format('H:i T');
        $this->secondsToNextTick = $gameService->getSecondsToNextTick();
        
        // Calculate based on normalized Dominion data
        $this->level = $gameService->calculateLevel((int)$dominion->xp);
        $this->xpProgress = $gameService->calculateXpProgress((int)$dominion->xp);
        
        $this->kingdomName = (string)$dominion->name;
        
        // Eager-loaded race name
        $this->raceName = $dominion->race ? $dominion->race->name : 'Unknown';
    }
}
