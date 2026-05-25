<?php
declare(strict_types=1);

namespace sdo\Services;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Models\DiscordAccountLink;
use sdo\Models\DiscordLinkChallenge;
use sdo\Models\User;

class DiscordLinkService
{
    public function __construct()
    {
    }

    public function createChallengeForUser(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $code = $this->generateLinkCode();
        $codeHash = $this->hashCode($code);
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->add(new DateInterval('PT10M'));

        Capsule::transaction(function () use ($userId, $codeHash, $expiresAt): void {
            DiscordLinkChallenge::where('user_id', $userId)
                ->whereNull('consumed_at')
            ->update(['consumed_at' => gmdate('Y-m-d H:i:s')]);

            DiscordLinkChallenge::create([
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
        return DiscordAccountLink::where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    public function getActiveLinkForDiscordUser(string $discordUserID): ?DiscordAccountLink
    {
        $discordUserID = trim($discordUserID);
        if ($discordUserID === '') {
            return null;
        }

        return DiscordAccountLink::where('discord_user_id', $discordUserID)
            ->where('is_active', true)
            ->first();
    }

    private function handleLinkRequested(array $envelope, array $payload): array
    {
        $discordUserID = trim((string)($payload['discord_user_id'] ?? $envelope['discord_user_id'] ?? ''));
        $linkCode = trim((string)($payload['link_code'] ?? ''));

        if ($discordUserID === '' || $linkCode === '') {
            return $this->rejectionEnvelope($envelope, 'missing_fields', 'Missing discord user id or link code.');
        }

        return Capsule::transaction(function () use ($envelope, $discordUserID, $linkCode): array {
            $challenge = DiscordLinkChallenge::where('code_hash', $this->hashCode($linkCode))
                ->whereNull('consumed_at')
                ->where('expires_at', '>', gmdate('Y-m-d H:i:s'))
                ->lockForUpdate()
                ->first();

            if (!$challenge) {
                return $this->rejectionEnvelope($envelope, 'invalid_or_expired_code', 'That link code is invalid or expired.');
            }

            $userID = (int)$challenge->user_id;

            $existingDiscord = DiscordAccountLink::where('discord_user_id', $discordUserID)
                ->lockForUpdate()
                ->first();
            if ($existingDiscord && (bool)$existingDiscord->is_active && (int)$existingDiscord->user_id !== $userID) {
                return $this->rejectionEnvelope($envelope, 'discord_already_linked', 'This Discord account is already linked to another commander.');
            }

            $existingUser = DiscordAccountLink::where('user_id', $userID)
                ->lockForUpdate()
                ->first();
            if ($existingUser && (bool)$existingUser->is_active && $existingUser->discord_user_id !== $discordUserID) {
                return $this->rejectionEnvelope($envelope, 'sdo_user_already_linked', 'This commander is already linked to a different Discord account.');
            }

            $challenge->consumed_at = gmdate('Y-m-d H:i:s');
            $challenge->save();

            if ($existingUser && $existingDiscord && (int)$existingUser->id !== (int)$existingDiscord->id) {
                // Keep the row keyed by user_id and drop stale discord-only history.
                $existingDiscord->delete();
            }

            $link = $existingUser ?? $existingDiscord;
            if ($link) {
                $link->user_id = $userID;
                $link->discord_user_id = $discordUserID;
                $link->linked_at = gmdate('Y-m-d H:i:s');
                $link->unlinked_at = null;
                $link->is_active = true;
                $link->save();
            } else {
                $link = DiscordAccountLink::create([
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

        return Capsule::transaction(function () use ($envelope, $discordUserID): array {
            $link = DiscordAccountLink::where('discord_user_id', $discordUserID)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$link) {
                return $this->rejectionEnvelope($envelope, 'not_linked', 'No active account link exists for this Discord user.');
            }

            $link->is_active = false;
            $link->unlinked_at = gmdate('Y-m-d H:i:s');
            $link->save();

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

        $link = DiscordAccountLink::where('discord_user_id', $discordUserID)
            ->where('is_active', true)
            ->first();

        if (!$link) {
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
