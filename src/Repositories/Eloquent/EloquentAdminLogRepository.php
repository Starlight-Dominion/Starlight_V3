<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\AdminLog;
use sdo\Repositories\Interfaces\AdminLogRepositoryInterface;

class EloquentAdminLogRepository implements AdminLogRepositoryInterface
{
    public function log(array $data): void
    {
        AdminLog::create($data);
    }

    public function getPaginatedLogs(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $query = AdminLog::with('admin');

        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', (int)$filters['admin_id']);
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
                'admin_id' => $log->admin_id,
                'admin_username' => $log->admin ? $log->admin->username : 'Unknown',
                'action' => $log->action,
                'description' => $log->description,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at->format('Y-m-d H:i:s')
            ])->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / $perPage)
        ];
    }
}
