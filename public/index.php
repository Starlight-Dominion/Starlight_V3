<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;
use sdo\Infrastructure\Csrf;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Error reporting
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Initialize Dependency Injection Container
$container = require __DIR__ . '/../config/container.php';

// Session management
$sessionHandler = new \sdo\Infrastructure\RedisSessionHandler($container->get(\Predis\Client::class));
session_set_save_handler($sessionHandler, true);
session_start();

// Initialize Router
$dispatcher = simpleDispatcher(require __DIR__ . '/../config/routes.php');

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo "404 Not Found";
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo "405 Method Not Allowed";
        break;
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        
        if ($httpMethod === 'POST') {
            $csrfToken = $_POST['_csrf'] ?? '';
            if (!Csrf::verifyToken($csrfToken)) {
                http_response_code(403);
                echo "403 Forbidden: Invalid CSRF token.";
                break;
            }
        }

        [$controllerClass, $method] = $handler;
        $controller = $container->get($controllerClass);
        echo $controller->$method($vars);
        break;
}