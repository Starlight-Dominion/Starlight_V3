<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, fly, slide } from 'svelte/transition';

    let { dominion = {}, structures = {}, repair_cost = 0 } = $props();

    let loading = $state(false);
    let message = $state(null);

    const hpPercent = $derived(Math.floor((dominion.foundation_hp / dominion.foundation_max_hp) * 100));

    async function runAction(action, id = null) {
        loading = true;
        message = null;
        const fd = new FormData();
        if (id) fd.append('structure_id', id);
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/foundation/${action}`, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            message = data;
            if (data.success) setTimeout(() => window.location.reload(), 1000);
        } catch (e) {
            message = { success: false, message: "Link failure." };
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-24">
    <header class="border-b border-cyan-500/20 pb-6 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Foundations</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2">Structural Engineering Terminal</p>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <!-- INTEGRITY MONITOR -->
    <div class="bg-dark-translucent border-2 border-cyan-500/20 rounded-3xl p-8 relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="space-y-2 text-center md:text-left">
                <h2 class="text-white font-title text-xl uppercase tracking-widest">Sector Integrity</h2>
                <p class="text-[10px] text-gray-500 uppercase">Nano-repair requires active credit flow.</p>
                <div class="flex items-center gap-4 mt-4">
                    <span class="text-3xl font-mono font-bold {hpPercent < 50 ? 'text-red-500' : 'text-cyan-400'}">{hpPercent}%</span>
                    <div class="w-64 h-3 bg-black/50 rounded-full border border-cyan-500/20 overflow-hidden">
                        <div class="h-full bg-cyan-500 shadow-[0_0_15px_rgba(6,182,212,1)] transition-all duration-1000" style="width: {hpPercent}%"></div>
                    </div>
                </div>
            </div>

            {#if hpPercent < 100}
                <div in:fade class="bg-black/40 p-6 rounded-2xl border border-red-900/30 text-center space-y-4">
                    <p class="text-xs font-mono text-red-400">Repair Cost: {repair_cost.toLocaleString()} CP</p>
                    <button onclick={() => runAction('repair')} class="btn-launch py-3 px-8 text-xs" disabled={loading}>Initialize Repair</button>
                </div>
            {/if}
        </div>
    </div>

    <!-- STRUCTURE GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {#each Object.entries(structures) as [key, s]}
            <div class="bg-dark-translucent border border-white/5 rounded-2xl p-6 flex flex-col justify-between hover:border-cyan-500/30 transition-all group">
                <div class="space-y-4">
                    <header class="flex justify-between items-start">
                        <div>
                            <h3 class="text-white font-title text-sm uppercase tracking-wider group-hover:text-cyan-400 transition-colors">{s.name}</h3>
                            <p class="text-[9px] text-gray-600 uppercase font-bold tracking-widest mt-1">Tier {s.current_level} / {s.max_level}</p>
                        </div>
                        <div class="w-8 h-8 bg-black rounded border border-white/5 flex items-center justify-center text-[10px] text-cyan-900 font-mono">
                            {s.current_level}
                        </div>
                    </header>

                    <p class="text-[11px] text-gray-500 leading-relaxed italic">{s.description}</p>

                    {#if s.next_upgrade}
                        <div class="bg-black/40 p-4 rounded-xl border border-white/5 space-y-2">
                            <p class="text-[9px] font-black text-cyan-800 uppercase tracking-widest">Next Evolution</p>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-white font-bold">{s.next_upgrade.buff_name}</span>
                                <span class="text-[10px] text-cyan-500 font-mono">{parseInt(s.next_upgrade.cost).toLocaleString()} CP</span>
                            </div>
                        </div>
                    {/if}
                </div>

                <div class="mt-8 space-y-3">
                    <button 
                        onclick={() => runAction('upgrade', s.id)}
                        class="w-full bg-cyan-950/20 border border-cyan-500/30 text-cyan-400 py-3 rounded-lg font-title text-[9px] uppercase tracking-[2px] hover:bg-cyan-500 hover:text-black transition-all"
                        disabled={loading || !s.next_upgrade || resources.gold < s.next_upgrade.cost}
                    >
                        {#if !s.next_upgrade}
                            Max Tier Reached
                        {:else if resources.gold < s.next_upgrade.cost}
                            Insufficient Credits
                        {:else}
                            Evolve Structure
                        {/if}
                    </button>

                    <!-- MODIFICATION SLOT -->
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-2 rounded-full {s.mod ? 'bg-cyan-400 animate-pulse' : 'bg-gray-800'}"></div>
                        <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">
                            MOD_SLOT: {s.mod || 'VACANT'}
                        </span>
                    </div>
                </div>
            </div>
        {/each}
    </div>
</div>