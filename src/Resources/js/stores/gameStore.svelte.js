class GameStore {
    component = $state('home');
    props = $state({});
    user = $state(null);
    csrf = $state('');
    heartbeatInterval = null;

    resources = $state({
        credits: 0,
        bank: 0,
        citizens: 0,
        turns: 0,
        xp: 0,
        deposits_today: 0,
        max_deposits: 6
    });

    nextTickSeconds = $state(600);

    get formattedTick() {
        const m = Math.floor(this.nextTickSeconds / 60);
        const s = this.nextTickSeconds % 60;
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    init(initialState) {
        this.component = initialState.component || 'home';
        this.props = initialState.props || {};
        this.user = initialState.user || null;
        this.csrf = initialState.csrf || '';

        if (this.user?.kingdom) {
            this.syncResources(this.user.kingdom);
        }
        
        if (initialState.user?.secondsToNextTick) {
            this.nextTickSeconds = initialState.user.secondsToNextTick;
        }
    }

    syncResources(kingdom) {
        this.resources.credits = kingdom.credits ?? 0;
        this.resources.bank = kingdom.credits_banked ?? 0;
        this.resources.citizens = kingdom.citizens ?? 0;
        this.resources.turns = kingdom.turns ?? 0;
        this.resources.xp = kingdom.xp ?? 0;
        this.resources.deposits_today = kingdom.deposits_today ?? 0;
    }

    startHeartbeat() {
        if (this.heartbeatInterval) return;

        this.heartbeatInterval = setInterval(() => {
            if (this.nextTickSeconds > 0) {
                this.nextTickSeconds--;
            } else {
                this.nextTickSeconds = 600;
                this.refreshState();
            }
        }, 1000);
    }

    async refreshState() {
        try {
            const res = await fetch(window.location.pathname, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.user?.kingdom) {
                this.syncResources(data.user.kingdom);
            }
        } catch (e) {
            console.error("Telemetry link lost.");
        }
    }
}

export const game = new GameStore();
export const resources = game.resources;

/**
 * Functional exports for app.js integration
 */
export const updateGame = (state) => {
    game.init(state);
};

export const startHeartbeat = () => {
    game.startHeartbeat();
};
