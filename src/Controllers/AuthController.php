<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Services\ConfigService;
use sdo\Dto\Auth\LoginRequest;
use sdo\Dto\Auth\RegisterRequest;

class AuthController extends BaseController
{
    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService,
        ConfigService $configService,
        AuthService $authService
    ) {
        parent::__construct($gameService, $advisorService, $configService, $authService);
    }

    public function showLogin(): string
    {
        return $this->render('auth/login', ['title' => 'Establish Link']);
    }

    public function showRegister(): string
    {
        return $this->render('auth/register', ['title' => 'Initialize Sector']);
    }

    public function login(): string
    {
        return $this->jsonResponse(function() {
            $request = new LoginRequest($_POST);
            $user = $this->authService->login($request->username, $request->password);

            if ($user) {
                // Check for Stasis Sanctions
                if ($user->stasis_until && $user->stasis_until > new \DateTime()) {
                    throw new \Exception('Commander in stasis until ' . $user->stasis_until->format('Y-m-d H:i:s T'));
                }

                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                
                // Trigger advice recording if session started
                $dominion = $this->gameService->getDominionByUserId($user->id);
                if ($dominion) {
                    $advice = $this->advisorService->getContextualAdviceFromDominion($dominion);
                    $_SESSION['advisor_history'] = $this->advisorService->formatAdviceLog($advice, $_SESSION['advisor_history'] ?? []);
                }

                return ['success' => true];
            }

            throw new \Exception('Invalid identity handle or cipher.');
        });
    }

    public function register(): string
    {
        return $this->jsonResponse(function() {
            $request = new RegisterRequest($_POST);
            
            $result = $this->authService->register(
                $request->username,
                $request->email,
                $request->password,
                $request->dominion_name,
                $request->race
            );

            if ($result['success']) {
                return ['success' => true];
            }

            throw new \Exception($result['message']);
        });
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy(); 
        }
        $this->redirect('/');
    }
}
