<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Repositories\Interfaces\AllianceRepositoryInterface;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class AllianceService
{
    public function __construct(
        private AllianceRepositoryInterface $allianceRepository,
        private UserRepositoryInterface $userRepository,
        private AdvisorService $advisorService,
        private TransactionManager $transactionManager
    ) {}

    public function getAllianceHubPayload(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("User not found.");

        if (!$user->alliance_id) {
            return [
                'in_alliance' => false,
                'pending_application' => $this->allianceRepository->findActiveApplication($userId),
                'pending_invitation' => $this->allianceRepository->getInvitations($userId)->first(),
                'advisor' => [
                    'advice' => "Unaligned Commander. To dominate the stars, you must either join an existing power structure or found your own."
                ]
            ];
        }

        $alliance = $this->allianceRepository->findById((int)$user->alliance_id);
        $members = $user->alliance->members()->with('allianceRole')->get();

        return [
            'in_alliance' => true,
            'alliance' => $alliance->toArray(),
            'my_role' => $user->allianceRole ? $user->allianceRole->toArray() : null,
            'members' => $members->toArray(),
            'is_leader' => ($user->id === $alliance->leader_id),
            'applications' => $this->allianceRepository->getApplications($alliance->id)->toArray(),
            'invitations' => [], // Need to implement if needed
            'advisor' => [
                'advice' => "Collective Command Hub. Monitor your alliance's status and coordinate with your fleet commanders."
            ]
        ];
    }

    public function getAlliancesList(string $search = ''): array
    {
        return $this->allianceRepository->getAll($search)->toArray();
    }

    public function getAllianceDetail(int $allianceId): array
    {
        $alliance = $this->allianceRepository->findById($allianceId);
        if (!$alliance) throw new Exception("Alliance not found.");
        return $alliance->toArray();
    }

    public function createAlliance(int $userId, array $data): void
    {
        $name = trim($data['name'] ?? '');
        $tag = trim($data['tag'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($name) || empty($tag)) throw new Exception("Name and tag are required.");
        if (strlen($tag) > 5) throw new Exception("Tag cannot exceed 5 characters.");

        $this->transactionManager->transaction(function () use ($userId, $name, $tag, $description) {
            $user = $this->userRepository->findById($userId);
            if (!$user) throw new Exception("User not found.");
            if ($user->alliance_id) throw new Exception("You are already in an alliance.");
            
            // Check credits via dominion
            $dominion = $user->dominion;
            if (!$dominion || $dominion->credits < 1000000) throw new Exception("Insufficient credits. Cost: 1,000,000 CR");

            if ($this->allianceRepository->findByTag($tag)) throw new Exception("Alliance tag already exists.");

            $alliance = $this->allianceRepository->create([
                'name' => $name,
                'tag' => $tag,
                'description' => $description,
                'leader_id' => $userId,
                'bank_credits' => 0,
                'war_prestige' => 0
            ]);

            // Create default roles
            $leaderRole = $this->allianceRepository->createRole($alliance->id, [
                'name' => 'Leader',
                'order' => 1,
                'can_invite' => true,
                'can_kick' => true,
                'can_manage_roles' => true,
                'can_moderate_forum' => true,
                'can_bank_withdraw' => true,
                'can_purchase_structures' => true
            ]);

            $this->allianceRepository->createRole($alliance->id, [
                'name' => 'Member',
                'order' => 10
            ]);

            // Update user and deduct credits
            $user->update(['alliance_id' => $alliance->id, 'alliance_role_id' => $leaderRole->id]);
            $dominion->update(['credits' => $dominion->credits - 1000000]);
        });
    }

    public function leaveAlliance(int $userId): void
    {
        $this->transactionManager->transaction(function () use ($userId) {
            $user = $this->userRepository->findById($userId);
            if (!$user || !$user->alliance_id) throw new Exception("You are not in an alliance.");

            $alliance = $this->allianceRepository->findById((int)$user->alliance_id);
            if ($alliance->leader_id === $userId) {
                throw new Exception("The leader cannot leave. You must disband or transfer leadership first.");
            }

            $user->update(['alliance_id' => null, 'alliance_role_id' => null]);
        });
    }

    public function applyToAlliance(int $userId, int $allianceId, string $message = ''): void
    {
        $this->transactionManager->transaction(function () use ($userId, $allianceId, $message) {
            $user = $this->userRepository->findById($userId);
            if ($user->alliance_id) throw new Exception("You are already in an alliance.");

            if ($this->allianceRepository->findActiveApplication($userId)) {
                throw new Exception("You already have a pending application.");
            }

            $this->allianceRepository->createApplication([
                'user_id' => $userId,
                'alliance_id' => $allianceId,
                'message' => $message,
                'status' => 'pending'
            ]);
        });
    }

    public function processApplication(int $adminUserId, int $applicationId, string $action): void
    {
        $this->transactionManager->transaction(function () use ($adminUserId, $applicationId, $action) {
            $admin = $this->userRepository->findById($adminUserId);
            $app = $this->allianceRepository->findApplicationById($applicationId);
            if (!$app || $app->status !== 'pending') throw new Exception("Application not found.");

            if ($admin->alliance_id !== $app->alliance_id || !$admin->allianceRole->can_invite) {
                throw new Exception("Unauthorized.");
            }

            if ($action === 'accept') {
                $app->update(['status' => 'accepted']);
                $user = $app->user;
                $defaultRole = $this->allianceRepository->getRoles($app->alliance_id)->where('name', 'Member')->first();
                $user->update(['alliance_id' => $app->alliance_id, 'alliance_role_id' => $defaultRole->id]);
            } else {
                $app->update(['status' => 'rejected']);
            }
        });
    }

    public function kickMember(int $adminUserId, int $targetUserId): void
    {
        $this->transactionManager->transaction(function () use ($adminUserId, $targetUserId) {
            $admin = $this->userRepository->findById($adminUserId);
            $target = $this->userRepository->findById($targetUserId);

            if (!$admin->alliance_id || $admin->alliance_id !== $target->alliance_id || !$admin->allianceRole->can_kick) {
                throw new Exception("Unauthorized.");
            }

            if ($target->id === $admin->alliance->leader_id) {
                throw new Exception("You cannot kick the leader.");
            }

            $target->update(['alliance_id' => null, 'alliance_role_id' => null]);
        });
    }
}
