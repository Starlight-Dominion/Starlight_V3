<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\Services\AuthService;
use sdo\Infrastructure\Validator;

class AuthController extends BaseController
{
public function __construct(
GameService $gameService,
AdvisorService $advisorService,
private AuthService $authService
) {
parent::__construct($gameService, $advisorService);
}

public function showLogin(): string
{
return $this->render('auth/login', ['title' => 'Login']);
}

public function login(): void
{
$validator = Validator::make($_POST, [
'username' => 'required',
'password' => 'required',
]);

if ($validator->fails()) {
$this->redirectBack($validator->errors());
}

$user = $this->authService->login($_POST['username'], $_POST['password']);

if ($user) {
$_SESSION['user_id'] = $user->id;
$_SESSION['username'] = $user->username;
$this->redirect('/dashboard');
}

$this->redirectBack(['Invalid credentials.']);
}

public function showRegister(): string
{
return $this->render('auth/register', ['title' => 'Register']);
}

public function register(): void
{
$validator = Validator::make($_POST, [
'username' => 'required|min:3|alpha_num',
'email' => 'required|email',
'password' => 'required|min:6|confirmed',
'kingdom_name' => 'required|min:3',
]);

if ($validator->fails()) {
$this->redirectBack($validator->errors(), $_POST);
}

$success = $this->authService->register(
$_POST['username'],
$_POST['email'],
$_POST['password'],
$_POST['kingdom_name']
);

if ($success) {
$this->redirect('/login?success=1');
}

$this->redirectBack(['Registration failed. User or Kingdom name may be taken.']);
}

public function logout(): void
{
$this->authService->logout();
$this->redirect('/');
}
}