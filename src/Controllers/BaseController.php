<?php

declare(strict_types=1);

namespace sdo\Controllers;

use sdo\Infrastructure\Csrf;
use sdo\Services\GameService;
use sdo\Services\AdvisorService;
use sdo\ViewModels\GameStateViewModel;

abstract class BaseController
{
    protected GameService $gameService;
    protected AdvisorService $advisorService;

    public function __construct(
        GameService $gameService,
        AdvisorService $advisorService
    ) {
        $this->gameService = $gameService;
        $this->advisorService = $advisorService;
    }

    /**
     * Renders the Svelte Shell with refined hydration logic.
     */
    protected function render(string $component, array $pageData = []): string
    {
        $userData = null;

        if (isset($_SESSION['user_id'])) {
            $kingdom = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
            if ($kingdom) {
                $advice = $this->advisorService->getContextualAdviceFromKingdom($kingdom->toArray());
                $vm = new GameStateViewModel(
                    $kingdom,
                    $this->gameService,
                    $advice,
                    $_SESSION['username'] ?? 'Unknown Lord',
                    $_SESSION['advisor_history'] ?? []
                );

                // Map to a clean associative array for JS Proxy stability
                $userData = [
                    'username' => $vm->username,
                    'kingdomName' => $vm->kingdomName,
                    'raceName' => $vm->raceName,
                    'level' => $vm->level,
                    'xpProgress' => $vm->xpProgress,
                    'advice' => $vm->advice,
                    'realmTime' => $vm->realmTime,
                    'secondsToNextTick' => $vm->secondsToNextTick,
                    'kingdom' => $kingdom->toArray(),
                    'avatar_path' => $kingdom->user->avatar_path, // CRITICAL FIX: Explicitly pass the path
                    'advisorHistory' => $vm->advisorHistory
                ];
            }
        }

        $state = [
            'component' => $component,
            'props' => $pageData,
            'user' => $userData,
            'csrf' => Csrf::getToken()
        ];

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            return json_encode($state);
        }

        ob_start();
        extract(['state' => $state]);
        require __DIR__ . "/../Views/app.php";
        return ob_get_clean();
    }

    private function isJsonRequest(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') 
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function redirectBack(array $errors = [], array $input = []): void
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $input;
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }
}