<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let { units = {} } = $props();
    let quantities = $state({});
    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});
    
    // Units are stored in the dominion_manpower table, but we currently pull them 
    // from the kingdom object if they are aggregated there. 
    // In our current SDO architecture, we need to ensure we're mapping the 
    // manpower correctly. For now, we'll keep the logic consistent with 
    // how the controller provides data, but update the theme.

    function calculateMax(unit) {
        const byCredits = unit.cost_credits > 0 ? Math.floor(resources.credits / unit.cost_credits) : Infinity;
        const byCitizens = unit.cost_citizens > 0 ? Math.floor(resources.citizens / unit.cost_citizens) : Infinity;
        const byTurns = unit.cost_turns > 0 ? Math.floor(resources.turns / unit.cost_turns) : Infinity;
        
        const max = Math.min(byCredits, byCitizens, byTurns);
        return isFinite(max) ? max : 0;
    }

    async function handleTrain(slug) {
        const qty = quantities[slug] || 0;
        if (qty <= 0 || loading) return;

        loading = true;
        message = null;
        const formData = new FormData();
        formData.append('unit_type', slug); // Service expects 'unitSlug' but controller maps 'unit_type'
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
    <header class="border-b border-cyan-500/20 pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Military Mobilization</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2 italic">Converting civilian assets into frontline tactical divisions.</p>
        </div>
        <div class="bg-dark-translucent border border-cyan-500/20 p-4 rounded-xl max-w-xs relative overflow-hidden">
             <div class="absolute top-0 right-0 w-8 h-8 opacity-10">
                <svg viewBox="0 0 24 24" fill="currentColor" class="text-cyan-500"><path d="M12 2L4.5 20.29L5.21 21L12 18L18.79 21L19.5 20.29L12 2Z"/></svg>
            </div>
            <p class="text-[9px] text-gray-500 italic leading-relaxed relative z-10">
                <span class="text-cyan-400 font-black uppercase block mb-1">Commander's Directive:</span>
                Freshly enlisted personnel require <a href="/structures/armory" class="text-white underline hover:text-cyan-400">Tactical Gear</a> to reach peak combat efficiency. 1:1 Unit-to-Item ratio enforced.
            </p>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {#each Object.entries(units) as [slug, unit]}
            <div class="bg-dark-translucent border border-white/5 rounded-3xl p-8 space-y-6 group hover:border-cyan-500/30 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white text-4xl italic uppercase">
                    {slug}
                </div>

                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <h3 class="text-white text-xl font-title font-black uppercase tracking-widest">{unit.name}</h3>
                        <p class="text-cyan-500/60 text-[8px] font-bold uppercase tracking-[2px] mt-1 italic">Division Class: {unit.slug}</p>
                    </div>
                    <div class="flex gap-2">
                        <div class="text-center px-3 py-1 bg-red-950/20 border border-red-900/30 rounded">
                            <span class="block text-[7px] text-red-900 font-black uppercase">Offense</span>
                            <span class="text-xs font-mono font-black text-white">{unit.power_offense}</span>
                        </div>
                        <div class="text-center px-3 py-1 bg-cyan-950/20 border border-cyan-900/30 rounded">
                            <span class="block text-[7px] text-cyan-900 font-black uppercase">Defense</span>
                            <span class="text-xs font-mono font-black text-white">{unit.power_defense}</span>
                        </div>
                    </div>
                </div>

                <p class="text-gray-400 text-[10px] leading-relaxed italic relative z-10 pr-12">{unit.description}</p>

                <div class="bg-black/40 p-4 rounded-xl border border-white/5 flex justify-between items-center font-mono text-[9px] relative z-10">
                    <span class="text-gray-600 uppercase tracking-widest font-bold">Standard Requisition</span>
                    <span class="text-white">
                        <span class="text-cyan-400 font-bold">{unit.cost_credits.toLocaleString()} CP</span> | 
                        <span class="text-white font-bold">{unit.cost_citizens} CIT</span> | 
                        <span class="text-gray-400 font-bold">{unit.cost_turns} T</span>
                    </span>
                </div>

                <div class="space-y-4 relative z-10">
                    <div class="flex gap-3">
                        <div class="relative flex-grow">
                            <input 
                                type="number" 
                                bind:value={quantities[slug]} 
                                placeholder="Magnitude" 
                                class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-4 text-white font-mono text-sm focus:border-cyan-500 focus:outline-none transition-colors" 
                            />
                            <button 
                                onclick={() => quantities[slug] = calculateMax(unit)}
                                class="absolute right-3 top-1/2 -translate-y-1/2 px-3 py-1.5 text-[8px] font-black uppercase bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-md hover:bg-cyan-500 hover:text-black transition-all"
                            >
                                Max
                            </button>
                        </div>
                        <button 
                            onclick={() => handleTrain(slug)} 
                            class="px-10 bg-white text-black font-title font-black text-[10px] uppercase tracking-[2px] rounded-xl hover:bg-cyan-500 transition-all disabled:opacity-20" 
                            disabled={loading || !quantities[slug] || quantities[slug] <= 0 || quantities[slug] > calculateMax(unit)}
                        >
                            Enlist
                        </button>
                    </div>
                    <div class="flex justify-between items-center px-2">
                        <div class="flex flex-col">
                            <span class="text-[7px] font-black text-gray-600 uppercase tracking-widest">Available Capacity</span>
                            <span class="text-xs font-mono text-cyan-400 font-black">{calculateMax(unit).toLocaleString()} <span class="text-[8px] text-gray-700">Units</span></span>
                        </div>
                        {#if quantities[slug] > 0}
                            <div class="text-right">
                                <span class="text-[7px] font-black text-gray-600 uppercase tracking-widest text-right">Total Expenditure</span>
                                <span class="block text-xs font-mono text-white font-black">{(unit.cost_credits * quantities[slug]).toLocaleString()} CP</span>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        {/each}
    </div>
</div>
