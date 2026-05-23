<?php

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\DominionManpower;
use sdo\Models\Unit;
use Exception;
use DateTime;

class UntrainingService
{
    public const HOLD_PERIOD_SECONDS = 3600; // 1 hour hold period for citizens
    private float $holdFactor;
    private array $minesConfig;

    public function __construct()
    {
        $this->minesConfig = require __DIR__ . '/../../config/mines.php';
        $this->holdFactor = 0.5 + (count($this->minesConfig['unlocks'])) * 0.01; // Slight bonus based on mine count
    }

    /**
     * Check if a dominion can currently untrain (cooldown period)
     */
    public function canUntrainCitizens(int $dominionId): array
    {
        $dominion = Dominion::find($dominionId);

        if (!$dominion || $dominion->last_untrained === null) {
            return [
                'success' => true,
                'message' => 'No previous untraining recorded.',
                'cool_down' => 0,
                'available_now' => true,
            ];
        }

        $cooldownEnd = (clone $dominion->last_untrained)->modify('+1 hour');
        $now = new DateTime();

        if ($now < $cooldownEnd) {
            $secondsRemaining = $cooldownEnd->getTimestamp() - $now->getTimestamp();
            return [
                'success' => false,
                'message' => "Untraining is on cooldown. {$secondsRemaining} seconds remaining.",
                'cool_down' => $secondsRemaining > 0 ? $secondsRemaining : 0,
                'available_now' => false,
            ];
        }

        return [
            'success' => true,
            'message' => 'You are in the untraining grace period.',
            'cool_down' => 0,
            'available_now' => true,
        ];
    }

    /**
     * Get hold time remaining for released citizens
     */
    public function getHoldTimeRemaining(int $dominionId): ?int
    {
        $dominion = Dominion::find($dominionId);

        if (!$dominion || !$dominion->last_untrained) return null;

        $cooldownEnd = (clone $dominion->last_untrained)->modify('+1 hour');
        $now = new DateTime();

        if ($now < $cooldownEnd) {
            return $cooldownEnd->getTimestamp() - $now->getTimestamp();
        }

        return 0;
    }

    /**
     * Release held citizens back into the pool
     */
    public function releaseHeldCitizens(int $dominionId): array
    {
        $dominion = Dominion::find($dominionId);

        if (!$dominion) {
            return ['success' => false, 'message' => 'Sector not found.'];
        }

        $heldCitizens = (int)($dominion->held_citizens ?? 0);

        if ($heldCitizens === 0) {
            return ['success' => true, 'message' => 'No citizens held to release.',];
        }

        $dominion->increment('citizens', $heldCitizens);
        $dominion->held_citizens = 0;
        $dominion->save();

        return [
            'success' => true,
            'message' => "Reclaimed {$heldCitizens} held citizens to your population pool.",
            'released' => $heldCitizens,
        ];
    }

    /**
     * Main untraining action - releases units and rewards credits/citizens/turns
     */
    public function untrain(int $dominionId, string $unitSlug, int $quantity): array
    {
        $unit = Unit::where('slug', $unitSlug)->first();
        if (!$unit) {
            return ['success' => false, 'message' => "Invalid unit type: {$unitSlug}"];
        }

        return \Illuminate\Database\Capsule\Manager::transaction(function() use ($dominionId, $unit, $quantity) {
            $dominion = Dominion::lockForUpdate()->find($dominionId);
            if (!$dominion) throw new Exception('Sector not found.');

            $manpower = DominionManpower::where('dominion_id', $dominionId)
                ->where('unit_id', $unit->id)
                ->first();

            if (!$manpower || $manpower->total_quantity <= 0) {
                return ['success' => false, 'message' => "No {$unit->name} available for untraining."];
            }

            $actualQuantity = min($manpower->total_quantity, $quantity);

            // Check cooldown period
            $cooldownCheck = $this->canUntrainCitizens($dominionId);
            if (!$cooldownCheck['available_now']) {
                return [
                    'success' => false,
                    'message' => $cooldownCheck['message'],
                    'cool_down' => $cooldownCheck['cool_down'] ? ceil($cooldownCheck['cool_down'] / 60) : null,
                ];
            }

            // Calculate rewards
            $rewardCredits = $this->calculateUntrainingRewards($dominion, $actualQuantity);
            $rewardCitizens = (int)(15 * $actualQuantity * $this->holdFactor);
            $rewardTurns = (int)(8 * $actualQuantity * $this->holdFactor / 2.5);

            // Deduct units and apply rewards
            $manpower->decrement('total_quantity', $actualQuantity);
            
            $dominion->increment('credits', (int)$rewardCredits);
            $dominion->increment('citizens', $rewardCitizens);
            $dominion->increment('turns', $rewardTurns);
            $dominion->last_untrained = new DateTime();
            $dominion->save();

            return [
                'success' => true,
                'message' => "Untrained {$actualQuantity} {$unit->name}. Your kingdom's production has been boosted.",
                'rewards' => [
                    'credits' => $rewardCredits,
                    'citizens' => $rewardCitizens,
                    'turns' => $rewardTurns,
                ],
                'untrained_count' => $actualQuantity,
            ];
        });
    }

    private function calculateUntrainingRewards(Dominion $dominion, int $quantity): float
    {
        // Simple scaling reward
        $baseRewardPerUnit = 50 + ($quantity / 2);
        return (float)($baseRewardPerUnit * $quantity);
    }
}
