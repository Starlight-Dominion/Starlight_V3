<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { player_level = 1, currentTier = {}, nextTier = null, allTiers = {}, upgrades = {} } = $props();

    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});

    async function handleAction(action, key = '') {
        if (loading) return;
        loading = true;
        message = null;
        
        const formData = new FormData();
        if (key) formData.append('upgrade_key', key);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/foundation/${action}`, {
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
            message = { success: false, message: "Masons lost contact." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-12 pb-24">
    <header class="border-b border-[#2a231e] pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">The Foundation</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">The bedrock upon which empires are built.</p>
        </div>

        <div class="bg-[#0f0f0f] border border-[#2a231e] p-4 rounded-xl flex items-center gap-6">
            <div>
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Sovereign Level</span>
                <span class="text-xl font-black text-[#c5a059]">LEVEL {player_level}</span>
            </div>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-[#3f6b2f]/20 border-[#3f6b2f] text-[#3f6b2f]' : 'bg-red-900/20 border-red-900 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Tier Display -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-[#0f0f0f] border border-[#2a231e] p-10 rounded-3xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-6">
                    <span class="text-[60px] font-black text-white/5 uppercase select-none leading-none">{currentTier?.id || 0}</span>
                </div>

                <div class="relative z-10 space-y-6">
                    <h2 class="text-xs font-black text-gray-500 uppercase tracking-[4px]">Current Fortification</h2>
                    <div>
                        <h3 class="text-5xl font-black text-white uppercase tracking-tight">{currentTier?.name || 'Vulnerable Earth'}</h3>
                        <p class="text-[#c5a059] font-bold text-xs uppercase tracking-widest mt-2">{currentTier?.description || 'No formal foundation established.'}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-8 pt-6 border-t border-[#2a231e]">
                        <div>
                            <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Integrity (HP)</span>
                            <span class="text-2xl font-black text-white font-mono">{kingdom.foundation_hp?.toLocaleString() || 0}</span>
                        </div>
                        <div>
                            <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Upgrade Slot</span>
                            <span class="text-lg font-bold text-white uppercase">{kingdom.foundation_upgrade_slot_1 ? upgrades[kingdom.foundation_upgrade_slot_1].name : 'Empty'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upgrade Section -->
            {#if nextTier}
                <div class="bg-[#1a1a1a]/40 border border-dashed border-[#2a231e] p-8 rounded-3xl flex flex-col md:flex-row justify-between items-center gap-8">
                    <div class="space-y-2 text-center md:text-left">
                        <span class="text-[10px] font-black text-[#c5a059] uppercase tracking-[3px]">Next Tier: Level {currentTier ? (currentTier.id + 1) : 1}</span>
                        <h4 class="text-2xl font-black text-white uppercase">{nextTier.name}</h4>
                        <p class="text-gray-500 text-[10px]">{nextTier.description}</p>
                    </div>

                    <div class="flex flex-col items-center md:items-end gap-3">
                        <div class="flex items-center gap-4">
                            {#if player_level < nextTier.player_level_req}
                                <span class="text-[10px] font-black text-red-900 uppercase tracking-widest bg-red-900/10 px-4 py-2 rounded">Req Level {nextTier.player_level_req}</span>
                            {/if}
                            <button 
                                onclick={() => handleAction('upgrade')} 
                                class="btn-primary px-10 py-4 text-xs disabled:opacity-50"
                                disabled={loading || player_level < nextTier.player_level_req || resources.gold < nextTier.cost}
                            >
                                Evolve Foundation
                            </button>
                        </div>
                        <span class="text-[10px] font-mono text-gray-600 font-bold">{nextTier.cost.toLocaleString()} GP</span>
                    </div>
                </div>
            {:else}
                <div class="bg-[#3f6b2f]/10 border border-[#3f6b2f]/30 p-10 rounded-3xl text-center">
                    <span class="text-xl font-black text-[#3f6b2f] uppercase tracking-widest">Ascension Complete: Adamantine Citadel Reached</span>
                </div>
            {/if}
        </div>

        <!-- Sidebar: Modifications & Tiers List -->
        <div class="space-y-8">
            <div class="bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl">
                <h2 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px] mb-8">Modification</h2>
                
                {#if kingdom.foundation_upgrade_slot_1}
                    {@const activeUpgrade = upgrades[kingdom.foundation_upgrade_slot_1]}
                    <div class="bg-[#c5a059]/5 border border-[#c5a059]/30 p-6 rounded-2xl text-center space-y-4">
                        <div class="text-[#c5a059] text-3xl">◈</div>
                        <h3 class="text-white font-black uppercase text-xs tracking-widest">{activeUpgrade.name}</h3>
                        <p class="text-gray-500 text-[9px] leading-relaxed italic">{activeUpgrade.description}</p>
                    </div>
                {:else}
                    <div class="space-y-3">
                        {#each Object.entries(upgrades) as [key, upgrade]}
                            <button 
                                onclick={() => handleAction('purchase-upgrade', key)} 
                                class="w-full bg-black/40 border border-[#2a231e] p-4 rounded-xl text-left hover:border-[#c5a059] transition-all group disabled:opacity-30"
                                disabled={loading || !currentTier}
                            >
                                <div class="flex justify-between items-start">
                                    <h3 class="text-white font-bold uppercase text-[10px] group-hover:text-[#c5a059]">{upgrade.name}</h3>
                                    <span class="text-[9px] font-mono text-gray-600">{( (currentTier?.cost || 0) * upgrade.cost_multiplier ).toLocaleString()} GP</span>
                                </div>
                                <p class="text-[9px] text-gray-600 mt-1">{upgrade.description}</p>
                            </button>
                        {/each}
                    </div>
                {/if}
            </div>

            <!-- Tier Progress List -->
            <div class="bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl">
                <h2 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px] mb-6">Evolution Path</h2>
                <div class="space-y-4">
                    {#each Object.entries(allTiers) as [tid, tier]}
                        <div class="flex items-center gap-4 {parseInt(tid) <= (currentTier?.id || 0) ? 'opacity-100' : 'opacity-30'}">
                            <div class="w-2 h-2 rounded-full {parseInt(tid) <= (currentTier?.id || 0) ? 'bg-[#3f6b2f]' : 'bg-[#2a231e]'}"></div>
                            <div class="flex-grow">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-white uppercase">{tier.name}</span>
                                    <span class="text-[8px] font-black text-gray-600">LVL {tier.player_level_req}</span>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        </div>
    </div>
</div>
