<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { status = {} } = $props();

    let session = $state(status.active_session || null);
    let dailyRemaining = $state(status.daily_remaining);
    let threeDayRemaining = $state(status.three_day_remaining);
    let maxClicks = $state(status.max_clicks);
    
    let loading = $state(false);
    let message = $state(null);
    let clicking = $state(false);

    const progress = $derived(session ? (session.clicks_count / maxClicks) * 100 : 0);

    async function startSession() {
        if (loading) return;
        loading = true;
        message = null;

        const fd = new FormData();
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch('/combat/recruit/start', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                session = data.session;
                // Refresh status values would be better from data, 
                // but let's assume reload or local state update
            } else {
                message = { success: false, message: data.message };
            }
        } catch (e) {
            message = { success: false, message: "Neural link initialization failed." };
        } finally {
            loading = false;
        }
    }

    async function handleStep() {
        if (!session || clicking || session.clicks_count >= maxClicks) return;
        clicking = true;

        const fd = new FormData();
        fd.append('session_id', session.id);
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch('/combat/recruit/click', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                session.clicks_count = data.count;
                // Reactive update for sidebar
                resources.citizens += 1;
            } else if (data.message === "Mobilization complete.") {
                session = null;
                window.location.reload(); // Hard refresh to reset limits
            } else {
                message = { success: false, message: data.message };
            }
        } catch (e) {
            message = { success: false, message: "Link unstable. Data loss imminent." };
        } finally {
            clicking = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-24">
    <header class="border-b border-cyan-500/20 pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Neural Recruitment</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2 italic">Direct civilian processing for sector expansion.</p>
        </div>

        <div class="flex gap-4">
            <div class="bg-dark-translucent border border-cyan-500/20 p-4 rounded-xl text-center min-w-[120px]">
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Daily Access</span>
                <span class="text-xl font-black {dailyRemaining > 0 ? 'text-cyan-400' : 'text-red-600'}">{dailyRemaining}</span>
            </div>
            <div class="bg-dark-translucent border border-cyan-500/20 p-4 rounded-xl text-center min-w-[120px]">
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">72H Allocation</span>
                <span class="text-xl font-black {threeDayRemaining > 0 ? 'text-cyan-400' : 'text-red-600'}">{threeDayRemaining}</span>
            </div>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="max-w-4xl mx-auto">
        {#if !session}
            <div in:fade class="bg-dark-translucent border border-cyan-500/10 rounded-3xl p-16 text-center space-y-8 shadow-2xl relative overflow-hidden">
                <div class="absolute inset-0 opacity-5 pointer-events-none">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-500 rounded-full blur-[120px]"></div>
                </div>

                <div class="space-y-4 relative z-10">
                    <h2 class="text-2xl font-title font-black text-white uppercase tracking-widest">Initialize Neural Uplink</h2>
                    <p class="text-gray-500 text-xs max-w-md mx-auto leading-relaxed italic">
                        Authorized commanders may manually oversee civilian processing. 
                        Each session facilitates the enlistment of {maxClicks} citizens.
                    </p>
                </div>

                <button 
                    onclick={startSession}
                    class="bg-cyan-600 hover:bg-cyan-400 text-white font-title font-black px-12 py-5 rounded-2xl uppercase tracking-[4px] transition-all disabled:opacity-20 shadow-[0_0_30px_rgba(6,182,212,0.2)]"
                    disabled={loading || dailyRemaining <= 0 || threeDayRemaining <= 0}
                >
                    Establish Connection
                </button>
            </div>
        {:else}
            <div in:fade class="bg-dark-translucent border border-cyan-500/20 rounded-3xl p-12 space-y-12 shadow-2xl relative">
                <div class="flex justify-between items-end">
                    <div class="space-y-1">
                        <span class="text-[10px] font-black text-cyan-800 uppercase tracking-[3px]">Link Status</span>
                        <h2 class="text-2xl font-title font-black text-cyan-400 uppercase">Processing Civilians</h2>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Session Progress</span>
                        <div class="text-3xl font-mono font-black text-white">
                            {session.clicks_count} <span class="text-xs text-gray-700">/ {maxClicks}</span>
                        </div>
                    </div>
                </div>

                <!-- PROGRESS BAR -->
                <div class="h-4 bg-black/60 border border-white/5 rounded-full overflow-hidden p-0.5">
                    <div 
                        class="h-full bg-gradient-to-r from-cyan-900 via-cyan-500 to-cyan-400 rounded-full shadow-[0_0_15px_rgba(6,182,212,0.5)] transition-all duration-300"
                        style="width: {progress}%"
                    ></div>
                </div>

                <div class="flex justify-center">
                    <button 
                        onclick={handleStep}
                        onmousedown={(e) => e.preventDefault()}
                        class="w-64 h-64 rounded-full border-4 border-cyan-500/20 bg-black/40 flex items-center justify-center relative group transition-all active:scale-95 active:border-cyan-400"
                        disabled={clicking}
                    >
                        <div class="absolute inset-0 rounded-full border border-cyan-500/5 group-hover:scale-110 transition-transform duration-500"></div>
                        <div class="absolute inset-4 rounded-full border-2 border-dashed border-cyan-500/10 animate-[spin_10s_linear_infinite]"></div>
                        
                        <div class="text-center relative z-10">
                            <div class="w-12 h-12 mx-auto mb-4 text-cyan-500 group-hover:text-cyan-400 transition-colors">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                            </div>
                            <span class="block text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Process</span>
                            <span class="block text-[8px] font-bold text-gray-600 uppercase tracking-widest">Neural Link</span>
                        </div>

                        {#if clicking}
                             <div class="absolute inset-0 rounded-full bg-cyan-500/10 animate-ping"></div>
                        {/if}
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-[9px] text-gray-600 uppercase tracking-widest font-black">Link Stability: {100 - (clicking ? 15 : 0)}% &bull; Latency: 42ms</p>
                </div>
            </div>
        {/if}
    </div>
</div>

<style>
    .input-terminal {
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(6, 182, 212, 0.2);
        color: #22d3ee;
        padding: 1rem;
        border-radius: 0.75rem;
        width: 100%;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    .input-terminal:focus {
        border-color: #22d3ee;
        outline: none;
        box-shadow: 0 0 15px rgba(6, 182, 212, 0.2);
    }
</style>
