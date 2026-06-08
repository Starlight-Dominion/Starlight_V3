<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\TacticalService;
use sdo\Services\ConfigService;

class DashboardController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private TacticalService $tacticalService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function index(): string
    {
        if (!$this->authService->isLoggedIn($_SESSION)) {
            $this->redirect('/login');
        }

        $dominion = $this->gameService->getDominionByUserId((int)$_SESSION['user_id']);

        if (!$dominion) {
            session_destroy();
            $this->redirect('/login?error=dominion_not_found');
        }

        $incomeBreakdown = $this->gameService->getIncomeBreakdown($dominion->id);
        $tactical = $this->tacticalService->getTacticalOverview($dominion->id);

        // Fetch wins/losses
        $wins = \sdo\Models\BattleLog::where('attacker_id', $dominion->id)
            ->whereRaw('attacker_damage > defender_damage')
            ->count();
        $losses = \sdo\Models\BattleLog::where('defender_id', $dominion->id)
            ->whereRaw('attacker_damage > defender_damage')
            ->count();

        // Calculate Net Worth (Simplified Legacy Style)
        // NW = (Credits + Banked) + (Units * 50) + (XP * 10)
        $manpowerTotal = 0;
        foreach ($tactical['manpower'] as $m) {
            $manpowerTotal += $m['quantity'];
        }
        $netWorth = (int)($dominion->credits + $dominion->credits_banked + ($manpowerTotal * 50) + ($dominion->xp * 10));

        // Format for Legacy Frontend
        $legacyData = [
            'user' => [
                'character_name' => $dominion->name,
                'avatar_path' => $dominion->user->avatar_path,
                'level' => $this->gameService->calculateLevel($dominion->xp),
                'race' => $dominion->race->name,
                'class' => 'Commander', // Standardizing as Commander
                'credits' => $dominion->credits,
                'banked_credits' => $dominion->credits_banked,
                'net_worth' => $netWorth,
                'untrained_citizens' => $dominion->citizens,
                'workers' => $tactical['army']['workers'] ?? 0,
                'soldiers' => $tactical['army']['soldiers'] ?? 0,
                'guards' => $tactical['army']['guards'] ?? 0,
                'sentries' => $tactical['army']['sentries'] ?? 0,
                'spies' => $tactical['army']['spies'] ?? 0,
                'attack_turns' => $dominion->turns,
                'previous_login_at' => $dominion->updated_at?->format('Y-m-d H:i') ?? 'N/A',
                'previous_login_ip' => $_SERVER['REMOTE_ADDR'] // Simplified
            ],
            'economy' => [
                'total_population' => $dominion->citizens + $manpowerTotal,
                'citizens_per_turn' => $this->gameService->getTotalCitizenGrowth($dominion->id),
                'total_military' => $manpowerTotal - ($tactical['army']['workers'] ?? 0),
                'income_per_turn' => $incomeBreakdown['total']
            ],
            'military' => [
                'offense_power' => $tactical['ratings']['offense'],
                'defense_rating' => $tactical['ratings']['defense'],
                'spy_offense' => $tactical['ratings']['espionage'],
                'sentry_defense' => $tactical['ratings']['sentry'],
                'wins' => $wins,
                'losses' => $losses
            ],
            'alliance' => [
                'name' => 'None',
                'tag' => ''
            ],
            'advisor' => [
                'seconds_until_next_turn' => $this->gameService->getSecondsToNextTick(),
                'advice' => $this->advisorService->getContextualAdviceFromDominion($dominion),
                'server_time' => [
                    'formatted_et' => $this->gameService->getRealmTime()->format('H:i T')
                ],
                'latest_news' => [
                    'title' => $this->configService->get('dominion_news', 'Sector is calm.'),
                    'date' => date('M j, Y')
                ],
                'user_stats' => [
                    'level' => $this->gameService->calculateLevel($dominion->xp),
                    'experience' => $dominion->xp,
                    'credits' => $dominion->credits,
                    'banked_credits' => $dominion->credits_banked,
                    'untrained_citizens' => $dominion->citizens,
                    'attack_turns' => $dominion->turns
                ],
                'xp_for_next' => (int)pow($this->gameService->calculateLevel($dominion->xp), 2) * 100,
                'xp_progress' => $this->gameService->calculateXpProgress($dominion->xp)
            ]
        ];

        return $this->render('dashboard/index', $legacyData);
    }
}
