<script>
    import { fade, slide } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        data = $bindable(null), 
        tab = $bindable('blueprint'),
        savingId = $bindable(null),
        onSaveDetails,
        onSaveLevel,
        onAddLevel
    } = $props();

</script>

{#if show && data}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-7xl h-full max-h-[90vh] bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 bg-cyan-950/20 rounded-lg flex items-center justify-center border border-cyan-500/20 text-cyan-500 font-title font-black text-3xl">
                        {data.details.slug.substring(0,2).toUpperCase()}
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">{data.details.name}</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">STRUCTURAL CALIBRATION // ID: {data.details.id}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick={() => onSaveDetails(data.details)} class="px-8 py-4 bg-cyan-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all shadow-[0_0_30px_rgba(6,182,212,0.3)]" disabled={savingId === data.details.id}>
                        {savingId === data.details.id ? 'UPLOADING...' : 'COMMIT ARCHITECTURE'}
                    </button>
                    <button onclick={() => show = false} class="w-16 h-16 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-2xl">×</button>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <nav class="flex border-b border-white/5 bg-white/[0.02]">
                {#each [
                    { id: 'blueprint', name: 'Core Blueprint' },
                    { id: 'matrix', name: 'Evolution Matrix' }
                ] as t}
                    <button 
                        onclick={() => tab = t.id}
                        class="px-10 py-6 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 {tab === t.id ? 'text-cyan-400 border-cyan-500 bg-cyan-500/5' : 'text-gray-600 border-transparent hover:text-gray-400'}"
                    >
                        {t.name}
                    </button>
                {/each}
            </nav>

            <!-- Content Area -->
            <div class="flex-grow overflow-y-auto p-12 custom-scrollbar">
                {#if tab === 'blueprint'}
                    <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-12 font-mono">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Building Designation</span>
                                <input type="text" bind:value={data.details.name} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-4 text-sm font-black text-white uppercase tracking-wider focus:border-cyan-500 outline-none" />
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Upgrade Slots</span>
                                    <input type="number" bind:value={data.details.upgrade_slots} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-xs font-mono text-cyan-400" />
                                </div>
                                <div class="space-y-2">
                                    <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Max Tier</span>
                                    <input type="number" bind:value={data.details.max_level} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-xs font-mono text-cyan-400" />
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Operational Directives (Description)</span>
                            <textarea bind:value={data.details.description} class="w-full bg-black/60 border border-white/10 rounded-2xl p-6 text-[11px] text-gray-400 focus:border-cyan-500 focus:outline-none min-h-[120px] leading-relaxed resize-none"></textarea>
                        </div>
                    </div>
                {:else if tab === 'matrix'}
                    <div in:fade class="space-y-6">
                        <div class="flex justify-between items-center px-2">
                            <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-[5px]">Rank Evolution Matrix</h4>
                            <button onclick={() => onAddLevel(data.details.id)} class="text-[9px] font-black text-gray-700 hover:text-white uppercase tracking-widest transition-colors">+ ADD RANK</button>
                        </div>
                        <div class="bg-black/40 rounded-3xl overflow-hidden shadow-2xl overflow-x-auto border border-white/5">
                            <table class="w-full text-left border-collapse min-w-[1200px] font-mono">
                                <thead>
                                    <tr class="bg-cyan-950/20 text-[9px] font-black text-gray-600 uppercase tracking-widest">
                                        <th class="px-6 py-5">Rank</th>
                                        <th class="px-6 py-5">Label</th>
                                        <th class="px-6 py-5">Cost (CP)</th>
                                        <th class="px-6 py-5">Integrity (HP)</th>
                                        <th class="px-6 py-5">ATK / DEF Buff</th>
                                        <th class="px-6 py-5">Economy Buff (%)</th>
                                        <th class="px-6 py-5">Citizens / Tick</th>
                                        <th class="px-6 py-5">Military Reinforcements</th>
                                        <th class="px-6 py-5">Capacity</th>
                                        <th class="px-6 py-5">Req. Lvl</th>
                                        <th class="px-6 py-5"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    {#each data.levels as row}
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-5 text-red-600 font-black text-sm italic">{row.level}</td>
                                            <td class="px-6 py-5"><input type="text" bind:value={row.buff_name} class="bg-transparent border-none p-0 text-white text-[11px] font-black uppercase focus:ring-0 w-32" /></td>
                                            <td class="px-6 py-5"><input type="number" bind:value={row.cost} class="bg-transparent border-none p-0 text-cyan-400 text-xs font-mono focus:ring-0 w-28" /></td>
                                            <td class="px-6 py-5"><input type="number" bind:value={row.buff_hp} class="bg-transparent border-none p-0 text-gray-400 text-xs font-mono focus:ring-0 w-20" /></td>
                                            <td class="px-6 py-5">
                                                <div class="flex gap-2">
                                                    <input type="number" bind:value={row.buff_offense} class="bg-transparent border border-white/5 rounded px-2 py-1 text-red-500 text-[10px] font-mono focus:ring-0 w-12" title="Attack" />
                                                    <input type="number" bind:value={row.buff_defense} class="bg-transparent border border-white/5 rounded px-2 py-1 text-cyan-500 text-[10px] font-mono focus:ring-0 w-12" title="Defense" />
                                                </div>
                                            </td>
                                            <td class="px-6 py-5"><input type="number" bind:value={row.buff_economy} class="bg-transparent border-none p-0 text-green-500 text-xs font-mono focus:ring-0 w-16" /></td>
                                            <td class="px-6 py-5"><input type="number" bind:value={row.buff_citizens_per_tick} class="bg-transparent border-none p-0 text-blue-300 text-xs font-mono focus:ring-0 w-16" /></td>
                                            <td class="px-6 py-5">
                                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                                    <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">G</span><input type="number" bind:value={row.buff_unit_guards} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                    <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">S</span><input type="number" bind:value={row.buff_unit_soldiers} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                    <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">SP</span><input type="number" bind:value={row.buff_unit_spies} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                    <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">SE</span><input type="number" bind:value={row.buff_unit_sentries} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5"><input type="number" bind:value={row.capacity} class="bg-transparent border-none p-0 text-blue-400 text-xs font-mono focus:ring-0 w-20" /></td>
                                            <td class="px-6 py-5"><input type="number" bind:value={row.player_level_req} class="bg-transparent border-none p-0 text-white text-xs font-mono focus:ring-0 w-12" /></td>
                                            <td class="px-6 py-5 text-right">
                                                <button onclick={() => onSaveLevel(data.details.id, row)} class="text-cyan-500 font-black uppercase text-[10px] tracking-[2px] hover:text-white transition-all">
                                                    {savingId === `level-${data.details.id}-${row.level}` ? '...' : 'SAVE'}
                                                </button>
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}
