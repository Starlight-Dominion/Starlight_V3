<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;
use sdo\Services\BattlefieldService;
use sdo\Services\FoundationService;
use Illuminate\Database\Capsule\Manager as Capsule;

class ApiController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService,
        private BattlefieldService $battlefieldService,
        private FoundationService $foundationService
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

        return $this->gameService->getKingdomByUserId((int)$apiKey->user_id);
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

        $units = Capsule::table('dominion_manpower')
            ->join('units', 'dominion_manpower.unit_id', '=', 'units.id')
            ->where('dominion_id', $dom->id)
            ->select('units.name', 'units.slug', 'dominion_manpower.total_quantity')
            ->get();

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

        $state = $this->foundationService->getFoundationState($dom->id);
        
        $structures = [];
        foreach ($state['structures'] as $slug => $data) {
            $structures[] = [
                'slug' => $slug,
                'name' => $data['name'],
                'current_level' => $data['current_level'],
                'max_level' => $data['max_level']
            ];
        }

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
}
