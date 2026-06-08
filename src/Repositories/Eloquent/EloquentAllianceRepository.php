<?php
declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\Alliance;
use sdo\Models\AllianceRole;
use sdo\Models\AllianceApplication;
use sdo\Models\AllianceInvitation;
use sdo\Models\AllianceStructure;
use sdo\Models\AllianceBankLog;
use sdo\Repositories\Interfaces\AllianceRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentAllianceRepository implements AllianceRepositoryInterface
{
    public function findById(int $id): ?Alliance
    {
        return Alliance::find($id);
    }

    public function findByTag(string $tag): ?Alliance
    {
        return Alliance::where('tag', $tag)->first();
    }

    public function getAll(string $search = ''): Collection
    {
        $query = Alliance::query();
        if ($search !== '') {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('tag', 'LIKE', "%{$search}%");
        }
        return $query->withCount('members')->get();
    }

    public function create(array $data): Alliance
    {
        return Alliance::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $alliance = $this->findById($id);
        return $alliance ? $alliance->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $alliance = $this->findById($id);
        return $alliance ? $alliance->delete() : false;
    }

    public function getRoles(int $allianceId): Collection
    {
        return AllianceRole::where('alliance_id', $allianceId)->orderBy('order', 'ASC')->get();
    }

    public function findRoleById(int $roleId): ?AllianceRole
    {
        return AllianceRole::find($roleId);
    }

    public function createRole(int $allianceId, array $data): AllianceRole
    {
        $data['alliance_id'] = $allianceId;
        return AllianceRole::create($data);
    }

    public function getApplications(int $allianceId): Collection
    {
        return AllianceApplication::where('alliance_id', $allianceId)->with('user')->get();
    }

    public function findApplicationById(int $id): ?AllianceApplication
    {
        return AllianceApplication::find($id);
    }

    public function findActiveApplication(int $userId): ?AllianceApplication
    {
        return AllianceApplication::where('user_id', $userId)->where('status', 'pending')->first();
    }

    public function createApplication(array $data): AllianceApplication
    {
        return AllianceApplication::create($data);
    }

    public function getInvitations(int $userId): Collection
    {
        return AllianceInvitation::where('user_id', $userId)->where('status', 'pending')->with('alliance')->get();
    }

    public function findInvitationById(int $id): ?AllianceInvitation
    {
        return AllianceInvitation::find($id);
    }

    public function createInvitation(array $data): AllianceInvitation
    {
        return AllianceInvitation::create($data);
    }

    public function getStructures(int $allianceId): Collection
    {
        return AllianceStructure::where('alliance_id', $allianceId)->get();
    }

    public function findStructure(int $allianceId, string $key): ?AllianceStructure
    {
        return AllianceStructure::where('alliance_id', $allianceId)->where('structure_key', $key)->first();
    }

    public function createStructure(array $data): AllianceStructure
    {
        return AllianceStructure::create($data);
    }

    public function logBankAction(array $data): AllianceBankLog
    {
        return AllianceBankLog::create($data);
    }

    public function getBankLogs(int $allianceId, int $limit = 50): Collection
    {
        return AllianceBankLog::where('alliance_id', $allianceId)->with('user')->orderBy('created_at', 'DESC')->limit($limit)->get();
    }
}
