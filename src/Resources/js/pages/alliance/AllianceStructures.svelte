<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceSidebar from '../../components/AllianceSidebar.svelte';

    let { payload = {} } = $props();
    let processing = $state(false);
    let message = $state(null);

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    async function purchase(key) {
        processing = true;
        message = null;

        try {
            const formData = new FormData();
            formData.append('structure_key', key);
            formData.append('_csrf', game.csrf);
            
            const res = await fetch('/api/alliance/structures/purchase', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                message = { type: 'success', text: data.message };
                setTimeout(() => window.location.reload(), 1500);
            } else {
                message = { type: 'error', text: data.error || data.message };
            }
        } catch (e) {
            message = { type: 'error', text: 'Structure Deployment Failed.' };
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advisor={payload?.advisor} />
        <AllianceSidebar active="structures" {payload} />
    </aside>

    <main class="lg:col-span-3 space-y-6">
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Strategic Assets</h1>
                <p class="text-emerald-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Collective infrastructure and tactical enhancements.</p>
            </div>
            <div class="text-right">
                <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block">Available Treasury</span>
                <span class="text-2xl font-mono text-cyan-400 font-bold">{formatNumber(payload.bank_credits || 0)} <span class="text-[10px] opacity-40">CP</span></span>
            </div>
        </header>

        {#if message}
            <div class="p-4 rounded border text-center text-[10px] font-black uppercase tracking-widest {message.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/50 text-emerald-400' : 'bg-red-500/10 border-red-500/50 text-red-400'}">
                {message.text}
            </div>
        {/if}

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {#each payload.slots || [] as slot}
                <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm flex flex-col">
                    <header class="bg-black/40 px-4 py-3 border-b border-white/5 flex justify-between items-center">
                        <h3 class="text-[10px] font-black text-white uppercase tracking-[2px]">{slot.title} Track</h3>
                        <span class="text-[10px] font-mono text-gray-500 font-bold">{slot.current_level} / {slot.max_level}</span>
                    </header>
                    
                    <div class="p-6 flex-grow space-y-4">
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all duration-1000" style="width: {(slot.current_level / slot.max_level) * 100}%"></div>
                        </div>

                        {#if slot.is_maxed}
                            <div class="py-8 text-center">
                                <span class="text-[10px] font-black text-emerald-500 uppercase tracking-[4px]">System Fully Optimized</span>
                            </div>
                        {:else if slot.next}
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-xs font-title font-black text-white uppercase">{slot.next.name}</h4>
                                    <p class="text-[9px] text-gray-500 uppercase mt-1 leading-relaxed">{slot.next.description}</p>
                                </div>
                                
                                <div class="bg-black/40 p-3 rounded border border-white/5">
                                    <span class="text-[8px] font-black text-gray-600 uppercase block">Requisition Cost</span>
                                    <span class="text-sm font-mono {slot.next.can_afford ? 'text-white' : 'text-red-500'} font-bold">{formatNumber(slot.next.cost)} CP</span>
                                </div>

                                <button 
                                    onclick={() => purchase(slot.next.key)}
                                    disabled={processing || !slot.next.can_afford || !payload.can_purchase_structures}
                                    class="w-full bg-emerald-500/10 border border-emerald-500/50 py-3 text-[10px] font-black text-emerald-400 uppercase tracking-widest hover:bg-emerald-500/30 transition-all disabled:opacity-50"
                                >
                                    {processing ? 'INITIALIZING...' : 'AUTHORIZE DEPLOYMENT'}
                                </button>
                            </div>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
