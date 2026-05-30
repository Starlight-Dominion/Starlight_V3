<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\DiscordAccountLink;
use sdo\Models\DiscordLinkChallenge;
use sdo\Repositories\Interfaces\DiscordLinkRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDiscordLinkRepository implements DiscordLinkRepositoryInterface
{
    public function findChallengeByHash(string $hash): ?DiscordLinkChallenge
    {
        return DiscordLinkChallenge::where('code_hash', $hash)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', gmdate('Y-m-d H:i:s'))
            ->first();
    }

    public function lockChallengeByHash(string $hash): ?DiscordLinkChallenge
    {
        return DiscordLinkChallenge::where('code_hash', $hash)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', gmdate('Y-m-d H:i:s'))
            ->lockForUpdate()
            ->first();
    }

    public function invalidateActiveChallenges(int $userId): void
    {
        DiscordLinkChallenge::where('user_id', $userId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => gmdate('Y-m-d H:i:s')]);
    }

    public function createChallenge(array $data): DiscordLinkChallenge
    {
        return DiscordLinkChallenge::create($data);
    }

    public function updateChallenge(int $id, array $data): bool
    {
        $challenge = DiscordLinkChallenge::find($id);
        return $challenge ? $challenge->update($data) : false;
    }

    public function findLinkByUser(int $userId): ?DiscordAccountLink
    {
        return DiscordAccountLink::where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    public function lockLinkByUser(int $userId): ?DiscordAccountLink
    {
        return DiscordAccountLink::where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function findLinkByDiscordUser(string $discordUserId): ?DiscordAccountLink
    {
        return DiscordAccountLink::where('discord_user_id', $discordUserId)
            ->where('is_active', true)
            ->first();
    }

    public function lockLinkByDiscordUser(string $discordUserId): ?DiscordAccountLink
    {
        return DiscordAccountLink::where('discord_user_id', $discordUserId)
            ->lockForUpdate()
            ->first();
    }

    public function createLink(array $data): DiscordAccountLink
    {
        return DiscordAccountLink::create($data);
    }

    public function updateLink(int $id, array $data): bool
    {
        $link = DiscordAccountLink::find($id);
        return $link ? $link->update($data) : false;
    }

    public function deleteLink(int $id): bool
    {
        return DiscordAccountLink::where('id', $id)->delete() > 0;
    }
}
