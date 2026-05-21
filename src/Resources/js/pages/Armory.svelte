<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { loadouts = {}, armory_level = 0, upgrade_cost = null } = $props();

    let activeTab = $state('soldiers');
    let message = $state(null);
    let loading = $state(false);

    const currentLoadout = $derived(loadouts[activeTab] || { categories: {}, unit_count: 0 });

    async function handleAction(action, itemId = null, qty = 1) {
        loading = true;
        message = null;
        
        const fd = new FormData();
        if (itemId) fd.append('item_id', itemId);
        fd.append('quantity', qty);
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/armory/${action}`, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            message = data;
            if (data.success) {
                // Svelte logic: We could manually update stores, but for data consistency
                // after a bulk purchase, we refresh the session state.
                window.location.reload();
            }
        } catch (e) {
            message = { success: false, message: "Signal interference detected." };
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-24">
    <header class="border-b border-cyan-500/20 pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Sector Armory</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2 italic">Standardizing combat efficiency across all divisions.</p>
        </div>

        <div class="bg-dark-translucent border border-cyan-500/20 p-4 rounded-xl flex items-center gap-6">
            <div>
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Tech Rank</span>
                <span class="text-xl font-black text-cyan-400">RANK {armory_level}</span>
            </div>
            {#if upgrade_cost}
                <div class="border-l border-white/5 pl-6">
                    <button 
                        onclick={() => handleAction('upgrade')}
                        class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded font-title text-[9px] uppercase tracking-widest transition-all disabled:opacity-50"
                        disabled={loading || resources.credits < upgrade_cost}
                    >
                        Evolve Tech ({upgrade_cost.toLocaleString()} CP)
                    </button>
                </div>
            {/if}
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <!-- TAB NAV -->
    <div class="flex flex-wrap gap-2">
        {#each Object.keys(loadouts) as type}
            <button 
                onclick={() => activeTab = type}
                class="px-8 py-3 rounded-lg font-title text-[10px] uppercase tracking-[2px] border transition-all {activeTab === type ? 'bg-cyan-500 text-black border-cyan-400' : 'bg-black/40 border-white/5 text-gray-500 hover:text-white'}"
            >
                {type}
            </button>
        {/each}
    </div>

    <!-- CATEGORY GRID -->
    <div class="space-y-12">
        {#each Object.entries(currentLoadout.categories) as [catKey, category]}
            <section class="space-y-6">
                <div class="flex justify-between items-center bg-cyan-950/10 p-4 border border-cyan-500/10 rounded-xl">
                    <h3 class="text-xs font-title font-black text-white uppercase tracking-[4px]">{category.title}</h3>
                    <span class="text-[9px] font-bold text-cyan-600 uppercase">Operational Force: {currentLoadout.unit_count.toLocaleString()} Units</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {#each Object.entries(category.items) as [slug, item]}
                        <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl relative group hover:border-cyan-500/30 transition-all {!item.unlocked ? 'opacity-30' : ''}">
                            <header class="flex justify-between items-start mb-4">
                                <h4 class="text-white font-bold text-sm uppercase font-title">{item.name}</h4>
                                <span class="text-xs font-mono font-bold text-cyan-500">{item.owned_quantity.toLocaleString()}</span>
                            </header>

                            <div class="space-y-2 mb-6">
                                <div class="flex justify-between text-[10px]">
                                    <span class="text-gray-600 uppercase">Stat Mod</span>
                                    <span class="text-white font-mono">+{item.attack_bonus || item.defense_bonus}</span>
                                </div>
                                <div class="flex justify-between text-[10px]">
                                    <span class="text-gray-600 uppercase">Requisition</span>
                                    <span class="text-cyan-600 font-mono">{item.cost.toLocaleString()} CP</span>
                                </div>
                            </div>

                            {#if item.unlocked}
                                <div class="grid grid-cols-2 gap-2">
                                    <button 
                                        onclick={() => handleAction('buy', item.id, 1)}
                                        class="bg-cyan-950/40 border border-cyan-500/20 text-cyan-400 py-2 rounded text-[9px] font-black uppercase tracking-widest hover:bg-cyan-500 hover:text-black transition-all"
                                        disabled={loading || resources.credits < item.cost}
                                    >Buy x1</button>
                                    <button 
                                        onclick={() => handleAction('buy', item.id, 10)}
                                        class="bg-cyan-950/40 border border-cyan-500/20 text-cyan-400 py-2 rounded text-[9px] font-black uppercase tracking-widest hover:bg-cyan-500 hover:text-black transition-all"
                                        disabled={loading || resources.credits < (item.cost * 10)}
                                    >Buy x10</button>
                                </div>
                            {:else}
                                <div class="absolute inset-0 flex items-center justify-center bg-black/60 rounded-2xl z-10">
                                    <span class="text-[8px] font-black text-white uppercase tracking-[3px]">Tech Rank {item.armory_level_req} Required</span>
                                </div>
                            {/if}
                        </div>
                    {/each}
                </div>
            </section>
        {/each}
    </div>
</div>