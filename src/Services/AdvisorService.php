<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;

class AdvisorService
{
    public function __construct(
        private GameService $gameService,
        private ConfigService $configService
    ) {}

    public function getAdvice(): string
    {
        $raw = $this->configService->get('ai_advisor_messages', '');
        $messages = array_filter(array_map('trim', explode("\n", $raw)));

        if (empty($messages)) {
            return "Welcome, Commander. Infrastructure development is advised.";
        }

        return $messages[array_rand($messages)];
    }

    /**
     * Return contextual advisor advice based on current level and XP progress.
     */
    public function getContextualAdvice(int $level, int $xp): string
    {
        $xp = max(0, $xp);
        $currentThreshold = ($level - 1) * ($level - 1) * 100;
        $nextThreshold = $level * $level * 100;
        
        $progress = 0.0;
        if ($nextThreshold > $currentThreshold) {
            $progress = (($xp - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100.0;
            $progress = max(0, min(100, $progress));
        }

        if ($progress < 20) {
            return 'You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.';
        } elseif ($progress < 50) {
            return 'Your sector is gaining momentum. Consider boosting extraction and growth to accelerate XP progression.';
        } elseif ($progress < 75) {
            return 'You are approaching a new tier. Prepare for upgrades that unlock higher production and defenses.';
        } else {
            return 'You are near a level-up. Invest in infrastructure and military upgrades to maximize the payoff of the next tick.';
        }
    }
    
    /**
     * Return curated advisor advice. Contextual logic is overridden by High Command directives.
     */
    public function getContextualAdviceFromDominion(?Dominion $dominion): string
    {
        return $this->getAdvice();
    }

    /**
     * Prepare a log entry for advisor history.
     */
    public function formatAdviceLog(string $text, array $history): array
    {
        $history[] = [
            'ts' => (new \DateTime())->format('Y-m-d H:i:s'),
            'text' => $text,
        ];
        if (count($history) > 5) {
            $history = array_slice($history, -5);
        }
        return $history;
    }
}
