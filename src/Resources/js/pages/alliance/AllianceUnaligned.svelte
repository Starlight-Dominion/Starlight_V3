<script>
    import { onMount } from 'svelte';
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceList from './AllianceList.svelte';
    import AllianceCreate from './AllianceCreate.svelte';
    import AllianceDetail from './AllianceDetail.svelte';

    let { error = null } = $props();
    let view = $state('unaligned');
    let loading = $state(false);
    let payload = $state({ alliances: [] });

    async function fetchPayload(targetView = 'list', params = {}) {
        loading = true;
        let url = '/api/alliance/list';
        if (targetView === 'detail') url = `/api/alliance/detail/${params.id}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            payload = data;
            view = targetView;
        } catch (e) {
            console.error("Manifest uplink unstable.");
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advice={error || "Collective Unaligned. Found a new order or enlist in an existing command."} />
    </aside>

    <main class="lg:col-span-3">
        {#if loading}
            <div class="h-64 flex items-center justify-center opacity-20 italic uppercase tracking-widest">
                Scanning Sector Manifests...
            </div>
        {:else}
            {#if view === 'unaligned'}
                <div class="space-y-6">
                    <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
                        <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Collective Unaligned</h1>
                        <p class="text-gray-500 text-[9px] font-bold uppercase tracking-[4px] mt-1 italic">Found a new order or enlist in an existing command.</p>
                    </header>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button onclick={() => fetchPayload('list')} class="bg-gray-900/40 border border-white/5 p-8 rounded-lg text-center hover:bg-cyan-500/5 transition-all group">
                            <h3 class="font-title text-xl text-white uppercase group-hover:text-cyan-400 transition-colors">Search Manifest</h3>
                            <p class="text-[10px] text-gray-500 mt-2 uppercase">Find an existing alliance.</p>
                        </button>
                        <button onclick={() => view = 'create'} class="bg-gray-900/40 border border-white/5 p-8 rounded-lg text-center hover:bg-emerald-500/5 transition-all group">
                            <h3 class="font-title text-xl text-white uppercase group-hover:text-emerald-400 transition-colors">Found Order</h3>
                            <p class="text-[10px] text-gray-500 mt-2 uppercase">Establish your own command. Cost: 1M CP</p>
                        </button>
                    </div>
                </div>
            {:else if view === 'list'}
                <AllianceList {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'create'}
                <AllianceCreate onNavigate={() => window.location.href = '/alliance'} />
            {:else if view === 'detail'}
                <AllianceDetail {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {/if}
        {/if}
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
