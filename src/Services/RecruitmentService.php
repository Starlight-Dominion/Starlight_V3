<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\RecruitmentSession;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\RecruitmentRepositoryInterface;
use sdo\Repositories\Interfaces\RecruitmentLogRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\ConfigService;
use Exception;
use DateTime;

class RecruitmentService
{
    public function __construct(
        private ConfigService $configService,
        private DominionRepositoryInterface $dominionRepository,
        private RecruitmentRepositoryInterface $recruitmentRepository,
        private RecruitmentLogRepositoryInterface $recruitmentLogRepository,
        private TransactionManager $transactionManager
    ) {}

    /**
     * Get the current recruitment status for a dominion.
     */
    public function getStatus(int $domId): array
    {
        $activeSession = $this->recruitmentRepository->findActiveSession($domId);

        $dailyLimit = (int)$this->configService->get('recruitment_sessions_per_day', 2);
        $threeDayLimit = (int)$this->configService->get('recruitment_sessions_per_3days', 5);

        $dailyCount = $this->recruitmentRepository->countRecentSessions($domId, 24);
        $threeDayCount = $this->recruitmentRepository->countRecentSessions($domId, 72);

        return [
            'active_session' => $activeSession,
            'daily_remaining' => max(0, $dailyLimit - $dailyCount),
            'three_day_remaining' => max(0, $threeDayLimit - $threeDayCount),
            'max_clicks' => (int)$this->configService->get('recruitment_clicks_per_session', 150),
            'cooldown_ms' => (int)$this->configService->get('recruitment_click_cooldown_ms', 500),
            'total_recruited' => $this->recruitmentRepository->getTotalCitizensRecruited($domId),
            'today_recruited' => $this->recruitmentRepository->getTodayCitizensRecruited($domId)
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

        $session = $this->recruitmentRepository->createSession([
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
        return $this->transactionManager->transaction(function() use ($domId, $sessionId) {
            $session = $this->recruitmentRepository->lockActiveSession($sessionId, $domId);

            if (!$session) {
                throw new Exception("Recruitment session invalid or expired.");
            }

            $maxClicks = (int)$this->configService->get('recruitment_clicks_per_session', 150);
            
            if ($session->clicks_count >= $maxClicks) {
                $this->recruitmentRepository->updateSession($sessionId, [
                    'is_active' => false,
                    'completed_at' => date('Y-m-d H:i:s')
                ]);
                return ['success' => false, 'message' => "Mobilization complete."];
            }

            // 1. Increment progress
            $newCount = $this->recruitmentRepository->incrementClicks($sessionId);

            // 2. Grant Citizen
            $this->dominionRepository->incrementStats($domId, ['citizens' => 1]);

            // 3. Log click
            $this->recruitmentLogRepository->log([
                'dominion_id' => $domId,
                'action' => 'recruitment_click',
                'description' => "Commander processed a neural recruitment click.",
                'amount' => 1
            ]);

            // 4. Finalize if reached max
            if ($newCount >= $maxClicks) {
                $this->recruitmentRepository->updateSession($sessionId, [
                    'is_active' => false,
                    'completed_at' => date('Y-m-d H:i:s')
                ]);
                
                $this->recruitmentLogRepository->log([
                    'dominion_id' => $domId,
                    'action' => 'recruitment_complete',
                    'description' => "Commander completed a mobilization session, enlisting {$maxClicks} civilians.",
                    'amount' => $maxClicks
                ]);
            }

            return [
                'success' => true,
                'count' => $newCount,
                'max' => $maxClicks
            ];
        });
    }
}
