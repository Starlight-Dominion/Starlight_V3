<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\BotProfile;
use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\Structure;
use sdo\Infrastructure\TransactionManager;
use DateTime;
use Exception;

class BotAutomationService
{
    public function __construct(
        private TransactionManager $transactionManager,
        private BattlefieldService $battlefieldService,
        private UpgradesService $upgradesService,
        private TrainingService $trainingService,
        private FoundationService $foundationService,
        private MinesService $minesService,
        private GameService $gameService
    ) {}

    public function processBots(): void
    {
        // Find bots that need to act
        $bots = User::where('is_bot', true)
            ->whereNotNull('bot_profile_id')
            ->with(['botProfile', 'dominion'])
            ->get();

        foreach ($bots as $bot) {
            $profile = $bot->botProfile;
            if (!$profile) continue;

            $lastAction = $bot->last_bot_action_at;
            $frequency = (int)$profile->action_frequency_minutes;

            if ($lastAction) {
                $lastActionTime = new DateTime($lastAction->format('Y-m-d H:i:s'));
                $diffSeconds = time() - $lastActionTime->getTimestamp();
                if ($diffSeconds < ($frequency * 60)) {
                    continue;
                }
            }

            $this->executeBotAction($bot);
        }
    }

    private function executeBotAction(User $bot): void
    {
        $profile = $bot->botProfile;
        $dominion = $bot->dominion;

        if (!$dominion) return;

        // Weighted RNG
        $weights = [
            'attack'  => (int)$profile->weight_attack,
            'build'   => (int)$profile->weight_build,
            'train'   => (int)$profile->weight_train,
            'explore' => (int)$profile->weight_explore
        ];

        $totalWeight = array_sum($weights);
        if ($totalWeight <= 0) return;

        $rand = rand(1, $totalWeight);
        $current = 0;
        $action = 'explore';

        foreach ($weights as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                $action = $key;
                break;
            }
        }

        try {
            switch ($action) {
                case 'attack':
                    $this->performAttack($bot, $dominion);
                    break;
                case 'build':
                    $this->performBuild($bot, $dominion);
                    break;
                case 'train':
                    $this->performTrain($bot, $dominion);
                    break;
                case 'explore':
                    $this->performExplore($bot, $dominion);
                    break;
            }
        } catch (Exception $e) {
            // Silence errors during bot loop to prevent worker crash
        }

        $bot->update(['last_bot_action_at' => new DateTime()]);
    }

    private function performAttack(User $bot, Dominion $dominion): void
    {
        if ($dominion->turns < 15) return;
        
        $targets = $this->battlefieldService->getBattlefieldList();
        if (empty($targets)) return;

        // Pick a random target that isn't itself
        $validTargets = array_filter($targets, fn($t) => (int)$t['kingdom_id'] !== (int)$dominion->id);
        if (empty($validTargets)) return;

        // Target with most gold first? No, let's keep it random for now
        $target = $validTargets[array_rand($validTargets)];
        
        $turns = rand(10, min(15, $dominion->turns));
        $this->battlefieldService->executeAttack((int)$dominion->id, (int)$target['kingdom_id'], $turns);
    }

    private function performBuild(User $bot, Dominion $dominion): void
    {
        if ($dominion->credits < 10000) return;

        // Repair foundation if needed
        if ($dominion->foundation_hp < $dominion->foundation_max_hp) {
            try {
                $this->foundationService->repair((int)$dominion->id);
                return;
            } catch (Exception $e) {}
        }

        // Try random structure upgrade
        $structures = Structure::all();
        if ($structures->isEmpty()) return;

        $structure = $structures->random();
        try {
            $this->foundationService->upgrade((int)$dominion->id, (int)$structure->id);
        } catch (Exception $e) {
            // If failed, try to upgrade current mine
            try {
                $this->minesService->upgradeCurrentMine((int)$dominion->id);
            } catch (Exception $e) {}
        }
    }

    private function performTrain(User $bot, Dominion $dominion): void
    {
        if ($dominion->credits < 2000 || $dominion->citizens < 10) return;
        
        $units = Unit::all();
        if ($units->isEmpty()) return;

        // Favor Soldiers or Guards
        $slugs = ['soldiers', 'guards', 'spies', 'sentries'];
        $slug = $slugs[array_rand($slugs)];
        $unit = $units->where('slug', $slug)->first() ?: $units->random();

        $quantity = min(50, (int)floor($dominion->credits / $unit->cost_credits), $dominion->citizens);
        
        if ($quantity > 0) {
            $this->trainingService->train((int)$dominion->id, (int)$unit->id, $quantity);
        }
    }

    private function performExplore(User $bot, Dominion $dominion): void
    {
        if ($dominion->turns < 5) return;

        $this->transactionManager->transaction(function() use ($dominion) {
            $dom = Dominion::find($dominion->id);
            $dom->update([
                'turns' => $dom->turns - 5,
                'citizens' => $dom->citizens + rand(1, 5),
                'xp' => $dom->xp + rand(50, 100)
            ]);
        });
    }
}
