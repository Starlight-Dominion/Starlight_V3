<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let { units = {} } = $props();
    let quantities = $state({});
    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.kingdom || {});
    const currentUnits = $derived(Object.entries(kingdom)
        .filter(([key, val]) => key.startsWith('unit_') && val > 0)
        .map(([key, val]) => ({
            key,
            slug: key.replace('unit_', ''),
            count: val,
            name: units[key.replace('unit_', '')]?.name || key.replace('unit_', '').charAt(0).toUpperCase() + key.replace('unit_', '').slice(1)
        }))
    );

    function calculateMax(unit) {
        const byGold = unit.cost_gold > 0 ? Math.floor(resources.gold / unit.cost_gold) : Infinity;
        const byCitizens = unit.cost_citizens > 0 ? Math.floor(resources.citizens / unit.cost_citizens) : Infinity;
        const byTurns = unit.cost_turns > 0 ? Math.floor(resources.turns / unit.cost_turns) : Infinity;
        
        const max = Math.min(byGold, byCitizens, byTurns);
        return isFinite(max) ? max : 0;
    }

    async function handleTrain(type) {
        const qty = quantities[type] || 0;
        if (qty <= 0 || loading) return;

        loading = true;
        message = null;
        const formData = new FormData();
        formData.append('unit_type', type);
        formData.append('quantity', qty);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/combat/train', {
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
            message = { success: false, message: "Recruitment office signal lost." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="border-b border-[#2a231e] pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Training Grounds</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Mobilize your citizenry into specialized divisions.</p>
        </div>
        <div class="bg-black/40 border border-[#2a231e] p-4 rounded-xl max-w-xs">
            <p class="text-[9px] text-gray-500 italic leading-relaxed">
                <span class="text-[#c5a059] font-black uppercase block mb-1">Architect's Note:</span>
                Trained units are inactive (idle) until placed in the <a href="/structures/stable" class="text-white underline hover:text-[#c5a059]">Royal Stable</a>.
            </p>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-[#3f6b2f]/20 border-[#3f6b2f] text-[#3f6b2f]' : 'bg-red-900/20 border-red-900 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <!-- Barracks Inventory -->
    <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none">
            <svg class="w-24 h-24 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29L5.21 21L12 18L18.79 21L19.5 20.29L12 2Z"/></svg>
        </div>
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-xl font-black text-white uppercase tracking-tight">Barracks Inventory</h2>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-1">Personnel currently awaiting orders.</p>
            </div>
            <div class="text-right">
                <span class="text-[8px] text-gray-600 uppercase font-black block tracking-[2px]">Total Manpower</span>
                <span class="text-2xl font-black text-[#c5a059] font-mono">
                    {currentUnits.reduce((acc, u) => acc + u.count, 0).toLocaleString()}
                </span>
            </div>
        </div>

        {#if currentUnits.length > 0}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                {#each currentUnits as unit}
                    <div class="bg-black/40 border border-[#2a231e] p-4 rounded-2xl hover:border-[#c5a059]/20 transition-all">
                        <span class="text-[8px] text-gray-600 uppercase block font-black mb-1">{unit.name}</span>
                        <span class="text-lg font-black text-white font-mono">{unit.count.toLocaleString()}</span>
                    </div>
                {/each}
            </div>
        {:else}
            <div class="py-8 text-center border-2 border-dashed border-[#2a231e] rounded-3xl">
                <span class="text-[10px] text-gray-700 font-black uppercase tracking-[5px] italic">No active divisions</span>
            </div>
        {/if}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {#each Object.entries(units) as [key, unit]}
            <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-6 group hover:border-[#c5a059]/30 transition-all">
                <div class="flex justify-between items-start">
                    <h3 class="text-white font-black uppercase tracking-widest">{unit.name}</h3>
                    <div class="flex gap-2">
                        <span class="text-[8px] font-black text-red-900 uppercase bg-red-900/10 px-2 py-1 border border-red-900/20">{unit.power_offense} ATK</span>
                        <span class="text-[8px] font-black text-[#3f6b2f] uppercase bg-[#3f6b2f]/10 px-2 py-1 border border-[#3f6b2f]/20">{unit.power_defense} DEF</span>
                    </div>
                </div>

                <p class="text-gray-500 text-[10px] leading-relaxed italic">{unit.description}</p>

                <div class="bg-black/40 p-4 rounded-xl border border-[#2a231e] flex justify-between items-center font-mono text-[9px]">
                    <span class="text-gray-600 uppercase">Per Unit</span>
                    <span class="text-[#c5a059]">{unit.cost_gold}g | {unit.cost_citizens} Cit | {unit.cost_turns} T</span>
                </div>

                <div class="space-y-3">
                    <div class="flex gap-4">
                        <div class="relative flex-grow">
                            <input type="number" bind:value={quantities[key]} placeholder="Qty" class="w-full bg-black border border-[#2a231e] rounded-lg px-4 py-3 text-white font-mono focus:border-[#c5a059] focus:outline-none" />
                            <button 
                                onclick={() => quantities[key] = calculateMax(unit)}
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-[8px] font-black uppercase bg-[#c5a059]/10 text-[#c5a059] rounded hover:bg-[#c5a059] hover:text-black transition-colors"
                            >
                                Max
                            </button>
                        </div>
                        <button onclick={() => handleTrain(key)} class="px-8 btn-primary disabled:opacity-50" disabled={loading || !quantities[key] || quantities[key] <= 0}>Commission</button>
                    </div>
                    <div class="flex justify-between items-center px-1">
                        <span class="text-[8px] font-black text-gray-600 uppercase tracking-tighter">Available Capacity</span>
                        <span class="text-[10px] font-mono text-[#c5a059] font-bold">{calculateMax(unit).toLocaleString()} units</span>
                    </div>
                </div>
            </div>
        {/each}
    </div>
</div>
