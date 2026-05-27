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
                        
                        <div class="space-y-4">
                            {#each data.levels as row}
                                <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl flex flex-col xl:flex-row gap-6 group relative transition-all hover:border-white/10">
                                    <!-- Rank & Label -->
                                    <div class="flex items-center gap-6 xl:w-1/4">
                                        <div class="w-16 h-16 bg-red-950/20 rounded-xl flex items-center justify-center border border-red-500/10 text-red-500 font-black text-2xl font-title italic">
                                            {row.level}
                                        </div>
                                        <div class="space-y-2 flex-grow">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Rank Designation</span>
                                            <input type="text" bind:value={row.buff_name} class="w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-white text-xs font-black uppercase focus:border-cyan-500 outline-none transition-all" />
                                        </div>
                                    </div>
                                    
                                    <!-- Stats Grid -->
                                    <div class="flex-grow grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Cost (CP)</span>
                                            <input type="number" bind:value={row.cost} class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-cyan-400 text-xs font-mono focus:border-cyan-500 outline-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Integrity (HP)</span>
                                            <input type="number" bind:value={row.buff_hp} class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-gray-400 text-xs font-mono focus:border-cyan-500 outline-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Capacity</span>
                                            <input type="number" bind:value={row.capacity} class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-blue-400 text-xs font-mono focus:border-cyan-500 outline-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Req. Player Lvl</span>
                                            <input type="number" bind:value={row.player_level_req} class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-white text-xs font-mono focus:border-cyan-500 outline-none" />
                                        </div>
                                        
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Economy Buff (%)</span>
                                            <input type="number" bind:value={row.buff_economy} class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-green-500 text-xs font-mono focus:border-cyan-500 outline-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Citizens / Tick</span>
                                            <input type="number" bind:value={row.buff_citizens_per_tick} class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-blue-300 text-xs font-mono focus:border-cyan-500 outline-none" />
                                        </div>
                                        <div class="space-y-1 col-span-2">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Combat Buffs (ATK / DEF)</span>
                                            <div class="flex gap-2">
                                                <input type="number" bind:value={row.buff_offense} class="w-1/2 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-red-500 text-xs font-mono focus:border-cyan-500 outline-none" title="Attack" />
                                                <input type="number" bind:value={row.buff_defense} class="w-1/2 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-cyan-500 text-xs font-mono focus:border-cyan-500 outline-none" title="Defense" />
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-1 col-span-2 md:col-span-4">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-tighter">Military Reinforcements (G / S / SP / SE)</span>
                                            <div class="flex gap-2">
                                                <div class="relative flex-grow">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[7px] font-black text-gray-700">G</span>
                                                    <input type="number" bind:value={row.buff_unit_guards} class="w-full bg-black/40 border border-white/10 rounded-lg pl-6 pr-3 py-2 text-white text-xs font-mono focus:border-cyan-500 outline-none" />
                                                </div>
                                                <div class="relative flex-grow">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[7px] font-black text-gray-700">S</span>
                                                    <input type="number" bind:value={row.buff_unit_soldiers} class="w-full bg-black/40 border border-white/10 rounded-lg pl-6 pr-3 py-2 text-white text-xs font-mono focus:border-cyan-500 outline-none" />
                                                </div>
                                                <div class="relative flex-grow">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[7px] font-black text-gray-700">SP</span>
                                                    <input type="number" bind:value={row.buff_unit_spies} class="w-full bg-black/40 border border-white/10 rounded-lg pl-6 pr-3 py-2 text-white text-xs font-mono focus:border-cyan-500 outline-none" />
                                                </div>
                                                <div class="relative flex-grow">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[7px] font-black text-gray-700">SE</span>
                                                    <input type="number" bind:value={row.buff_unit_sentries} class="w-full bg-black/40 border border-white/10 rounded-lg pl-6 pr-3 py-2 text-white text-xs font-mono focus:border-cyan-500 outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center xl:w-32 justify-end">
                                        <button 
                                            onclick={() => onSaveLevel(data.details.id, row)} 
                                            class="w-full xl:h-full px-6 py-4 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-cyan-600 hover:text-white transition-all xl:opacity-0 group-hover:opacity-100 disabled:opacity-30" 
                                            disabled={savingId === `level-${data.details.id}-${row.level}`}
                                        >
                                            {savingId === `level-${data.details.id}-${row.level}` ? '...' : 'SAVE'}
                                        </button>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}
