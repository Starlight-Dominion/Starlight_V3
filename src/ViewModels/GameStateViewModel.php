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
    public string $kingdomName;
    public string $raceName;

    public function __construct(
        public Kingdom $kingdom,
        GameService $gameService,
        public string $advice,
        public string $username,
        public array $advisorHistory = []
    ) {
        $this->realmTime = $gameService->getRealmTime()->format('H:i T');
        $this->secondsToNextTick = $gameService->getSecondsToNextTick();
        
        // Calculate based on normalized Dominion (Kingdom) data
        $this->level = (int)floor(sqrt($kingdom->xp / 100)) + 1;
        $this->xpProgress = $gameService->calculateXpProgress((int)$kingdom->xp);
        
        $this->kingdomName = (string)$kingdom->kingdom_name;
        
        // Eager-loaded race name
        $this->raceName = $kingdom->race ? $kingdom->race->name : 'Unknown';
    }
}