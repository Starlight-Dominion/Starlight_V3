<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        data = $bindable(null), 
        tab = $bindable('identity'),
        savingId = $bindable(null),
        unitTypes = [],
        categories = [],
        allArmoryItems = [],
        onSave
    } = $props();

</script>

{#if show && data}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-4xl h-full max-h-[80vh] bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 bg-amber-950/20 rounded-lg flex items-center justify-center border border-amber-500/20 text-amber-500 font-title font-black text-3xl">
                        {data.slug.substring(0,2).toUpperCase()}
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">{data.name}</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">ARMAMENT CALIBRATION // ID: {data.id}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick={() => onSave(data)} class="px-8 py-4 bg-amber-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-400 transition-all shadow-[0_0_30px_rgba(217,119,6,0.3)]" disabled={savingId === data.id}>
                        {savingId === data.id ? 'CALIBRATING...' : 'COMMIT ASSET'}
                    </button>
                    <button onclick={() => show = false} class="w-16 h-16 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-2xl">×</button>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <nav class="flex border-b border-white/5 bg-white/[0.02]">
                {#each [
                    { id: 'identity', name: 'Core Identity' },
                    { id: 'combat', name: 'Combat Calibration' },
                    { id: 'reqs', name: 'Prerequisites' }
                ] as t}
                    <button 
                        onclick={() => tab = t.id}
                        class="px-10 py-6 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 {tab === t.id ? 'text-amber-400 border-amber-500 bg-amber-500/5' : 'text-gray-600 border-transparent hover:text-gray-400'}"
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
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Asset Designation (Name)</span>
                                <input type="text" bind:value={data.name} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-amber-500 outline-none" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Protocol Slug</span>
                                <input type="text" bind:value={data.slug} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-amber-500 outline-none" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Unit Compatibility Type</span>
                                <select bind:value={data.unit_type} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-amber-500 outline-none uppercase text-xs font-black">
                                    {#each unitTypes as u}
                                        <option value={u.slug}>{u.name.toUpperCase()}</option>
                                    {/each}
                                </select>
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Asset Category</span>
                                <select bind:value={data.category_id} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-amber-500 outline-none uppercase text-xs font-black">
                                    {#each categories as cat}
                                        <option value={cat.id}>{cat.name.toUpperCase()}</option>
                                    {/each}
                                </select>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Administrative Notes</span>
                            <textarea bind:value={data.notes} rows="4" class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-gray-400 font-mono text-sm focus:border-amber-500 outline-none resize-none" placeholder="Enter developer context or asset history..."></textarea>
                        </div>
                    </div>
                {:else if tab === 'combat'}
                    <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-red-900 uppercase tracking-widest mb-1">Offensive Power (ATK Bonus)</span>
                            <input type="number" bind:value={data.attack_bonus} class="w-full bg-black/40 border border-red-900/20 rounded-xl px-8 py-6 text-red-500 font-title font-black text-3xl focus:border-red-500 outline-none text-center" />
                            <p class="text-[8px] text-gray-600 italic uppercase mt-2">Additive bonus applied to every unit in this item's class.</p>
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-cyan-900 uppercase tracking-widest mb-1">Defensive Power (DEF Bonus)</span>
                            <input type="number" bind:value={data.defense_bonus} class="w-full bg-black/40 border border-cyan-900/20 rounded-xl px-8 py-6 text-cyan-500 font-title font-black text-3xl focus:border-cyan-500 outline-none text-center" />
                            <p class="text-[8px] text-gray-600 italic uppercase mt-2">Armor rating improvement for sector-wide defense.</p>
                        </div>
                    </div>
                {:else if tab === 'reqs'}
                    <div in:fade class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Requisition Cost (Credits)</span>
                                <input type="number" bind:value={data.cost} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-cyan-400 font-mono text-lg focus:border-cyan-500 outline-none" />
                            </div>
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Min. Armory Rank Requirement</span>
                                <input type="number" bind:value={data.armory_level_req} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono text-lg focus:border-amber-500 outline-none" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Prerequisite Schematic (Requirement Slug)</span>
                            <select bind:value={data.requirement_slug} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-amber-500 outline-none uppercase text-xs font-black">
                                <option value="">NO PREVIOUS ASSET REQUIRED</option>
                                {#each allArmoryItems.filter(i => i.id !== data.id && i.unit_type === data.unit_type) as p}
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
