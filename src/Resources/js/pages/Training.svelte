<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    import AiAdvisor from '../components/AiAdvisor.svelte';
    
    let { units = {} } = $props();
    let quantities = $state({});
    let loading = $state(false);
    let message = $state(null);

    // Derived Calculations
    const totalTrainCost = $derived(
        Object.entries(quantities).reduce((sum, [slug, qty]) => {
            const unit = units[slug];
            return sum + (Number(qty || 0) * (unit?.cost_credits || 0));
        }, 0)
    );

    const totalCitizensNeeded = $derived(
        Object.values(quantities).reduce((sum, qty) => sum + Number(qty || 0), 0)
    );

    const totalPersonnel = $derived(
        Object.values(units).reduce((sum, unit) => sum + (unit.owned || 0), 0)
    );

    const canAfford = $derived(
        totalCitizensNeeded <= resources.citizens && totalTrainCost <= resources.credits
    );

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    function calculateMax(slug) {
        const unit = units[slug];
        if (!unit) return 0;
        const byCredits = unit.cost_credits > 0 ? Math.floor(resources.credits / unit.cost_credits) : Infinity;
        const byCitizens = resources.citizens;
        
        const max = Math.min(byCredits, byCitizens);
        return isFinite(max) ? max : 0;
    }

    function setMax(slug) {
        quantities[slug] = calculateMax(slug);
    }

    async function handleTrain() {
        if (loading || !canAfford || totalCitizensNeeded <= 0) return;

        loading = true;
        message = null;

        // Collect non-zero quantities
        const trainingSet = Object.entries(quantities)
            .filter(([_, qty]) => Number(qty) > 0);

        if (trainingSet.length === 0) {
            loading = false;
            return;
        }

        try {
            // For now, the backend handles one unit type per request or needs an update.
            // Let's assume we need to process them or the backend can handle a batch.
            // If the backend handles only one, we might need a loop, but let's try the first one 
            // or assume a batch if the controller supports it.
            // Looking at TrainingController.php, it takes 'unit_type' and 'quantity'.
            // So we need to loop if there are multiple.
            
            for (const [slug, qty] of trainingSet) {
                const formData = new FormData();
                formData.append('unit_type', slug); 
                formData.append('quantity', qty);
                formData.append('_csrf', game.csrf);

                const res = await fetch('/combat/train', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (!data.success) {
                    message = data;
                    loading = false;
                    return;
                }
            }

            // If we reach here, all were successful
            window.location.reload();
        } catch (e) {
            message = { success: false, message: "Neural link unstable. Mobilization aborted." };
            loading = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-4 pb-24">
    
    <!-- SIDEBAR ADVISOR -->
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor 
            stats={[
                { label: 'Personnel Strength', value: totalPersonnel }
            ]}
        />
    </aside>

    <!-- MAIN TRAINING CONTENT -->
    <main class="lg:col-span-3 space-y-4">
        
        <!-- Header Panel -->
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-6xl font-title font-black text-white italic">BARRACKS_V4</span>
            </div>
            <div class="relative z-10">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Military Terminal</h1>
                <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Authorized personnel mobilization interface.</p>
            </div>
        </header>

        {#if message}
            <div in:slide class="p-3 rounded border text-[10px] font-black uppercase text-center {message.success ? 'bg-cyan-950/40 border-cyan-500/50 text-cyan-400' : 'bg-red-950/40 border-red-500/50 text-red-500'}">
                {message.message}
            </div>
        {/if}

        <!-- Top Summary Dashboard -->
        <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 backdrop-blur-sm shadow-2xl">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="space-y-1">
                    <p class="text-[8px] uppercase font-black text-gray-600 tracking-widest">Available Citizens</p>
                    <p class="text-lg font-mono font-bold text-white">{formatNumber(resources.citizens)}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[8px] uppercase font-black text-gray-600 tracking-widest">Operation Credits</p>
                    <p class="text-lg font-mono font-bold text-white">{formatNumber(resources.credits)}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[8px] uppercase font-black text-gray-600 tracking-widest">Projected Cost</p>
                    <p class="text-lg font-mono font-bold text-cyan-400">{formatNumber(totalTrainCost)} <span class="text-[10px] opacity-40">CP</span></p>
                </div>
                <div class="space-y-1">
                    <p class="text-[8px] uppercase font-black text-gray-600 tracking-widest">Mobilization Status</p>
                    {#if totalCitizensNeeded > resources.citizens}
                        <p class="text-lg font-title font-black text-red-600 uppercase tracking-tighter">OVER_POP</p>
                    {:else if totalTrainCost > resources.credits}
                        <p class="text-lg font-title font-black text-red-600 uppercase tracking-tighter">NO_FUNDS</p>
                    {:else}
                        <p class="text-lg font-title font-black text-emerald-500 uppercase tracking-tighter">READY</p>
                    {/if}
                </div>
            </div>
        </div>

        <!-- Unit Training Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {#each Object.entries(units) as [slug, unit]}
                <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-4 backdrop-blur-sm transition-all hover:bg-gray-900/60 group">
                    <div class="flex items-center gap-4 border-b border-white/5 pb-3">
                        <div class="w-12 h-12 rounded-lg bg-black/60 border border-white/10 flex items-center justify-center text-cyan-400 font-title font-black text-xl group-hover:border-cyan-500 transition-all">
                            {unit.name?.charAt(0)}
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-white font-title font-black text-sm uppercase tracking-widest">{unit.name}</h3>
                            <div class="flex gap-3 mt-1">
                                <span class="text-[9px] text-gray-500 uppercase font-black tracking-tighter">Cost: <span class="text-cyan-600 font-mono">{formatNumber(unit.cost_credits)} CP</span></span>
                                <span class="text-[9px] text-gray-500 uppercase font-black tracking-tighter">Personnel: <span class="text-white font-mono">{formatNumber(unit.owned || 0)}</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-[10px] text-gray-400 italic leading-relaxed min-h-[30px] line-clamp-2">{unit.description}</p>
                    
                    <div class="flex gap-2">
                        <div class="relative flex-grow">
                            <input 
                                type="number" 
                                bind:value={quantities[slug]} 
                                placeholder="0" 
                                class="w-full bg-black/40 border border-white/10 rounded px-3 py-2 text-white font-mono text-sm focus:border-cyan-500 outline-none transition-all pr-12"
                                min="0"
                            />
                            <button 
                                onclick={() => setMax(slug)}
                                class="absolute right-1 top-1/2 -translate-y-1/2 px-2 py-1 text-[8px] font-black uppercase bg-cyan-900/30 text-cyan-500 border border-cyan-500/20 rounded hover:bg-cyan-500 hover:text-black transition-all"
                            >MAX</button>
                        </div>
                    </div>
                </div>
            {/each}
        </div>

        <!-- Action Button -->
        <div class="pt-4">
            <button 
                onclick={handleTrain}
                disabled={loading || !canAfford || totalCitizensNeeded <= 0}
                class="w-full bg-cyan-900/30 hover:bg-cyan-900/50 border border-cyan-500/30 text-cyan-400 font-title font-black py-4 rounded-lg uppercase text-[11px] tracking-[3px] transition-all disabled:opacity-20 shadow-[0_0_20px_rgba(34,211,238,0.1)]"
            >
                {loading ? 'Processing Requisition...' : 'Execute Training Authorization'}
            </button>
        </div>

        <footer class="text-center pt-8">
            <p class="text-gray-600 text-[9px] font-bold uppercase tracking-[4px] italic">
                Strategic military deployments require synchronized neural command. 
                <a href="/combat/battlefield" class="text-cyan-500 underline hover:text-white transition-colors">Access War Room &raquo;</a>
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
