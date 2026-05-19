<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let { spyCount = 0 } = $props();
    
    let selectedTarget = $state("");
    let loading = $state(false);
    let report = $state(null);

    const players = $derived(game.props?.players || []);

    async function executeRecon() {
        if (!selectedTarget || loading) return;
        loading = true;
        report = null;
        
        const formData = new FormData();
        formData.append('target_id', selectedTarget);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/spy/reconnaissance/execute', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                report = data;
                const saved = JSON.parse(localStorage.getItem('shadow_intel') || '{}');
                saved[selectedTarget] = {
                    gold: data.intel_gained.gold,
                    total_army: data.intel_gained.army.total,
                    ts: Date.now()
                };
                localStorage.setItem('shadow_intel', JSON.stringify(saved));
            }
            loading = false;
        } catch (e) {
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="border-b border-[#2a231e] pb-6 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Espionage Hub</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Information is the sharpest blade.</p>
        </div>
        <div class="bg-black border border-[#2a231e] px-6 py-3 rounded-xl text-center">
            <span class="text-[8px] text-gray-600 font-black uppercase block">Active Spies</span>
            <span class="text-xl font-bold text-[#c5a059] font-mono">{spyCount}</span>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-6">
            <h2 class="text-white text-[10px] font-black uppercase tracking-[4px]">Deploy Recon</h2>
            
            <div class="space-y-4">
                <select bind:value={selectedTarget} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059]">
                    <option value="">Select Target...</option>
                    {#each players as p}
                        <option value={p.kingdom_id}>{p.username} ({p.name})</option>
                    {/each}
                </select>

                <button onclick={executeRecon} class="w-full btn-primary py-4 disabled:opacity-50" disabled={loading || spyCount <= 0 || !selectedTarget}>
                    {loading ? 'Infiltrating...' : 'Launch Infiltration'}
                </button>
            </div>
        </div>

        <div class="lg:col-span-2 bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 relative overflow-hidden">
            {#if report}
                <div in:fade class="space-y-6">
                    <h2 class="text-[#3f6b2f] text-[10px] font-black uppercase tracking-[4px]">Report: {report.intel_gained.kingdom_name}</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-black/40 p-4 rounded-xl border border-[#2a231e] text-center">
                            <span class="text-[8px] text-gray-600 uppercase block">Treasury</span>
                            <span class="text-white font-mono font-bold">{report.intel_gained.gold.toLocaleString()} GP</span>
                        </div>
                        <div class="bg-black/40 p-4 rounded-xl border border-[#2a231e] text-center">
                            <span class="text-[8px] text-gray-600 uppercase block">Total Army</span>
                            <span class="text-white font-mono font-bold">{report.intel_gained.army.total.toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            {:else}
                <div class="h-full flex items-center justify-center py-20 opacity-20">
                    <p class="text-xs font-black uppercase tracking-[5px]">Awaiting Data Transmission</p>
                </div>
            {/if}
        </div>
    </div>
</div>