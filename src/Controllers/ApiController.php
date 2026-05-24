<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;
use sdo\Services\BattlefieldService;
use sdo\Services\DiscordLinkService;
use sdo\Services\FoundationService;
use sdo\Models\DominionManpower;
use sdo\Models\DominionStructure;

class ApiController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private BattlefieldService $battlefieldService,
        private FoundationService $foundationService,
        private DiscordLinkService $discordLinkService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    /**
     * Helper to get dominion from API vars.
     */
    private function getDominion(array $vars): ?\sdo\Models\Dominion
    {
        $apiKey = $vars['_api_key'] ?? null;
        if (!$apiKey) return null;

        return $this->gameService->getDominionByUserId((int)$apiKey->user_id);
    }

    /**
     * GET /api/v1/ping
     */
    public function ping(): string
    {
        header('Content-Type: application/json');
        return json_encode([
            'success' => true,
            'message' => 'Neural link active.',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => 'v1.0.0'
        ]);
    }

    /**
     * GET /api/v1/sector/status
     */
    public function sectorStatus(array $vars): string
    {
        header('Content-Type: application/json');
        $dom = $this->getDominion($vars);
        if (!$dom) return json_encode(['success' => false, 'message' => 'Sector not found.']);

        return json_encode([
            'success' => true,
            'data' => [
                'name' => $dom->name,
                'credits' => $dom->credits,
                'banked' => $dom->credits_banked,
                'citizens' => $dom->citizens,
                'turns' => $dom->turns,
                'xp' => $dom->xp,
                'level' => $dom->getPlayerLevel(),
                'integrity' => [
                    'current' => $dom->foundation_hp,
                    'max' => $dom->foundation_max_hp
                ],
                'last_tick' => $dom->last_tick
            ]
        ]);
    }

    /**
     * GET /api/v1/sector/manpower
     */
    public function sectorManpower(array $vars): string
    {
        header('Content-Type: application/json');
        $dom = $this->getDominion($vars);
        if (!$dom) return json_encode(['success' => false, 'message' => 'Sector not found.']);

        $units = DominionManpower::with('unit')
            ->where('dominion_id', $dom->id)
            ->get()
            ->map(fn($m) => [
                'slug' => $m->unit->slug,
                'name' => $m->unit->name,
                'quantity' => (int)$m->total_quantity
            ]);

        return json_encode([
            'success' => true,
            'data' => $units
        ]);
    }

    /**
     * GET /api/v1/sector/structures
     */
    public function sectorStructures(array $vars): string
    {
        header('Content-Type: application/json');
        $dom = $this->getDominion($vars);
        if (!$dom) return json_encode(['success' => false, 'message' => 'Sector not found.']);

        $structures = DominionStructure::with(['structure', 'levelData'])
            ->where('dominion_id', $dom->id)
            ->get()
            ->map(fn($s) => [
                'slug' => $s->structure->slug,
                'name' => $s->structure->name,
                'current_level' => (int)$s->level,
                'buffs' => [
                    'economy' => (int)($s->levelData->buff_economy ?? 0),
                    'offense' => (int)($s->levelData->buff_offense ?? 0),
                    'defense' => (int)($s->levelData->buff_defense ?? 0)
                ]
            ]);

        return json_encode([
            'success' => true,
            'data' => $structures
        ]);
    }

    /**
     * GET /api/v1/battlefield
     */
    public function battlefield(): string
    {
        header('Content-Type: application/json');
        // Battlefield list is public tactical data within the sector
        return json_encode([
            'success' => true,
            'data' => $this->battlefieldService->getBattlefieldList()
        ]);
    }

    /**
     * GET /api/v1/discord/link-status
     */
    public function discordLinkStatus(): string
    {
        header('Content-Type: application/json');

        $discordUserID = trim((string)($_GET['discord_user_id'] ?? ''));
        if ($discordUserID === '') {
            http_response_code(400);
            return json_encode([
                'success' => false,
                'message' => 'Missing discord_user_id parameter.',
            ]);
        }

        $link = $this->discordLinkService->getActiveLinkForDiscordUser($discordUserID);
        return json_encode([
            'success' => true,
            'data' => [
                'linked' => (bool)$link,
                'sdo_user_id' => $link ? (string)$link->user_id : null,
                'linked_at' => $link?->linked_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
