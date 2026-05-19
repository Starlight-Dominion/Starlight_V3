<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { stableData = {}, unitDetails = {} } = $props();

    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});

    async function handleAction(action, type = null, quantity = 1) {
        if (loading) return;
        loading = true;
        message = null;
        
        const formData = new FormData();
        if (type) formData.append('unit_type', type);
        formData.append('quantity', quantity);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/stable/${action}`, {
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
            message = { success: false, message: "Stable transmission failed." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-24">
    <header class="border-b border-[#2a231e] pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">The Royal Stable</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Elite units maintained for heavy deployment.</p>
        </div>

        <div class="bg-[#0f0f0f] border border-[#2a231e] p-4 rounded-xl flex items-center gap-6">
            <div>
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Structure Rank</span>
                <span class="text-xl font-black text-[#c5a059]">LEVEL {stableData.stable_level}</span>
            </div>
            {#if stableData.upgrade_cost}
                <div class="border-l border-[#2a231e] pl-6 flex flex-col gap-1">
                    <button 
                        onclick={() => handleAction('upgrade', null, 0)} 
                        class="bg-[#c5a059] text-black px-4 py-2 rounded font-black text-[9px] uppercase tracking-widest hover:bg-white transition-all disabled:opacity-50"
                        disabled={loading || resources.gold < stableData.upgrade_cost || stableData.stable_level >= stableData.max_stable_level}
                    >
                        Upgrade Rank
                    </button>
                    <span class="text-[8px] font-bold text-gray-500 uppercase tracking-tighter text-center">{stableData.upgrade_cost.toLocaleString()} GP</span>
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar: Stats -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-6">
                <h2 class="text-white text-[10px] font-black uppercase tracking-[4px]">Active Capacity</h2>
                <div class="py-6 text-center">
                    <span class="text-5xl font-black text-white">{stableData.total_stabled.toLocaleString()}</span>
                    <span class="block text-[10px] text-gray-600 font-bold uppercase mt-2">Units / {stableData.current_capacity.toLocaleString()}</span>
                </div>
                <div class="h-2 w-full bg-black rounded-full overflow-hidden border border-[#2a231e]">
                    <div class="h-full bg-[#c5a059] transition-all duration-1000" style="width: {(stableData.total_stabled / stableData.current_capacity) * 100}%"></div>
                </div>
                <div class="pt-4 border-t border-[#2a231e]">
                    <div class="flex justify-between items-center">
                        <span class="text-[8px] font-black text-gray-600 uppercase">Available Slots</span>
                        <span class="text-xs font-mono text-[#3f6b2f] font-bold">{stableData.available_capacity.toLocaleString()}</span>
                    </div>
                </div>
            </div>

            <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8">
                <h2 class="text-white text-[10px] font-black uppercase tracking-[4px] mb-6">Stable Upkeep</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-[8px] font-black text-gray-600 uppercase">Per Tick</span>
                        <span class="text-sm font-mono text-red-900 font-bold">-{stableData.maintenance_cost.toLocaleString()} GP</span>
                    </div>
                    <p class="text-[9px] text-gray-500 italic leading-relaxed">Only stabled units contribute to kingdom power and incur maintenance costs.</p>
                </div>
            </div>
        </div>

        <!-- Unit Management -->
        <div class="lg:col-span-3 space-y-6">
            {#each Object.entries(unitDetails) as [type, detail]}
                <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 flex flex-col md:flex-row items-center justify-between gap-8 group hover:border-[#c5a059]/30 transition-all relative overflow-hidden">
                    <div class="flex items-center gap-8 z-10">
                        <div class="w-16 h-16 bg-black border border-[#2a231e] rounded-2xl flex items-center justify-center text-[#c5a059] text-2xl font-black uppercase">
                            {type.charAt(0)}
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-xl font-black text-white uppercase tracking-tight">{detail.name}</h3>
                            <div class="flex gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-[8px] font-black text-gray-600 uppercase">Active</span>
                                    <span class="text-xs font-mono text-white font-bold">{stableData.stabled_unit_counts[type].toLocaleString()}</span>
                                </div>
                                <div class="flex items-center gap-2 border-l border-[#2a231e] pl-4">
                                    <span class="text-[8px] font-black text-gray-600 uppercase">Idle</span>
                                    <span class="text-xs font-mono text-gray-500">{stableData.idle_unit_counts[type].toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center md:items-end gap-3 z-10">
                        <div class="flex gap-2">
                            <button 
                                onclick={() => handleAction('stable-unit', type, 1)} 
                                class="bg-[#3f6b2f]/10 border border-[#3f6b2f]/30 text-[#3f6b2f] px-6 py-3 rounded font-black text-[9px] uppercase tracking-widest hover:bg-[#3f6b2f] hover:text-white transition-all disabled:opacity-50" 
                                disabled={loading || stableData.idle_unit_counts[type] < 1 || stableData.available_capacity < 1}
                            >
                                Stable x1
                            </button>
                            <button 
                                onclick={() => handleAction('stable-unit', type, Math.min(stableData.idle_unit_counts[type], stableData.available_capacity))} 
                                class="bg-[#c5a059]/10 border border-[#c5a059]/30 text-[#c5a059] px-6 py-3 rounded font-black text-[9px] uppercase tracking-widest hover:bg-[#c5a059] hover:text-black transition-all disabled:opacity-50" 
                                disabled={loading || stableData.idle_unit_counts[type] < 1 || stableData.available_capacity < 1}
                            >
                                Stable All
                            </button>
                        </div>
                        {#if stableData.available_capacity < 1 && stableData.idle_unit_counts[type] > 0}
                            <span class="text-[8px] font-black text-red-900 uppercase">Insufficient Capacity</span>
                        {/if}
                    </div>
                </div>
            {/each}

            {#if Object.values(stableData.idle_unit_counts).every(c => c === 0)}
                <div class="bg-black/40 border border-dashed border-[#2a231e] p-12 rounded-3xl text-center">
                    <p class="text-gray-600 font-bold uppercase tracking-widest text-[10px]">No idle units available to stable.</p>
                    <a href="/combat/training" class="inline-block mt-6 text-[#c5a059] font-black uppercase text-[10px] border-b border-[#c5a059]/50 pb-1 hover:text-white hover:border-white transition-all">Visit Training Grounds &rarr;</a>
                </div>
            {/if}
        </div>
    </div>
</div>
