<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let { units = {} } = $props();
    let quantities = $state({});
    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});
    
    function calculateMax(unit) {
        const byCredits = unit.cost_credits > 0 ? Math.floor(resources.credits / unit.cost_credits) : Infinity;
        const byCitizens = unit.cost_citizens > 0 ? Math.floor(resources.citizens / unit.cost_citizens) : Infinity;
        
        const max = Math.min(byCredits, byCitizens);
        return isFinite(max) ? max : 0;
    }

    async function handleTrain(slug) {
        const qty = quantities[slug] || 0;
        if (qty <= 0 || loading) return;

        loading = true;
        message = null;
        const formData = new FormData();
        formData.append('unit_type', slug); 
        formData.append('quantity', qty);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/combat/train', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            message = data;
            if (data.success) {
                window.location.reload();
            } else {
                loading = false;
            }
        } catch (e) {
            message = { success: false, message: "Mobilization link unstable." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-24">
    <!-- Main Header -->
    <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 border-b border-cyan-500/20 pb-8">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Military Mobilization</h1>
            <p class="text-cyan-500/60 text-xs font-bold uppercase tracking-[4px] mt-2 italic">Sector Defense Command Interface</p>
        </div>
        
        <!-- Commander's Protocol (User Journey) -->
        <div class="flex gap-4">
            <div class="px-4 py-3 bg-cyan-950/20 border border-cyan-500/20 rounded-lg flex flex-col items-center">
                <span class="text-[10px] text-cyan-500 font-black uppercase">Phase 01</span>
                <span class="text-xs text-white font-bold uppercase">Enlist</span>
            </div>
            <div class="px-4 py-3 bg-black/40 border border-white/5 rounded-lg flex flex-col items-center opacity-40">
                <span class="text-[10px] text-gray-600 font-black uppercase">Phase 02</span>
                <span class="text-xs text-gray-500 font-bold uppercase">Equip</span>
            </div>
            <div class="px-4 py-3 bg-black/40 border border-white/5 rounded-lg flex flex-col items-center opacity-40">
                <span class="text-[10px] text-gray-600 font-black uppercase">Phase 03</span>
                <span class="text-xs text-gray-500 font-bold uppercase">Deploy</span>
            </div>
        </div>
    </header>

    <!-- Contextual Logistics Dashboard -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-cyan-950/10 border border-cyan-500/10 rounded-2xl p-6 shadow-inner">
        <div class="flex items-center gap-6">
            <div class="p-4 bg-black/40 border border-cyan-500/20 rounded-xl">
                <svg viewBox="0 0 24 24" class="w-8 h-8 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <h4 class="text-xs font-black text-cyan-700 uppercase tracking-widest leading-none mb-1">Available Personnel</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-mono font-black text-white">{resources.citizens.toLocaleString()}</span>
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-widest">Civilians</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6 border-t md:border-t-0 md:border-l border-white/5 pt-4 md:pt-0 md:pl-6">
            <div class="p-4 bg-black/40 border border-cyan-500/20 rounded-xl">
                <svg viewBox="0 0 24 24" class="w-8 h-8 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <div>
                <h4 class="text-xs font-black text-cyan-700 uppercase tracking-widest leading-none mb-1">War Chest</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-mono font-black text-white">{resources.credits.toLocaleString()}</span>
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-widest">Credits</span>
                </div>
            </div>
        </div>
    </section>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-xs font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'} shadow-lg shadow-cyan-500/5">
            {message.message}
        </div>
    {/if}

    <!-- Command Roster (Ledger Layout) -->
    <div class="flex flex-col gap-4">
        {#each Object.entries(units) as [slug, unit]}
            <div class="bg-dark-translucent border border-white/5 rounded-2xl p-6 hover:border-cyan-500/30 transition-all flex flex-col xl:flex-row gap-8 items-center relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white text-5xl italic uppercase select-none">
                    {slug}
                </div>

                <!-- Identity Zone (Left) -->
                <div class="w-full xl:w-1/3 space-y-2 relative z-10">
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-1.5 bg-cyan-500 rounded-full"></span>
                        <h3 class="text-white text-2xl font-title font-black uppercase tracking-widest">{unit.name}</h3>
                    </div>
                    <p class="text-gray-400 text-xs leading-relaxed italic">{unit.description}</p>
                </div>

                <!-- Metrics Zone (Middle) -->
                <div class="w-full xl:w-1/3 grid grid-cols-2 gap-4 relative z-10">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <div class="px-2 py-1 bg-red-950/20 border border-red-900/30 rounded text-center min-w-[50px]">
                                <span class="block text-[8px] text-red-700 font-black uppercase leading-none mb-0.5">Offense</span>
                                <span class="text-sm font-mono font-black text-white">{unit.power_offense}</span>
                            </div>
                            <div class="px-2 py-1 bg-cyan-950/20 border border-cyan-900/30 rounded text-center min-w-[50px]">
                                <span class="block text-[8px] text-cyan-700 font-black uppercase leading-none mb-0.5">Defense</span>
                                <span class="text-sm font-mono font-black text-white">{unit.power_defense}</span>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black text-gray-600 uppercase tracking-widest mb-1">Requisition Cost</span>
                            <span class="text-xs font-mono text-white">
                                <span class="text-cyan-400 font-bold">{unit.cost_credits.toLocaleString()} CP</span> / 
                                <span class="font-bold">{unit.cost_citizens} CIT</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center border-l border-white/5 pl-4">
                        <span class="text-[9px] font-black text-gray-600 uppercase tracking-widest mb-1">Max Enlistment</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-mono text-cyan-400 font-black">{calculateMax(unit).toLocaleString()}</span>
                            <span class="text-[8px] text-gray-500 uppercase font-bold">Units</span>
                        </div>
                    </div>
                </div>

                <!-- Action Zone (Right) -->
                <div class="w-full xl:w-1/3 flex flex-col sm:flex-row gap-4 relative z-10">
                    <div class="flex-grow flex flex-col gap-2">
                        <div class="relative">
                            <input 
                                type="number" 
                                bind:value={quantities[slug]} 
                                placeholder="Magnitude" 
                                class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-4 text-white font-mono text-base focus:border-cyan-400 focus:outline-none transition-colors pr-16" 
                            />
                            <button 
                                onclick={() => quantities[slug] = calculateMax(unit)}
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-2 text-[9px] font-black uppercase bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-lg hover:bg-cyan-500 hover:text-black transition-all"
                            >
                                Max
                            </button>
                        </div>
                        
                        {#if quantities[slug] > 0}
                            <div class="flex justify-between items-center px-1" transition:slide>
                                <span class="text-[9px] font-black text-gray-600 uppercase tracking-widest">Est. Cost:</span>
                                <span class="text-sm font-mono text-white font-black">{(unit.cost_credits * quantities[slug]).toLocaleString()} CP</span>
                            </div>
                        {/if}
                    </div>

                    <button 
                        onclick={() => handleTrain(slug)} 
                        class="sm:w-32 h-[58px] bg-white text-black font-title font-black text-xs uppercase tracking-[2px] rounded-xl hover:bg-cyan-500 hover:shadow-[0_0_20px_rgba(6,182,212,0.4)] transition-all disabled:opacity-20 disabled:grayscale" 
                        disabled={loading || !quantities[slug] || quantities[slug] <= 0 || quantities[slug] > calculateMax(unit)}
                    >
                        {loading ? '...' : 'Enlist'}
                    </button>
                </div>
            </div>
        {/each}
    </div>
    
    <div class="text-center pt-8">
        <p class="text-gray-600 text-[10px] font-bold uppercase tracking-[4px] italic">Tactical Gear requisitioning available in the <a href="/structures/armory" class="text-cyan-400 underline hover:text-white transition-colors">Sector Armory</a>.</p>
    </div>
</div>

<style>
    .text-shadow-glow {
        text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
    }
</style>
