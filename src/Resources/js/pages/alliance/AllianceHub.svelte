<script>
    import { fade, slide } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceSidebar from '../../components/AllianceSidebar.svelte';

    let { payload = {} } = $props();
    let processing = $state(false);
    
    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    async function leave() {
        if (!confirm("Terminate collective affiliation?")) return;
        processing = true;
        try {
            const formData = new FormData();
            formData.append('_csrf', game.csrf);
            const res = await fetch('/api/alliance/leave', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.href = '/alliance';
            }
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advisor={payload?.advisor} />
        <AllianceSidebar active="hub" {payload} />
    </aside>

    <main class="lg:col-span-3 space-y-6">
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-6xl font-title font-black text-white italic">{payload.alliance?.tag}</span>
            </div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">{payload.alliance?.name}</h1>
                    <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">{payload.alliance?.description || 'No public mission statement.'}</p>
                </div>
                {#if !payload.is_leader}
                    <button 
                        onclick={leave}
                        disabled={processing}
                        class="bg-red-500/10 border border-red-500/50 px-4 py-2 text-[8px] font-black text-red-500 uppercase tracking-widest hover:bg-red-500/30 transition-all disabled:opacity-50"
                    >
                        Leave
                    </button>
                {/if}
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-900/40 border border-white/5 p-4 rounded-lg backdrop-blur-sm">
                <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block mb-1">War Prestige</span>
                <span class="text-2xl font-mono font-bold text-white">{formatNumber(payload.alliance?.war_prestige)}</span>
            </div>
            <div class="bg-gray-900/40 border border-white/5 p-4 rounded-lg backdrop-blur-sm">
                <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block mb-1">Group Wealth</span>
                <span class="text-2xl font-mono font-bold text-cyan-400">{formatNumber(payload.alliance?.bank_credits)} <span class="text-[10px] opacity-40 uppercase">CP</span></span>
            </div>
            <div class="bg-gray-900/40 border border-white/5 p-4 rounded-lg backdrop-blur-sm">
                <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block mb-1">Active Members</span>
                <span class="text-2xl font-mono font-bold text-white">{payload.members?.length || 0}</span>
            </div>
        </div>

        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <header class="bg-black/40 px-6 py-3 border-b border-white/5 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Collective Personnel</h2>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-[10px]">
                    <thead class="bg-black/20 text-gray-600 uppercase">
                        <tr>
                            <th class="px-6 py-3 font-black">Commander</th>
                            <th class="px-6 py-3 font-black">Designation</th>
                            <th class="px-6 py-3 text-right font-black">Standing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each payload.members || [] as member}
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-white font-bold">{member.username}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-cyan-600 uppercase font-black">{member.alliance_role?.name || 'Ensign'}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-gray-500 uppercase">Active</span>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
