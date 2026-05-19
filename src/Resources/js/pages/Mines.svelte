<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let { minesConfig = {}, totalProduction = 0 } = $props();
    
    let assignQuantity = $state(1);
    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});
    const tier = $derived(kingdom.current_mine_tier || 1);
    const level = $derived(kingdom.current_mine_level || 1);
    const currentWorkers = $derived(kingdom.miners || 0);
    
    const yieldPerWorker = $derived(minesConfig?.mines?.[tier]?.[level]?.production_per_miner || 0);
    const projectedYield = $derived((currentWorkers + assignQuantity) * yieldPerWorker);

    async function handleAssignment(action) {
        loading = true;
        message = null;
        const formData = new FormData();
        formData.append('quantity', assignQuantity);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/structures/mines/${action}`, {
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
            message = { success: false, message: "Mining signal lost." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8">
    <header class="border-b border-[#2a231e] pb-6">
        <h1 class="text-4xl font-black text-white uppercase tracking-tighter">The Deep Mines</h1>
        <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Unearth the riches of the sub-strata.</p>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border bg-red-900/20 border-red-900 text-red-500">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-8">
            <h2 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">Workforce Allocation</h2>
            
            <div class="flex justify-between items-end border-b border-[#2a231e] pb-4">
                <div>
                    <span class="text-[9px] font-bold text-gray-500 uppercase block">Active Miners</span>
                    <span class="text-3xl font-black text-white font-mono">{currentWorkers.toLocaleString()}</span>
                </div>
                <div class="text-right">
                    <span class="text-[9px] font-bold text-gray-500 uppercase block">Current Yield</span>
                    <span class="text-xl font-bold text-[#c5a059] font-mono">{totalProduction.toLocaleString()} / TICK</span>
                </div>
            </div>

            <div class="space-y-6 pt-4">
                <div>
                    <label for="miner-qty" class="text-[9px] font-black text-gray-600 uppercase tracking-widest block mb-3">Citizens to Deploy</label>
                    <input id="miner-qty" type="number" bind:value={assignQuantity} min="1" class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white font-mono focus:outline-none focus:border-[#c5a059]" />
                    
                    {#if assignQuantity > 0}
                        <div class="mt-4 p-4 bg-[#1a1a1a] rounded-xl border border-[#2a231e] flex justify-between items-center">
                            <span class="text-[9px] font-black text-gray-500 uppercase">Projected Output</span>
                            <span class="text-[#3f6b2f] font-mono font-bold">+{projectedYield.toLocaleString()} GP</span>
                        </div>
                    {/if}
                </div>

                <div class="flex gap-4">
                    <button onclick={() => handleAssignment('assign')} class="flex-1 btn-primary py-4 disabled:opacity-50" disabled={loading}>Deploy</button>
                    <button onclick={() => handleAssignment('unassign')} class="flex-1 bg-red-900/20 border border-red-900/50 text-red-500 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-red-900/40 transition-all" disabled={loading}>Recall</button>
                </div>
            </div>
        </div>

        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 flex flex-col justify-between">
            <h2 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">Structural Depth</h2>
            <div class="py-12 text-center">
                 <p class="text-[#c5a059] font-black text-5xl mb-2">{level}</p>
                 <p class="text-gray-600 text-[10px] font-bold uppercase tracking-widest">Excavation Level</p>
            </div>
            <button class="w-full bg-[#2a231e] border border-[#3f3028] text-gray-400 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:text-white hover:border-[#c5a059] transition-all">
                Deeper Exploration Unavailable
            </button>
        </div>
    </div>
</div>