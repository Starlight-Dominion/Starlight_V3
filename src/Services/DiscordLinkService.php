<?php
declare(strict_types=1);

namespace sdo\Services;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DiscordLinkRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Models\DiscordAccountLink;

class DiscordLinkService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DiscordLinkRepositoryInterface $discordLinkRepository,
        private TransactionManager $transactionManager
    ) {}

    public function createChallengeForUser(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $code = $this->generateLinkCode();
        $codeHash = $this->hashCode($code);
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->add(new DateInterval('PT10M'));

        $this->transactionManager->transaction(function () use ($userId, $codeHash, $expiresAt): void {
            $this->discordLinkRepository->invalidateActiveChallenges($userId);

            $this->discordLinkRepository->createChallenge([
                'user_id' => $userId,
                'code_hash' => $codeHash,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
        });

        return [
            'success' => true,
            'message' => 'Discord link code generated.',
            'link_code' => $code,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ];
    }

    public function processActionEnvelope(array $envelope): ?array
    {
        if (($envelope['type'] ?? '') !== 'action') {
            return null;
        }

        $payload = $envelope['payload'] ?? [];
        if (!is_array($payload)) {
            return $this->rejectionEnvelope($envelope, 'invalid_payload', 'Action payload is invalid.');
        }

        $actionName = (string)($payload['action_name'] ?? '');
        return match ($actionName) {
            'account_link.requested' => $this->handleLinkRequested($envelope, $payload),
            'account_link.removal_requested' => $this->handleUnlinkRequested($envelope, $payload),
            'account_link.status_requested' => $this->handleLinkStatusRequested($envelope, $payload),
            default => $this->rejectionEnvelope($envelope, 'unsupported_action', 'Unsupported Discord action request.'),
        };
    }

    public function getActiveLinkForUser(int $userId): ?DiscordAccountLink
    {
        return $this->discordLinkRepository->findLinkByUser($userId);
    }

    public function getActiveLinkForDiscordUser(string $discordUserID): ?DiscordAccountLink
    {
        $discordUserID = trim($discordUserID);
        if ($discordUserID === '') {
            return null;
        }

        return $this->discordLinkRepository->findLinkByDiscordUser($discordUserID);
    }

    private function handleLinkRequested(array $envelope, array $payload): array
    {
        $discordUserID = trim((string)($payload['discord_user_id'] ?? $envelope['discord_user_id'] ?? ''));
        $linkCode = trim((string)($payload['link_code'] ?? ''));

        if ($discordUserID === '' || $linkCode === '') {
            return $this->rejectionEnvelope($envelope, 'missing_fields', 'Missing discord user id or link code.');
        }

        return $this->transactionManager->transaction(function () use ($envelope, $discordUserID, $linkCode): array {
            $challenge = $this->discordLinkRepository->lockChallengeByHash($this->hashCode($linkCode));

            if (!$challenge) {
                return $this->rejectionEnvelope($envelope, 'invalid_or_expired_code', 'That link code is invalid or expired.');
            }

            $userID = (int)$challenge->user_id;

            $existingDiscord = $this->discordLinkRepository->lockLinkByDiscordUser($discordUserID);
            if ($existingDiscord && (bool)$existingDiscord->is_active && (int)$existingDiscord->user_id !== $userID) {
                return $this->rejectionEnvelope($envelope, 'discord_already_linked', 'This Discord account is already linked to another commander.');
            }

            $existingUser = $this->discordLinkRepository->lockLinkByUser($userID);
            if ($existingUser && (bool)$existingUser->is_active && $existingUser->discord_user_id !== $discordUserID) {
                return $this->rejectionEnvelope($envelope, 'sdo_user_already_linked', 'This commander is already linked to a different Discord account.');
            }

            $this->discordLinkRepository->updateChallenge((int)$challenge->id, [
                'consumed_at' => gmdate('Y-m-d H:i:s')
            ]);

            if ($existingUser && $existingDiscord && (int)$existingUser->id !== (int)$existingDiscord->id) {
                $this->discordLinkRepository->deleteLink((int)$existingDiscord->id);
            }

            $link = $existingUser ?? $existingDiscord;
            if ($link) {
                $this->discordLinkRepository->updateLink((int)$link->id, [
                    'user_id' => $userID,
                    'discord_user_id' => $discordUserID,
                    'linked_at' => gmdate('Y-m-d H:i:s'),
                    'unlinked_at' => null,
                    'is_active' => true,
                ]);
            } else {
                $link = $this->discordLinkRepository->createLink([
                    'user_id' => $userID,
                    'discord_user_id' => $discordUserID,
                    'is_active' => true,
                    'linked_at' => gmdate('Y-m-d H:i:s'),
                    'unlinked_at' => null,
                ]);
            }

            return $this->resultEnvelope(
                $envelope,
                'account_link.completed',
                'Your Discord account is now linked.',
                [
                    'linked' => true,
                    'sdo_user_id' => (string)$link->user_id,
                ]
            );
        });
    }

    private function handleUnlinkRequested(array $envelope, array $payload): array
    {
        $discordUserID = trim((string)($payload['discord_user_id'] ?? $envelope['discord_user_id'] ?? ''));
        if ($discordUserID === '') {
            return $this->rejectionEnvelope($envelope, 'missing_discord_user_id', 'Missing discord user id.');
        }

        return $this->transactionManager->transaction(function () use ($envelope, $discordUserID): array {
            $link = $this->discordLinkRepository->lockLinkByDiscordUser($discordUserID);

            if (!$link || !$link->is_active) {
                return $this->rejectionEnvelope($envelope, 'not_linked', 'No active account link exists for this Discord user.');
            }

            $this->discordLinkRepository->updateLink((int)$link->id, [
                'is_active' => false,
                'unlinked_at' => gmdate('Y-m-d H:i:s')
            ]);

            return $this->resultEnvelope(
                $envelope,
                'account_link.removal_completed',
                'Your Discord account link has been removed.',
                [
                    'linked' => false,
                ]
            );
        });
    }

    private function handleLinkStatusRequested(array $envelope, array $payload): array
    {
        $discordUserID = trim((string)($payload['discord_user_id'] ?? $envelope['discord_user_id'] ?? ''));
        if ($discordUserID === '') {
            return $this->rejectionEnvelope($envelope, 'missing_discord_user_id', 'Missing discord user id.');
        }

        $link = $this->discordLinkRepository->findLinkByDiscordUser($discordUserID);

        if (!$link || !$link->is_active) {
            return $this->resultEnvelope(
                $envelope,
                'account_link.status_reported',
                'No active account link was found for your Discord account.',
                [
                    'linked' => false,
                ]
            );
        }

        return $this->resultEnvelope(
            $envelope,
            'account_link.status_reported',
            'Your Discord account is linked.',
            [
                'linked' => true,
                'sdo_user_id' => (string)$link->user_id,
            ]
        );
    }

    private function rejectionEnvelope(array $requestEnvelope, string $reason, string $message): array
    {
        $requestPayload = $requestEnvelope['payload'] ?? [];
        $actionName = is_array($requestPayload) ? (string)($requestPayload['action_name'] ?? '') : '';

        return $this->resultEnvelope($requestEnvelope, $this->rejectedActionName($actionName), $message, [
            'reason' => $reason,
            'linked' => false,
        ]);
    }

    private function rejectedActionName(string $actionName): string
    {
        return match ($actionName) {
            'account_link.removal_requested' => 'account_link.removal_rejected',
            default => 'account_link.rejected',
        };
    }

    private function resultEnvelope(array $requestEnvelope, string $actionName, string $message, array $extraPayload): array
    {
        $discordUserID = trim((string)($requestEnvelope['payload']['discord_user_id'] ?? $requestEnvelope['discord_user_id'] ?? ''));
        $payload = array_merge([
            'action_name' => $actionName,
            'message' => $message,
        ], $extraPayload);

        return [
            'type' => 'action',
            'source' => 'sdo',
            'schema_version' => 1,
            'correlation_id' => (string)($requestEnvelope['correlation_id'] ?? ('sdo-action-' . time())),
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'payload' => $payload,
            'destination_kind' => 'discord',
            'discord_user_id' => $discordUserID,
            'metadata' => [
                'origin' => 'sdo.discord_link_service',
            ],
        ];
    }

    private function generateLinkCode(): string
    {
        return 'SDO-' . strtoupper(bin2hex(random_bytes(8)));
    }

    private function hashCode(string $code): string
    {
        return hash('sha256', strtoupper(trim($code)));
    }
}
