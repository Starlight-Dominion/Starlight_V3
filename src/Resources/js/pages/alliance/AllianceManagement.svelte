<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceSidebar from '../../components/AllianceSidebar.svelte';

    let { payload = {} } = $props();
    let alliance = $derived(payload.alliance || {});
    let members = $derived(payload.members || []);
    let applications = $derived(payload.applications || []);
    
    let processing = $state(false);

    async function handleApplication(appId, action) {
        processing = true;
        try {
            const formData = new FormData();
            formData.append('application_id', appId);
            formData.append('action', action);
            formData.append('_csrf', game.csrf);
            const res = await fetch('/api/alliance/process-application', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            }
        } finally {
            processing = false;
        }
    }

    async function kickMember(memberId) {
        if (!confirm("Terminate this agent's affiliation?")) return;
        processing = true;
        try {
            const formData = new FormData();
            formData.append('user_id', memberId);
            formData.append('_csrf', game.csrf);
            const res = await fetch('/api/alliance/kick', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            }
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advisor={payload?.advisor} />
        <AllianceSidebar active="management" {payload} />
    </aside>

    <main class="lg:col-span-3 space-y-8">
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md">
            <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Command Oversight</h1>
            <p class="text-purple-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Authorized administrative and personnel control.</p>
        </header>

        <!-- Applications Section -->
        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <header class="bg-black/40 px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black text-purple-400 uppercase tracking-[4px]">Pending Enlistments</h2>
            </header>
            <div class="divide-y divide-white/5">
                {#each applications || [] as app}
                    <div class="px-6 py-4 flex justify-between items-center hover:bg-white/5 transition-colors">
                        <div>
                            <span class="text-white font-bold text-sm uppercase">{app.user?.username}</span>
                            <p class="text-[9px] text-gray-500 mt-1 italic uppercase tracking-widest">{app.message || 'No message provided.'}</p>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                onclick={() => handleApplication(app.id, 'accept')}
                                disabled={processing}
                                class="bg-emerald-500/10 border border-emerald-500/50 px-4 py-2 text-[10px] font-black text-emerald-400 uppercase tracking-widest hover:bg-emerald-500/30 transition-all"
                            >
                                Accept
                            </button>
                            <button 
                                onclick={() => handleApplication(app.id, 'reject')}
                                disabled={processing}
                                class="bg-red-500/10 border border-red-500/50 px-4 py-2 text-[10px] font-black text-red-400 uppercase tracking-widest hover:bg-red-500/30 transition-all"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                {:else}
                    <div class="py-12 text-center opacity-20 italic uppercase tracking-widest">No pending applications.</div>
                {/each}
            </div>
        </div>

        <!-- Member Management Section -->
        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <header class="bg-black/40 px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Personnel Roster</h2>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-[10px]">
                    <thead class="bg-black/20 text-gray-600 uppercase">
                        <tr>
                            <th class="px-6 py-3 font-black">Commander</th>
                            <th class="px-6 py-3 font-black">Designation</th>
                            <th class="px-6 py-3 text-right font-black">Standing</th>
                            <th class="px-6 py-3 text-right font-black">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each members || [] as member}
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-white font-bold uppercase">{member.username}</span>
                                    {#if member.id === alliance.leader_id}
                                        <span class="ml-2 text-[7px] bg-cyan-500/20 text-cyan-400 px-1 rounded">LEADER</span>
                                    {/if}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-400 uppercase">{member.alliance_role?.name || 'Ensign'}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-emerald-500 uppercase">Active</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {#if member.id !== alliance.leader_id}
                                        <button 
                                            onclick={() => kickMember(member.id)}
                                            disabled={processing}
                                            class="text-red-500/50 hover:text-red-500 font-black uppercase text-[8px] tracking-widest transition-colors"
                                        >
                                            Terminate
                                        </button>
                                    {/if}
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
