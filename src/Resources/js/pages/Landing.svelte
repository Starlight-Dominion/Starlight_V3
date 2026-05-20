<script>
    import { fade, fly, scale } from 'svelte/transition';
    import { game } from '../stores/gameStore.svelte.js';

    // Telemetry Stats passed from HomeController
    let { world_stats = { players: 0, battles_24h: 0 } } = $props();

    // UI States
    let showAuthModal = $state(false);
    let authTab = $state('login'); // 'login' or 'register'
    let loading = $state(false);
    let errorMessage = $state(null);

    // Asset Resolution
    const bgUrl = "/images/backgroundMain.avif";

    // Normalized Dominion Data
    const raceOptions = ['Human', 'Cyborg', 'Shade', 'Synthera'];

    // Registration/Auth State
    let formData = $state({
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        kingdom_name: '', // Target for 'dominion_name'
        race: 'Human'
    });

    async function handleAuth(e) {
        e.preventDefault();
        loading = true;
        errorMessage = null;

        const submission = new FormData();
        // Map current state to FormData for the PHP backend
        submission.append('username', formData.username);
        submission.append('password', formData.password);
        submission.append('_csrf', game.csrf);

        if (authTab === 'register') {
            submission.append('email', formData.email);
            submission.append('kingdom_name', formData.kingdom_name);
            submission.append('password_confirmation', formData.password_confirmation);
            submission.append('race', formData.race);
        }

        try {
            const endpoint = authTab === 'login' ? '/login' : '/register';
            const res = await fetch(endpoint, {
                method: 'POST',
                body: submission,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.ok) {
                // Success: Redirect to Neural Link Dashboard
                window.location.href = '/dashboard';
            } else {
                const data = await res.json();
                errorMessage = data.errors?.[0] || "Authorization failed. Check terminal logs.";
                loading = false;
            }
        } catch (err) {
            errorMessage = "Deep-space relay failure. Check connection.";
            loading = false;
        }
    }
</script>

<!-- Main Tactical Container -->
<div 
    class="min-h-screen w-full bg-cover bg-center bg-fixed flex flex-col items-center justify-center relative overflow-x-hidden"
    style="background-image: url('{bgUrl}');"
>
    <!-- Atmosphere Overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-[#030712]/60 via-[#030712]/40 to-[#030712]/90 z-0"></div>

    <!-- Hero Content -->
    <section class="relative z-10 text-center max-w-5xl mx-auto px-6 pt-24 pb-12">
        <div in:fly={{ y: 30, duration: 1000 }}>
            <h1 class="text-6xl md:text-9xl font-title font-black tracking-[10px] text-shadow-glow text-white uppercase leading-none">
                Starlight <br /> <span class="text-cyan-400">Dominion</span>
            </h1>
            <p class="text-cyan-500/80 text-lg md:text-xl font-bold mt-8 uppercase tracking-[8px] font-title">
                Strategic Sector Command
            </p>
            <p class="mt-10 text-lg text-gray-300 max-w-3xl mx-auto leading-relaxed font-light">
                Establish your command, stabilize decentralized sectors, and expand a galactic dominion that persists across the void.
            </p>
            
            <div class="mt-14">
                <button 
                    class="btn-launch"
                    onclick={() => showAuthModal = true}
                >
                    Initialize Command
                </button>
            </div>
        </div>

        <!-- Telemetry Data -->
        <div in:fade={{ delay: 1000 }} class="mt-32 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-0 border border-cyan-500/20 bg-black/40 backdrop-blur-md rounded-xl overflow-hidden shadow-[0_0_30px_rgba(0,0,0,0.5)]">
            <div class="p-8 border-r border-cyan-500/20 hover:bg-cyan-500/5 transition-colors">
                <div class="text-3xl font-black text-white font-title mb-1">{world_stats.players?.toLocaleString() || 0}</div>
                <div class="text-[9px] font-black text-cyan-500 uppercase tracking-[3px]">Commanders</div>
            </div>
            <div class="p-8 border-r border-cyan-500/20 hover:bg-cyan-500/5 transition-colors">
                <div class="text-3xl font-black text-white font-title mb-1">600s</div>
                <div class="text-[9px] font-black text-cyan-500 uppercase tracking-[3px]">Sector Pulse</div>
            </div>
            <div class="p-8 border-r border-cyan-500/20 hover:bg-cyan-500/5 transition-colors">
                <div class="text-3xl font-black text-white font-title mb-1">{world_stats.battles_24h?.toLocaleString() || 0}</div>
                <div class="text-[9px] font-black text-cyan-500 uppercase tracking-[3px]">Sorties (24h)</div>
            </div>
            <div class="p-8 hover:bg-cyan-500/5 transition-colors">
                <div class="text-3xl font-black text-cyan-400 font-title mb-1 uppercase animate-pulse">Active</div>
                <div class="text-[9px] font-black text-cyan-500 uppercase tracking-[3px]">Core Status</div>
            </div>
        </div>
    </section>

    <!-- Auth Terminal (Modal) -->
    {#if showAuthModal}
        <div 
            class="fixed inset-0 bg-black/90 backdrop-blur-md z-[100] flex items-center justify-center p-6"
            transition:fade={{ duration: 250 }}
        >
            <div 
                class="bg-[#060a19] border-2 border-cyan-500/30 rounded-2xl shadow-[0_0_50px_rgba(8,145,178,0.3)] w-full max-w-md overflow-hidden relative"
                transition:scale={{ start: 0.95, duration: 300 }}
            >
                <!-- Decorative HUD corner -->
                <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-cyan-400 opacity-50"></div>
                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-cyan-400 opacity-50"></div>

                <div class="flex justify-between items-center p-6 bg-cyan-950/20 border-b border-cyan-500/10">
                    <div class="flex gap-8">
                        <button 
                            class="font-title text-[10px] font-bold uppercase tracking-[3px] transition-all {authTab === 'login' ? 'text-cyan-400 scale-110' : 'text-gray-600 hover:text-gray-300'}"
                            onclick={() => authTab = 'login'}
                        >
                            Authorize
                        </button>
                        <button 
                            class="font-title text-[10px] font-bold uppercase tracking-[3px] transition-all {authTab === 'register' ? 'text-cyan-400 scale-110' : 'text-gray-600 hover:text-gray-300'}"
                            onclick={() => authTab = 'register'}
                        >
                            Enlist
                        </button>
                    </div>
                    <button class="text-gray-500 hover:text-white transition-colors" onclick={() => showAuthModal = false}>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-8">
                    {#if errorMessage}
                        <div in:fade class="bg-red-950/30 border border-red-500/50 text-red-400 p-4 rounded-lg text-[10px] font-black uppercase tracking-[2px] text-center mb-6">
                            {errorMessage}
                        </div>
                    {/if}

                    <form onsubmit={handleAuth} class="space-y-6">
                        {#if authTab === 'login'}
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Commander Handle</label>
                                <input type="text" bind:value={formData.username} class="input-terminal" placeholder="USER_ID" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Encryption Key</label>
                                <input type="password" bind:value={formData.password} class="input-terminal" placeholder="CIPHER" required />
                            </div>
                        {:else}
                            <!-- Registration HUD -->
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Comms Frequency (Email)</label>
                                <input type="email" bind:value={formData.email} class="input-terminal" placeholder="RELAY@STARNET.IO" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Identity Handle</label>
                                <input type="text" bind:value={formData.username} class="input-terminal" placeholder="COMMANDER_NAME" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Dominion Designation</label>
                                <input type="text" bind:value={formData.kingdom_name} class="input-terminal" placeholder="SECTOR_NAME" required />
                            </div>
                            
                            <!-- Normalized Race Selection -->
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-600 uppercase tracking-[2px] ml-1">Evolutionary Strain (Race)</label>
                                <div class="relative">
                                    <select bind:value={formData.race} class="input-terminal appearance-none cursor-pointer">
                                        {#each raceOptions as race}
                                            <option value={race}>{race}</option>
                                        {/each}
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-cyan-500">
                                        ▼
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Security</label>
                                    <input type="password" bind:value={formData.password} class="input-terminal" placeholder="CODE" required />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-1">Verify</label>
                                    <input type="password" bind:value={formData.password_confirmation} class="input-terminal" placeholder="CODE" required />
                                </div>
                            </div>
                        {/if}

                        <button 
                            type="submit" 
                            class="w-full bg-cyan-700/50 hover:bg-cyan-600 border border-cyan-500/50 text-white font-title font-black py-5 rounded-lg uppercase tracking-[4px] transition-all disabled:opacity-50 shadow-[0_0_20px_rgba(8,145,178,0.2)]"
                            disabled={loading}
                        >
                            {#if loading}
                                Establishing Neural Link...
                            {:else}
                                {authTab === 'login' ? 'Engage Command' : 'Initialize Sector'}
                            {/if}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    {/if}
</div>

<style>
    /* Prevent interaction while modal is active */
    :global(body) {
        background-color: #030712 !important;
    }
</style>