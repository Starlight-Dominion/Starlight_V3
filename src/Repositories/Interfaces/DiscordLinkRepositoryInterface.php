<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\DiscordAccountLink;
use sdo\Models\DiscordLinkChallenge;
use Illuminate\Support\Collection;

interface DiscordLinkRepositoryInterface
{
    // Challenges
    public function findChallengeByHash(string $hash): ?DiscordLinkChallenge;
    public function lockChallengeByHash(string $hash): ?DiscordLinkChallenge;
    public function invalidateActiveChallenges(int $userId): void;
    public function createChallenge(array $data): DiscordLinkChallenge;
    public function updateChallenge(int $id, array $data): bool;

    // Links
    public function findLinkByUser(int $userId): ?DiscordAccountLink;
    public function lockLinkByUser(int $userId): ?DiscordAccountLink;
    public function findLinkByDiscordUser(string $discordUserId): ?DiscordAccountLink;
    public function lockLinkByDiscordUser(string $discordUserId): ?DiscordAccountLink;
    public function createLink(array $data): DiscordAccountLink;
    public function updateLink(int $id, array $data): bool;
    public function deleteLink(int $id): bool;
}
