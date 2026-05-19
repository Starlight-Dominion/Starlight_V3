<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { loadouts = {}, armory_level = 0, upgrade_cost = null } = $props();

    let activeTab = $state('soldiers');
    let message = $state(null);
    let loading = $state(false);
    let showHidden = $state(false);

    const currentLoadout = $derived(loadouts[activeTab] || { categories: {}, unit_count: 0 });

    async function handleAction(action, itemId = null, qty = 1) {
        if (loading) return;
        loading = true;
        message = null;
        
        const formData = new FormData();
        if (itemId) formData.append('item_id', itemId);
        formData.append('quantity', qty);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/armory/${action}`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                message = data;
                loading = false;
            }
        } catch (e) {
            message = { success: false, message: "Forge signal lost." };
            loading = false;
        }
    }

    function getCategoryReadiness(category) {
        const items = category.items || {};
        const totalOwned = Object.values(items).reduce((acc, item) => acc + (item.owned_quantity || 0), 0);
        const unitCount = currentLoadout.unit_count || 0;
        const percent = unitCount > 0 ? Math.min(100, (totalOwned / unitCount) * 100) : 100;
        return { totalOwned, percent };
    }
</script>

<div class="space-y-8 pb-24">
    <header class="border-b border-[#2a231e] pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">The Royal Armory</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Steel wins wars. Gold buys steel.</p>
        </div>

        <div class="bg-[#0f0f0f] border border-[#2a231e] p-4 rounded-xl flex items-center gap-6">
            <div>
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Structure Rank</span>
                <span class="text-xl font-black text-[#c5a059]">LEVEL {armory_level}</span>
            </div>
            {#if upgrade_cost}
                <div class="border-l border-[#2a231e] pl-6 flex flex-col gap-1">
                    <button 
                        onclick={() => handleAction('upgrade', null, 0)} 
                        class="bg-[#c5a059] text-black px-4 py-2 rounded font-black text-[9px] uppercase tracking-widest hover:bg-white transition-all disabled:opacity-50"
                        disabled={loading || (game.user?.kingdom?.gold || 0) < upgrade_cost}
                    >
                        Upgrade Rank
                    </button>
                    <span class="text-[8px] font-bold text-gray-500 uppercase tracking-tighter text-center">{upgrade_cost.toLocaleString()} GP</span>
                </div>
            {:else}
                <div class="border-l border-[#2a231e] pl-6">
                    <span class="text-[8px] font-black text-[#3f6b2f] uppercase tracking-widest">Max Rank Reached</span>
                </div>
            {/if}
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-[#3f6b2f]/20 border-[#3f6b2f] text-[#3f6b2f]' : 'bg-red-900/20 border-red-900 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            {#each Object.keys(loadouts) as type}
                <button onclick={() => activeTab = type} class="px-8 py-3 rounded-sm font-black text-[10px] uppercase tracking-widest border {activeTab === type ? 'bg-[#c5a059] border-[#c5a059] text-black' : 'bg-[#0f0f0f] border-[#2a231e] text-gray-500 hover:border-[#c5a059]/50'}">{type}</button>
            {/each}
        </div>
        <button onclick={() => showHidden = !showHidden} class="px-4 py-2 rounded-sm font-black text-[9px] uppercase tracking-widest border {showHidden ? 'bg-red-900/20 border-red-900 text-red-500' : 'bg-[#0f0f0f] border-[#2a231e] text-gray-500'}">
            {showHidden ? 'Hide Hidden Items' : 'Show Hidden Items'}
        </button>
    </div>

    <div class="space-y-12">
        <div class="bg-[#1a1a1a]/50 p-6 border-l-4 border-[#c5a059] rounded-r-xl">
            <h2 class="text-2xl font-black text-white uppercase tracking-tight">{currentLoadout.title || 'Loadout'}</h2>
            <p class="text-[10px] font-bold text-[#c5a059] uppercase tracking-widest mt-1">Operational Force: {(currentLoadout.unit_count || 0).toLocaleString()} Units</p>
        </div>

        <div class="grid grid-cols-1 gap-16">
            {#each Object.entries(currentLoadout.categories || {}) as [catKey, category]}
                {@const readiness = getCategoryReadiness(category)}
                {@const items = Object.entries(category.items || {})}
                {@const visibleItems = items.filter(([_, item]) => showHidden || !item.is_hidden)}
                
                {#if visibleItems.length > 0}
                    <section class="space-y-6" in:fade>
                        <div class="flex flex-col md:flex-row justify-between items-center bg-[#0f0f0f] p-4 border border-[#2a231e] rounded-xl gap-4">
                            <h3 class="text-xs font-black text-white uppercase tracking-[4px]">{category.title}</h3>
                            <div class="flex items-center gap-6">
                                <span class="font-mono text-sm {readiness.percent < 100 ? 'text-red-600' : 'text-[#3f6b2f]'} font-bold">{readiness.percent.toFixed(0)}% Ready</span>
                                <div class="w-48 h-2 bg-black rounded-full overflow-hidden border border-[#2a231e]">
                                    <div class="h-full {readiness.percent < 100 ? 'bg-red-900' : 'bg-[#3f6b2f]'} transition-all" style="width: {readiness.percent}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {#each visibleItems as [itemSlug, item]}
                                <div class="bg-[#0f0f0f] border border-[#2a231e] p-6 rounded-2xl relative group transition-all {item.is_hidden ? 'opacity-60 border-dashed' : ''} {!item.unlocked ? 'opacity-30 grayscale pointer-events-none' : 'hover:border-[#c5a059]/40 hover:bg-[#141414]' }">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="text-white font-bold text-sm uppercase">{item.name}</h4>
                                        <button onclick={() => handleAction('toggle-hide', item.id)} class="text-[8px] font-black uppercase text-gray-600 hover:text-[#c5a059] transition-colors" title={item.is_hidden ? 'Unhide' : 'Hide'}>
                                            {item.is_hidden ? 'Unhide' : 'Hide'}
                                        </button>
                                    </div>
                                    <div class="flex justify-between items-center font-mono text-xs border-b border-[#2a231e] pb-4 mb-4">
                                        <span class="text-[#c5a059]">{(item.cost || 0).toLocaleString()} GP</span>
                                        <span class="text-white">Stock: {item.owned_quantity || 0}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button onclick={() => handleAction('buy', item.id, 1)} class="bg-[#3f6b2f]/10 border border-[#3f6b2f]/40 text-[#3f6b2f] py-3 rounded text-[9px] font-black uppercase tracking-widest hover:bg-[#3f6b2f] hover:text-white transition-all disabled:opacity-50" disabled={loading}>Purchase</button>
                                        <button onclick={() => handleAction('buy', item.id, 10)} class="bg-[#c5a059]/5 border border-[#c5a059]/20 text-gray-500 py-3 rounded text-[9px] font-black uppercase tracking-widest hover:border-[#c5a059] hover:text-[#c5a059] transition-all disabled:opacity-50" disabled={loading}>x10</button>
                                        
                                        {#if item.owned_quantity > 0}
                                            <button onclick={() => handleAction('sell', item.id, 1)} class="bg-red-900/10 border border-red-900/40 text-red-500 py-3 rounded text-[9px] font-black uppercase tracking-widest hover:bg-red-900 hover:text-white transition-all disabled:opacity-50" disabled={loading}>Sell x1</button>
                                            <button onclick={() => handleAction('sell', item.id, item.owned_quantity)} class="bg-red-900/5 border border-red-900/20 text-gray-600 py-3 rounded text-[9px] font-black uppercase tracking-widest hover:border-red-900 hover:text-red-500 transition-all disabled:opacity-50" disabled={loading}>Sell All</button>
                                        {/if}
                                    </div>
                                    {#if !item.unlocked}
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/80 rounded-2xl z-10 text-center px-4">
                                            {#if (armory_level || 0) < (item.armory_level_req || 0)}
                                                <span class="text-[10px] font-black text-white uppercase tracking-[4px]">Armory Lvl {item.armory_level_req || 0} Req</span>
                                            {:else}
                                                <span class="text-[10px] font-black text-white uppercase tracking-[4px]">Prerequisite Required</span>
                                            {/if}
                                        </div>
                                    {/if}
                                </div>
                            {/each}
                        </div>
                    </section>
                {/if}
            {/each}
        </div>
    </div>
</div>
