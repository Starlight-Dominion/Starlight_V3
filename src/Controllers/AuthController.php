<?php
declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Infrastructure\Validator;
use sdo\Services\ConfigService;

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

    private function jsonResponse(bool $success, array $errors = [], int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['success' => $success, 'errors' => $errors]);
        exit;
    }

    public function showLogin(): string
    {
        return $this->render('auth/login', ['title' => 'Establish Link']);
    }

    public function showRegister(): string
    {
        return $this->render('auth/register', ['title' => 'Initialize Sector']);
    }

    public function login(): void
    {
        $validator = Validator::make($_POST, [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->jsonResponse(false, ['Invalid identity handle or cipher.'], 401);
        }

        $user = $this->authService->login($_POST['username'], $_POST['password']);

        if ($user) {
            // Check for Stasis Sanctions
            if ($user->stasis_until && $user->stasis_until > new \DateTime()) {
                $this->jsonResponse(false, ['Commander in stasis until ' . $user->stasis_until->format('Y-m-d H:i:s T')], 403);
            }

            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $this->jsonResponse(true, []);
        }

        $this->jsonResponse(false, ['Invalid identity handle or cipher.'], 401);
    }

    public function register(): void
    {
        $validator = Validator::make($_POST, [
            'username' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'dominion_name' => 'required|min:3',
            'race' => 'required'
        ]);

        if ($validator->fails()) {
            $errorMsgs = [];
            foreach ($validator->errors() as $fieldErrors) {
                $errorMsgs = array_merge($errorMsgs, $fieldErrors);
            }
            $this->jsonResponse(false, $errorMsgs, 400);
        }

        $result = $this->authService->register(
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['dominion_name'],
            $_POST['race']
        );

        if ($result['success']) {
            $this->jsonResponse(true, []);
        } else {
            // Pass the specific AuthService error string back directly
            $this->jsonResponse(false, [$result['message']], 400);
        }
    }

    public function logout(): void
    {
        $this->authService->logout();
        $this->redirect('/');
    }
}
