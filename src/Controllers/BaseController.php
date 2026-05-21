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

    protected function render(string $component, array $pageData = []): string
    {
        $userData = null;

        if (isset($_SESSION['user_id'])) {
            $dominion = $this->gameService->getKingdomByUserId((int)$_SESSION['user_id']);
            if ($dominion) {
                $advice = $this->advisorService->getContextualAdviceFromKingdom($dominion->toArray());
                $vm = new GameStateViewModel(
                    $dominion,
                    $this->gameService,
                    $advice,
                    $_SESSION['username'] ?? 'Unknown Lord',
                    $_SESSION['advisor_history'] ?? []
                );

                $userData = [
                    'username' => $vm->username,
                    'kingdomName' => $vm->kingdomName,
                    'raceName' => $vm->raceName,
                    'level' => $vm->level,
                    'xpProgress' => $vm->xpProgress,
                    'advice' => $vm->advice,
                    'realmTime' => $vm->realmTime,
                    'secondsToNextTick' => $vm->secondsToNextTick,
                    'kingdom' => $dominion->toArray(),
                    'avatar_path' => $dominion->user->avatar_path,
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
}