<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\RecruitmentLog;
use sdo\Repositories\Interfaces\RecruitmentLogRepositoryInterface;

class EloquentRecruitmentLogRepository implements RecruitmentLogRepositoryInterface
{
    public function log(array $data): void
    {
        RecruitmentLog::create($data);
    }

    public function getPaginatedLogs(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $query = RecruitmentLog::with('dominion');

        if (!empty($filters['dominion_id'])) {
            $query->where('dominion_id', (int)$filters['dominion_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', 'LIKE', '%' . $filters['action'] . '%');
        }

        $total = $query->count();

        $logs = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => $logs->map(fn($log) => [
                'id' => $log->id,
                'dominion_id' => $log->dominion_id,
                'dominion_name' => $log->dominion ? $log->dominion->name : 'Unknown',
                'action' => $log->action,
                'description' => $log->description,
                'amount' => $log->amount,
                'created_at' => $log->created_at->format('Y-m-d H:i:s')
            ])->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / $perPage)
        ];
    }
}
