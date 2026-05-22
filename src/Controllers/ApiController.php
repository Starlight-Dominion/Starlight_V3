<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\ConfigService;
use sdo\Services\AuthService;

class ApiController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    /**
     * Endpoint: /api/v1/ping
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
}
