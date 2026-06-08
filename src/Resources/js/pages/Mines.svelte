<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    import AiAdvisor from '../components/AiAdvisor.svelte';
    
    let { minesConfig = {}, totalProduction = 0 } = $props();
    
    let assignQuantity = $state(0);
    let loading = $state(false);
    let message = $state(null);

    const kingdom = $derived(game.user?.dominion || {});
    const tier = $derived(kingdom.current_mine_tier || 1);
    const level = $derived(kingdom.current_mine_level || 1);
    const currentWorkers = $derived(kingdom.miners || 0);
    
    const yieldPerWorker = $derived(minesConfig?.mines?.[tier]?.[level]?.production_per_miner || 0);
    const projectedYield = $derived((currentWorkers + assignQuantity) * yieldPerWorker);

    async function handleAssignment(action) {
        if (loading || assignQuantity <= 0) return;
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

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    
    <!-- SIDEBAR ADVISOR -->
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor 
            stats={[
                { label: 'Active Miners', value: currentWorkers },
                { label: 'Current Yield', value: totalProduction, suffix: 'CP/TICK', highlight: true }
            ]}
        />
    </aside>

    <!-- MAIN MINES CONTENT -->
    <main class="lg:col-span-3 space-y-4">
        
        <!-- Header Panel -->
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-6xl font-title font-black text-white italic">DEPTH_CORE</span>
            </div>
            <div class="relative z-10">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">The Deep Mines</h1>
                <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Unearth the planetary core's untapped wealth.</p>
            </div>
        </header>

        {#if message}
            <div in:slide class="p-3 rounded border text-[10px] font-black uppercase text-center {message.success ? 'bg-cyan-950/40 border-cyan-500/50 text-cyan-400' : 'bg-red-950/40 border-red-500/50 text-red-500'}">
                {message.message}
            </div>
        {/if}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Workforce Panel -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-6 space-y-6 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-title text-cyan-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-cyan-500 rounded-full mr-2 shadow-[0_0_5px_#00ffff]"></span>
                        Workforce Allocation
                    </h3>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block">Active Miners</span>
                        <span class="text-2xl font-mono font-bold text-white">{formatNumber(currentWorkers)}</span>
                    </div>
                    <div class="space-y-1 text-right">
                        <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block">Current Yield</span>
                        <span class="text-xl font-mono font-bold text-emerald-500">+{formatNumber(totalProduction)} <span class="text-[8px] opacity-40 uppercase">CP/T</span></span>
                    </div>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/5">
                    <div class="space-y-2">
                        <label for="miner-qty" class="text-[9px] font-black text-gray-600 uppercase tracking-widest block">Deployment Magnitude</label>
                        <input 
                            id="miner-qty" 
                            type="number" 
                            bind:value={assignQuantity} 
                            min="0" 
                            class="w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-xl font-mono text-cyan-400 focus:outline-none focus:border-cyan-500 transition-all"
                            placeholder="0"
                        />
                    </div>

                    {#if assignQuantity > 0}
                        <div in:slide class="p-3 bg-cyan-900/10 border border-cyan-500/10 rounded flex justify-between items-center">
                            <span class="text-[8px] font-black text-cyan-700 uppercase tracking-widest">Projected Output</span>
                            <span class="text-emerald-500 font-mono font-bold text-sm">+{formatNumber(projectedYield)} CP/TICK</span>
                        </div>
                    {/if}

                    <div class="flex gap-3 pt-2">
                        <button 
                            onclick={() => handleAssignment('assign')} 
                            class="flex-1 bg-cyan-900/30 hover:bg-cyan-900/50 border border-cyan-500/30 text-cyan-400 font-title font-black py-3 rounded uppercase text-[10px] tracking-[2px] transition-all disabled:opacity-20 shadow-[0_0_10px_rgba(6,182,212,0.2)]"
                            disabled={loading || assignQuantity <= 0 || assignQuantity > resources.citizens}
                        >Deploy</button>
                        <button 
                            onclick={() => handleAssignment('unassign')} 
                            class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-400 font-title font-black py-3 rounded uppercase text-[10px] tracking-[2px] transition-all disabled:opacity-20"
                            disabled={loading || assignQuantity <= 0 || assignQuantity > currentWorkers}
                        >Recall</button>
                    </div>
                </div>
            </div>

            <!-- Structural Panel -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-6 flex flex-col backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-6">
                    <h3 class="font-title text-gray-400 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-gray-600 rounded-full mr-2"></span>
                        Structural Depth
                    </h3>
                </div>

                <div class="flex-grow flex flex-col items-center justify-center py-8 space-y-2">
                    <span class="text-7xl font-title font-black text-white/90 italic tracking-tighter text-shadow-glow">{level}</span>
                    <span class="text-[10px] font-black text-cyan-600 uppercase tracking-[4px]">Excavation Rank</span>
                </div>

                <div class="mt-6">
                    <button 
                        class="w-full bg-white/5 border border-white/10 text-gray-600 py-4 rounded-lg text-[9px] font-black uppercase tracking-[3px] cursor-not-allowed opacity-40"
                        disabled
                    >
                        Deeper Exploration Restricted
                    </button>
                    <p class="text-[8px] text-gray-700 text-center mt-3 uppercase tracking-tighter italic">Tier {tier} protocol active. Planetary limits reached.</p>
                </div>
            </div>

        </div>

        <footer class="text-center pt-8">
            <p class="text-gray-600 text-[9px] font-bold uppercase tracking-[4px] italic">
                Raw minerals are synthesized into Command Points through the global network. 
                <a href="/structures/upgrades" class="text-cyan-500 underline hover:text-white transition-colors">Improve Logistics &raquo;</a>
            </p>
        </footer>

    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
    .text-shadow-glow {
        text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
    }
</style>
