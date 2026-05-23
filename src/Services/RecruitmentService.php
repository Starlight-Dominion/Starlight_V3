<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\RecruitmentSession;
use sdo\Services\ConfigService;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use DateTime;

class RecruitmentService
{
    public function __construct(
        private ConfigService $configService,
        private LogService $logService
    ) {}

    /**
     * Get the current recruitment status for a dominion.
     */
    public function getStatus(int $domId): array
    {
        $activeSession = RecruitmentSession::where('dominion_id', $domId)
            ->where('is_active', true)
            ->first();

        $dailyLimit = (int)$this->configService->get('recruitment_sessions_per_day', 2);
        $threeDayLimit = (int)$this->configService->get('recruitment_sessions_per_3days', 5);

        $dailyCount = RecruitmentSession::where('dominion_id', $domId)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->count();

        $threeDayCount = RecruitmentSession::where('dominion_id', $domId)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-72 hours')))
            ->count();

        return [
            'active_session' => $activeSession,
            'daily_remaining' => max(0, $dailyLimit - $dailyCount),
            'three_day_remaining' => max(0, $threeDayLimit - $threeDayCount),
            'max_clicks' => (int)$this->configService->get('recruitment_clicks_per_session', 150)
        ];
    }

    /**
     * Start a new recruitment session if authorized.
     */
    public function startSession(int $domId): array
    {
        $status = $this->getStatus($domId);

        if ($status['active_session']) {
            return ['success' => true, 'session' => $status['active_session']];
        }

        if ($status['daily_remaining'] <= 0 || $status['three_day_remaining'] <= 0) {
            throw new Exception("Recruitment authorization denied. Frequency limit reached.");
        }

        $session = RecruitmentSession::create([
            'dominion_id' => $domId,
            'clicks_count' => 0,
            'is_active' => true
        ]);

        return [
            'success' => true,
            'session' => $session
        ];
    }

    /**
     * Process a single recruitment click.
     */
    public function processClick(int $domId, int $sessionId): array
    {
        return Capsule::transaction(function() use ($domId, $sessionId) {
            $session = RecruitmentSession::where('id', $sessionId)
                ->where('dominion_id', $domId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                throw new Exception("Recruitment session invalid or expired.");
            }

            $maxClicks = (int)$this->configService->get('recruitment_clicks_per_session', 150);
            
            if ($session->clicks_count >= $maxClicks) {
                $session->update(['is_active' => false, 'completed_at' => date('Y-m-d H:i:s')]);
                return ['success' => false, 'message' => "Mobilization complete."];
            }

            // 1. Increment progress
            $session->increment('clicks_count');
            $newCount = $session->clicks_count;

            // 2. Grant Citizen
            $dom = Dominion::lockForUpdate()->find($domId);
            $dom->increment('citizens');

            // 3. Finalize if reached max
            if ($newCount >= $maxClicks) {
                $session->update(['is_active' => false, 'completed_at' => date('Y-m-d H:i:s')]);
                
                $this->logService->log(
                    $domId,
                    'recruitment_complete',
                    "Commander completed a mobilization session, enlisting {$maxClicks} civilians.",
                    $maxClicks
                );
            }

            return [
                'success' => true,
                'count' => $newCount,
                'max' => $maxClicks
            ];
        });
    }
}
