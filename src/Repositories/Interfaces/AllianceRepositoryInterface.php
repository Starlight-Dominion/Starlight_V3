<?php
declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\Alliance;
use sdo\Models\AllianceRole;
use sdo\Models\AllianceApplication;
use sdo\Models\AllianceInvitation;
use Illuminate\Support\Collection;

interface AllianceRepositoryInterface
{
    public function findById(int $id): ?Alliance;
    public function findByTag(string $tag): ?Alliance;
    public function getAll(string $search = ''): Collection;
    public function create(array $data): Alliance;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;

    // Roles
    public function getRoles(int $allianceId): Collection;
    public function findRoleById(int $roleId): ?AllianceRole;
    public function createRole(int $allianceId, array $data): AllianceRole;

    // Applications & Invitations
    public function getApplications(int $allianceId): Collection;
    public function findApplicationById(int $id): ?AllianceApplication;
    public function findActiveApplication(int $userId): ?AllianceApplication;
    public function createApplication(array $data): AllianceApplication;
    
    public function getInvitations(int $userId): Collection;
    public function findInvitationById(int $id): ?AllianceInvitation;
    public function createInvitation(array $data): AllianceInvitation;

    // Structures & Bank
    public function getStructures(int $allianceId): Collection;
    public function findStructure(int $allianceId, string $key): ?\sdo\Models\AllianceStructure;
    public function createStructure(array $data): \sdo\Models\AllianceStructure;
    public function logBankAction(array $data): \sdo\Models\AllianceBankLog;
    public function getBankLogs(int $allianceId, int $limit = 50): Collection;
}
