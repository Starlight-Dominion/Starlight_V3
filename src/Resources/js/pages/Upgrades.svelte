<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let { housingConfig = {}, mercenaryMarketConfig = {} } = $props();

    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});

    async function handleUpgrade(type) {
        if (loading) return;
        loading = true;
        message = null;
        
        const formData = new FormData();
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/upgrades/${type}`, {
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
            message = { success: false, message: "Transmission lost." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="border-b border-[#2a231e] pb-6">
        <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Imperial Upgrades</h1>
        <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Architecture is the foundation of empire.</p>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-[#3f6b2f]/20 border-[#3f6b2f] text-[#3f6b2f]' : 'bg-red-900/20 border-red-900 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- HOUSING -->
        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-6">
            <div class="flex justify-between items-start">
                <h2 class="text-[#c5a059] text-[10px] font-black uppercase tracking-[4px]">Great Housing</h2>
                <span class="text-white font-mono text-xs">Level {kingdom.housing_level || 1} / {housingConfig.max_level || 5}</span>
            </div>
            
            <div class="py-10 text-center">
                <span class="block text-[8px] text-gray-600 font-black uppercase mb-2">Current Growth</span>
                <span class="text-5xl font-black text-white">{housingConfig.levels?.[kingdom.housing_level]?.citizens_per_tick || 0}</span>
                <span class="block text-[10px] text-[#3f6b2f] font-bold uppercase mt-2">Citizens / Tick</span>
            </div>

            {#if (kingdom.housing_level || 1) < (housingConfig.max_level || 5)}
                {@const next = housingConfig.levels[kingdom.housing_level + 1]}
                <button onclick={() => handleUpgrade('housing')} class="w-full btn-primary py-5 disabled:opacity-50" disabled={loading}>
                    Upgrade Housing ({next.cost.toLocaleString()} GP)
                </button>
            {:else}
                <div class="w-full bg-[#1a1a1a] text-center py-5 border border-[#2a231e] text-gray-500 text-[10px] font-black uppercase tracking-widest">Max Efficiency</div>
            {/if}
        </div>

        <!-- MERCENARIES -->
        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-6">
            <h2 class="text-red-900 text-[10px] font-black uppercase tracking-[4px]">Mercenary Market</h2>
            <div class="space-y-4">
                <p class="text-gray-500 text-xs italic leading-relaxed">Instantly surge your troop counts for gold.</p>
                <div class="bg-black/40 border border-[#2a231e] p-4 rounded-xl text-center">
                    <span class="text-white font-bold block">{kingdom.mercenary_market_level || 0}</span>
                    <span class="text-[8px] text-gray-600 uppercase font-black">Market Tier</span>
                </div>
            </div>

            {#if (kingdom.mercenary_market_level || 0) < (mercenaryMarketConfig.max_level || 5)}
                {@const next = mercenaryMarketConfig.levels[kingdom.mercenary_market_level + 1]}
                <button onclick={() => handleUpgrade('mercenary-market')} class="w-full bg-[#8b0000]/10 border border-[#8b0000]/40 text-red-500 py-5 rounded font-black text-xs uppercase tracking-widest hover:bg-[#8b0000]/20 transition-all disabled:opacity-50" disabled={loading}>
                    Enlist ({next.cost.toLocaleString()} GP)
                </button>
            {/if}
        </div>
    </div>
</div>