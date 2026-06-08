<script>
    import { onMount } from 'svelte';
    import { fade, slide } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    
    // Components
    import AllianceHub from './AllianceHub.svelte';
    import AllianceList from './AllianceList.svelte';
    import AllianceDetail from './AllianceDetail.svelte';
    import AllianceCreate from './AllianceCreate.svelte';
    import AllianceBank from './AllianceBank.svelte';
    import AllianceStructures from './AllianceStructures.svelte';
    import AllianceForum from './AllianceForum.svelte';
    import AllianceThread from './AllianceThread.svelte';
    import AllianceManagement from './AllianceManagement.svelte';

    let { view: initialView = 'hub' } = $props();
    let view = $state(initialView);
    let loading = $state(true);
    let payload = $state({});
    let detailId = $state(null);
    let threadId = $state(null);

    async function fetchPayload(targetView = 'hub', params = {}) {
        loading = true;
        let url = '/api/alliance/hub';
        if (targetView === 'list') url = '/api/alliance/list';
        if (targetView === 'detail') url = `/api/alliance/detail/${params.id}`;
        if (targetView === 'bank') url = '/api/alliance/bank';
        if (targetView === 'structures') url = '/api/alliance/structures';
        if (targetView === 'forum') url = '/api/alliance/forum';
        if (targetView === 'thread') url = `/api/alliance/forum/thread/${params.id}`;
        if (targetView === 'management') url = '/api/alliance/hub'; 

        try {
            const res = await fetch(url);
            const data = await res.json();
            payload = data || {};
            view = targetView;
        } catch (e) {
            console.error("Link unstable.");
            payload = { success: false, message: "Sector Uplink Failed." };
        } finally {
            loading = false;
        }
    }

    onMount(() => fetchPayload(view));
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advisor={payload?.advisor} />
        
        {#if payload?.in_alliance}
            <div class="bg-gray-900/60 border border-white/5 rounded-lg overflow-hidden shadow-2xl backdrop-blur-md">
                <header class="bg-[#030712]/60 px-4 py-2 border-b border-white/5 flex justify-between items-center">
                    <h2 class="text-cyan-500 font-title text-[9px] font-black uppercase tracking-[3px]">Sector Group</h2>
                </header>
                <div class="p-2 space-y-1">
                    <button onclick={() => fetchPayload('hub')} class="w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {view === 'hub' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Hub</button>
                    <button onclick={() => fetchPayload('bank')} class="w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {view === 'bank' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Treasury</button>
                    <button onclick={() => fetchPayload('structures')} class="w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {view === 'structures' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Structures</button>
                    <button onclick={() => fetchPayload('forum')} class="w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {view === 'forum' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Forum</button>
                    {#if payload.is_leader || payload.my_role?.can_invite || payload.my_role?.can_kick}
                        <button onclick={() => fetchPayload('management')} class="w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-purple-500/10 hover:text-white transition-all {view === 'management' ? 'text-purple-400 bg-purple-500/5' : 'text-gray-500'}">Command</button>
                    {/if}
                </div>
            </div>
        {/if}
    </aside>

    <main class="lg:col-span-3">
        {#if loading}
            <div class="h-64 flex items-center justify-center opacity-20">
                <span class="text-xs font-black uppercase tracking-[5px] animate-pulse">Syncing Collective Data...</span>
            </div>
        {:else}
            {#if payload?.success === false}
                <div class="h-96 flex items-center justify-center">
                    <div class="bg-red-500/10 border border-red-500/30 p-8 rounded-lg text-center backdrop-blur-sm max-w-md">
                        <span class="text-xs font-black text-red-500 uppercase tracking-[4px] block mb-2 italic">Sector Link Fault</span>
                        <p class="text-[10px] font-mono text-gray-400 uppercase leading-relaxed">{payload.message || 'The neural link to this sector was terminated.'}</p>
                        <button onclick={() => fetchPayload('hub')} class="mt-6 text-[8px] font-black text-white bg-red-500/20 px-4 py-2 uppercase tracking-widest hover:bg-red-500/40 transition-all border border-red-500/50">Return to Hub</button>
                    </div>
                </div>
            {:else if view === 'hub'}
                {#if payload.in_alliance}
                    <AllianceHub {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
                {:else}
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
                {/if}
            {:else if view === 'list'}
                <AllianceList {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'create'}
                <AllianceCreate onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'bank'}
                <AllianceBank {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'structures'}
                <AllianceStructures {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'forum'}
                <AllianceForum {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'thread'}
                <AllianceThread {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {:else if view === 'management'}
                <AllianceManagement {payload} onNavigate={(v, p) => fetchPayload(v, p)} />
            {/if}
        {/if}
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
