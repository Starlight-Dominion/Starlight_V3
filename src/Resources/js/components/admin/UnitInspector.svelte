<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        data = $bindable(null), 
        tab = $bindable('identity'),
        savingId = $bindable(null),
        allUnits = [],
        onSave
    } = $props();

</script>

{#if show && data}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-5xl h-full max-h-[85vh] bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 bg-red-950/20 border border-red-900/30 rounded-2xl flex items-center justify-center text-red-600 font-title font-black text-3xl">
                        {data.slug.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">{data.name}</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">TACTICAL CALIBRATION // ID: {data.id}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick={() => onSave(data)} class="px-8 py-4 bg-red-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-[0_0_30px_rgba(153,27,27,0.3)]" disabled={savingId === data.id}>
                        {savingId === data.id ? 'UPLOADING...' : 'COMMIT DOCTRINE'}
                    </button>
                    <button onclick={() => show = false} class="w-16 h-16 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-2xl">×</button>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <nav class="flex border-b border-white/5 bg-white/[0.02]">
                {#each [
                    { id: 'identity', name: 'Core Identity' },
                    { id: 'costs', name: 'Requisition Costs' },
                    { id: 'yield', name: 'Tactical Yield' },
                    { id: 'reqs', name: 'Prerequisites' }
                ] as t}
                    <button 
                        onclick={() => tab = t.id}
                        class="px-10 py-6 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 {tab === t.id ? 'text-red-500 border-red-500 bg-red-500/5' : 'text-gray-600 border-transparent hover:text-gray-400'}"
                    >
                        {t.name}
                    </button>
                {/each}
            </nav>

            <!-- Content Area -->
            <div class="flex-grow overflow-y-auto p-12 custom-scrollbar">
                {#if tab === 'identity'}
                    <div in:fade class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Division Designation (Name)</span>
                                <input type="text" bind:value={data.name} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-red-900 outline-none" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Protocol ID (Slug)</span>
                                <input type="text" bind:value={data.slug} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-red-900 outline-none" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Division Dossier (Description)</span>
                            <textarea bind:value={data.description} rows="6" class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-gray-400 font-mono text-sm focus:border-red-900 outline-none resize-none leading-relaxed" placeholder="Enter combat role and lore..."></textarea>
                        </div>
                    </div>
                {:else if tab === 'costs'}
                    <div in:fade class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Requisition (Credits)</span>
                            <input type="number" bind:value={data.cost_credits} class="w-full bg-black/40 border border-white/10 rounded-xl px-8 py-6 text-cyan-400 font-title font-black text-3xl focus:border-cyan-500 outline-none text-center" />
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Manpower (Citizens)</span>
                            <input type="number" bind:value={data.cost_citizens} class="w-full bg-black/40 border border-white/10 rounded-xl px-8 py-6 text-white font-title font-black text-3xl focus:border-red-500 outline-none text-center" />
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Energy Drain (Turns)</span>
                            <input type="number" bind:value={data.cost_turns} class="w-full bg-black/40 border border-white/10 rounded-xl px-8 py-6 text-white font-title font-black text-3xl focus:border-red-500 outline-none text-center" />
                        </div>
                    </div>
                {:else if tab === 'yield'}
                    <div in:fade class="space-y-12">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-red-900 uppercase tracking-widest">Offensive Power</span>
                                <input type="number" bind:value={data.power_offense} class="w-full bg-black/40 border border-red-900/20 rounded-xl px-6 py-4 text-red-500 font-title font-black text-2xl focus:border-red-500 outline-none text-center" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-cyan-900 uppercase tracking-widest">Defensive Power</span>
                                <input type="number" bind:value={data.power_defense} class="w-full bg-black/40 border border-cyan-900/20 rounded-xl px-6 py-4 text-cyan-500 font-title font-black text-2xl focus:border-cyan-500 outline-none text-center" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-emerald-900 uppercase tracking-widest">Production Yield (CP)</span>
                                <input type="number" bind:value={data.production_credits} class="w-full bg-black/40 border border-emerald-900/20 rounded-xl px-6 py-4 text-emerald-500 font-title font-black text-2xl focus:border-emerald-500 outline-none text-center" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-8 border-t border-white/5 pt-12">
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-purple-900 uppercase tracking-widest">Infiltration (Spy ATK)</span>
                                <input type="number" bind:value={data.power_spy_offense} class="w-full bg-black/40 border border-purple-900/20 rounded-xl px-6 py-4 text-purple-500 font-title font-black text-2xl focus:border-purple-500 outline-none text-center" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-indigo-900 uppercase tracking-widest">Surveillance (Spy DEF)</span>
                                <input type="number" bind:value={data.power_spy_defense} class="w-full bg-black/40 border border-indigo-900/20 rounded-xl px-6 py-4 text-indigo-400 font-title font-black text-2xl focus:border-indigo-500 outline-none text-center" />
                            </div>
                        </div>
                    </div>
                {:else if tab === 'reqs'}
                    <div in:fade class="space-y-8">
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Required Foundation Rank</span>
                            <input type="number" bind:value={data.foundation_level_req} class="w-full bg-black/40 border border-white/10 rounded-xl px-8 py-6 text-white font-title font-black text-3xl focus:border-red-900 outline-none text-center" />
                            <p class="text-[8px] text-gray-600 italic uppercase mt-2 text-center">Minimum command center rank needed to enlist this class.</p>
                        </div>
                        <div class="space-y-2 pt-8 border-t border-white/5">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Prerequisite Combat Class (Dependency)</span>
                            <select bind:value={data.requirement_slug} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-red-900 outline-none uppercase text-xs font-black">
                                <option value="">NO PREVIOUS CLASS REQUIRED</option>
                                {#each allUnits.filter(u => u.id !== data.id) as p}
                                    <option value={p.slug}>{p.name.toUpperCase()} (ID: {p.id})</option>
                                {/each}
                            </select>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}
