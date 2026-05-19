// src/Resources/js/stores/gameStore.svelte.js

// Using $state.raw for the user object can sometimes help with heavy models, 
// but for an RPG, we want deep reactivity.
export const game = $state({
    component: 'home',
    props: {},
    user: null,
    csrf: '',
    secondsToNextTick: 600
});

export function updateGame(data) {
    if (!data) return;
    
    // Batch updates to ensure the component re-renders once
    game.component = data.component;
    game.props = data.props;
    game.user = data.user; 
    game.csrf = data.csrf;
    
    if (data.user?.secondsToNextTick) {
        game.secondsToNextTick = data.user.secondsToNextTick;
    }
}

export function startHeartbeat() {
    // Prevent multiple intervals
    if (window.gameInterval) clearInterval(window.gameInterval);
    
    window.gameInterval = setInterval(() => {
        if (game.secondsToNextTick > 0) {
            game.secondsToNextTick--;
        } else {
            clearInterval(window.gameInterval);
            window.location.reload();
        }
    }, 1000);
}

export const resources = {
    get gold() { return game.user?.kingdom?.gold || 0 },
    get bank() { return game.user?.kingdom?.gold_in_bank || 0 },
    get citizens() { return game.user?.kingdom?.citizens || 0 },
    get turns() { return game.user?.kingdom?.turns || 0 }
};

export const formattedTick = {
    get value() {
        const mins = Math.floor(game.secondsToNextTick / 60);
        const secs = game.secondsToNextTick % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
};