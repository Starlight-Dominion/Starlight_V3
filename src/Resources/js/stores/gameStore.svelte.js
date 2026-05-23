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
        max_deposits: 6,
        citizens_per_tick: 0
    });

    nextTickSeconds = $state(900);

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

        if (this.user?.dominion) {
            this.syncResources(this.user.dominion);
        }

        if (this.user?.citizen_growth_rate) {
            this.resources.citizens_per_tick = this.user.citizen_growth_rate;
        }
        
        if (initialState.user?.secondsToNextTick) {
            this.nextTickSeconds = initialState.user.secondsToNextTick;
        }
    }

    syncResources(dominion) {
        this.resources.credits = dominion.credits ?? 0;
        this.resources.bank = dominion.credits_banked ?? 0;
        this.resources.citizens = dominion.citizens ?? 0;
        this.resources.turns = dominion.turns ?? 0;
        this.resources.xp = dominion.xp ?? 0;
        this.resources.deposits_today = dominion.deposits_today ?? 0;
    }

    startHeartbeat() {
        if (this.heartbeatInterval) return;

        this.heartbeatInterval = setInterval(() => {
            if (this.nextTickSeconds > 0) {
                this.nextTickSeconds--;
            } else {
                this.nextTickSeconds = 900;
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
            if (data.user?.dominion) {
                this.syncResources(data.user.dominion);
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
