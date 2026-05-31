<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        data = $bindable(null), 
        tab = $bindable('identity'),
        savingId = $bindable(null),
        botProfiles = [],
        onUpdateDominion,
        onUpdateManpower,
        onUpdateStructure,
        onUpdateArmory
    } = $props();

</script>

{#if show && data}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-7xl h-full max-h-[90vh] bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 rounded-full bg-cyan-950/30 border border-cyan-500/20 flex items-center justify-center text-cyan-500 font-title font-black text-3xl">
                        {data.dominion.id}
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">{data.dominion.name}</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">DEEP-DIVE SOVEREIGN OVERSIGHT // CMD: {data.dominion.user.username}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick={onUpdateDominion} class="px-8 py-4 bg-cyan-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all shadow-[0_0_30px_rgba(6,182,212,0.3)]" disabled={savingId === 'inspector-save'}>
                        {savingId === 'inspector-save' ? 'UPLOADING...' : 'COMMIT PROFILE'}
                    </button>
                    <button onclick={() => show = false} class="w-16 h-16 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-2xl">×</button>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <nav class="flex border-b border-white/5 bg-white/[0.02]">
                {#each [
                    { id: 'identity', name: 'Core Identity' },
                    { id: 'stats', name: 'Dominion Stats' },
                    { id: 'military', name: 'Military' },
                    { id: 'structures', name: 'Structures' },
                    { id: 'armory', name: 'Armory' }
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
                {#if tab === 'identity'}
                    <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <h3 class="text-xs font-black text-cyan-500 uppercase tracking-widest mb-4">Account Metadata</h3>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Username</span>
                                    <input type="text" bind:value={data.dominion.user.username} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-cyan-500 outline-none" />
                                </div>
                                <div class="space-y-2">
                                    <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Email Address</span>
                                    <input type="email" bind:value={data.dominion.user.email} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-cyan-500 outline-none" />
                                </div>
                                <div class="space-y-2">
                                    <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Avatar Path</span>
                                    <input type="text" bind:value={data.dominion.user.avatar_path} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-cyan-500 outline-none" />
                                </div>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <h3 class="text-xs font-black text-red-500 uppercase tracking-widest mb-4">Security & Clearance</h3>
                            <div class="space-y-4">
                                <div class="flex items-center gap-6 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
                                    <input type="checkbox" bind:checked={data.dominion.user.is_admin} class="w-6 h-6 rounded border-gray-800 text-cyan-600 focus:ring-cyan-500 bg-black/40" />
                                    <div>
                                        <span class="block text-[10px] font-black uppercase text-white">Administrator Privileges</span>
                                        <span class="text-[9px] text-gray-600 uppercase">Access to the High Command terminal.</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
                                    <input type="checkbox" bind:checked={data.dominion.user.is_bot} class="w-6 h-6 rounded border-gray-800 text-cyan-600 focus:ring-cyan-500 bg-black/40" />
                                    <div>
                                        <span class="block text-[10px] font-black uppercase text-white">Automated Drone (Bot)</span>
                                        <span class="text-[9px] text-gray-600 uppercase">Flags this account as a non-player entity.</span>
                                    </div>
                                </div>
                                {#if data.dominion.user.is_bot}
                                    <div class="space-y-2">
                                        <span class="block text-[9px] font-black text-emerald-900 uppercase tracking-widest">Automation Protocol</span>
                                        <select bind:value={data.dominion.user.bot_profile_id} class="w-full bg-black/40 border border-emerald-900/20 rounded-xl px-6 py-4 text-emerald-500 font-mono focus:border-emerald-500 outline-none uppercase text-xs font-black">
                                            <option value={null}>NO ACTIVE PROTOCOL</option>
                                            {#each botProfiles as profile}
                                                <option value={profile.id}>{profile.name.toUpperCase()}</option>
                                            {:else}
                                                <option disabled>NO PROFILES CONFIGURED</option>
                                            {/each}
                                        </select>
                                    </div>
                                {/if}
                                <div class="space-y-2">
                                    <span class="block text-[9px] font-black text-red-900 uppercase tracking-widest">Force Password Reset</span>
                                    <input type="password" bind:value={data.dominion.user.password} placeholder="••••••••" class="w-full bg-black/40 border border-red-900/20 rounded-xl px-6 py-4 text-red-500 font-mono focus:border-red-500 outline-none" />
                                </div>
                            </div>
                        </div>
                    </div>
                {:else if tab === 'stats'}
                    <div in:fade class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        {#each [
                            { f: 'credits', n: 'Liquid Credits' },
                            { f: 'credits_banked', n: 'Sovereign Bank' },
                            { f: 'xp', n: 'Experience' },
                            { f: 'turns', n: 'Energy Turns' },
                            { f: 'citizens', n: 'Total Citizens' },
                            { f: 'held_citizens', n: 'Held Population' },
                            { f: 'foundation_hp', n: 'Foundation HP' },
                            { f: 'foundation_max_hp', n: 'Max HP' },
                            { f: 'strength_points', n: 'STR Attribute' },
                            { f: 'dexterity_points', n: 'DEX Attribute' },
                            { f: 'constitution_points', n: 'CON Attribute' },
                            { f: 'charisma_points', n: 'CHA Attribute' },
                            { f: 'current_mine_tier', n: 'Mine Tier' },
                            { f: 'current_mine_level', n: 'Mine Level' },
                            { f: 'housing_level', n: 'Housing Level' },
                            { f: 'mercenary_market_level', n: 'Mercenary Rank' }
                        ] as stat}
                            <div class="space-y-2">
                                <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">{stat.n}</span>
                                <input type="number" bind:value={data.dominion[stat.f]} class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-cyan-400 font-mono text-xs focus:border-cyan-500" />
                            </div>
                        {/each}
                    </div>
                {:else if tab === 'military'}
                    <div in:fade class="space-y-4">
                        {#each data.all_units as unit}
                            {@const manpower = data.dominion.manpower.find(m => m.unit_id === unit.id) || { unit_id: unit.id, total_quantity: 0, stabled_quantity: 0 }}
                            <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl flex items-center justify-between group">
                                <div class="flex items-center gap-6 w-1/3">
                                    <div class="w-12 h-12 bg-cyan-900/20 rounded-lg flex items-center justify-center border border-cyan-500/10 text-cyan-400 font-black">
                                        {unit.slug.substring(0,2).toUpperCase()}
                                    </div>
                                    <div>
                                        <span class="block text-xs font-black text-white uppercase tracking-tight">{unit.name}</span>
                                        <span class="text-[9px] text-gray-600 uppercase font-black">Unit ID: {unit.id}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-8 w-2/3 justify-end">
                                    <div class="flex gap-4">
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase">Total Fielded</span>
                                            <input type="number" bind:value={manpower.total_quantity} class="w-24 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-xs text-white font-mono" />
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase">In Stables</span>
                                            <input type="number" bind:value={manpower.stabled_quantity} class="w-24 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-xs text-white font-mono" />
                                        </div>
                                    </div>
                                    <button onclick={() => onUpdateManpower(manpower)} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-cyan-600 hover:text-white transition-all opacity-0 group-hover:opacity-100 disabled:opacity-30" disabled={savingId === `unit-${unit.id}`}>
                                        {savingId === `unit-${unit.id}` ? '...' : 'SAVE'}
                                    </button>
                                </div>
                            </div>
                        {/each}
                    </div>
                {:else if tab === 'structures'}
                    <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {#each data.all_structures as structure}
                            {@const domStruct = data.dominion.structures.find(s => s.structure_id === structure.id) || { structure_id: structure.id, level: 0 }}
                            <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl flex items-center justify-between group">
                                <div class="flex items-center gap-6">
                                    <div class="w-12 h-12 bg-purple-900/20 rounded-lg flex items-center justify-center border border-purple-500/10 text-purple-400 font-black">
                                        {structure.slug.substring(0,2).toUpperCase()}
                                    </div>
                                    <div>
                                        <span class="block text-xs font-black text-white uppercase tracking-tight">{structure.name}</span>
                                        <span class="text-[9px] text-gray-600 uppercase font-black">Max Rank: {structure.max_level}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="space-y-1 text-right">
                                        <span class="block text-[8px] font-black text-gray-700 uppercase">Current Rank</span>
                                        <input type="number" bind:value={domStruct.level} class="w-20 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-xs text-white font-mono text-right" />
                                    </div>
                                    <button onclick={() => onUpdateStructure(domStruct)} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-purple-600 hover:text-white transition-all opacity-0 group-hover:opacity-100 disabled:opacity-30" disabled={savingId === `structure-${structure.id}`}>
                                        {savingId === `structure-${structure.id}` ? '...' : 'COMMIT'}
                                    </button>
                                </div>
                            </div>
                        {/each}
                    </div>
                {:else if tab === 'armory'}
                    <div in:fade class="space-y-4">
                        {#each data.all_armory as item}
                            {@const domItem = data.armory.find(ai => ai.item_id === item.id) || { item_id: item.id, quantity: 0, is_equipped: false }}
                            <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl flex items-center justify-between group">
                                <div class="flex items-center gap-6 w-1/3">
                                    <div class="w-12 h-12 bg-amber-900/20 rounded-lg flex items-center justify-center border border-amber-500/10 text-amber-400 font-black">
                                        {item.slug.substring(0,2).toUpperCase()}
                                    </div>
                                    <div>
                                        <span class="block text-xs font-black text-white uppercase tracking-tight">{item.name}</span>
                                        <span class="text-[9px] text-gray-600 uppercase font-black">{item.unit_type} // {item.cost.toLocaleString()} CR</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-8 w-2/3 justify-end">
                                    <div class="flex items-center gap-8">
                                        <div class="flex items-center gap-3 bg-black/40 px-4 py-2 rounded-lg border border-white/5">
                                            <input type="checkbox" bind:checked={domItem.is_equipped} class="w-4 h-4 rounded border-gray-800 text-amber-600 focus:ring-amber-500 bg-black/40" />
                                            <span class="text-[9px] font-black uppercase {domItem.is_equipped ? 'text-amber-500' : 'text-gray-700'}">Equipped</span>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase">Quantity</span>
                                            <input type="number" bind:value={domItem.quantity} class="w-24 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-xs text-white font-mono" />
                                        </div>
                                    </div>
                                    <button onclick={() => onUpdateArmory(domItem)} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-amber-600 hover:text-white transition-all opacity-0 group-hover:opacity-100 disabled:opacity-30" disabled={savingId === `item-${item.id}`}>
                                        {savingId === `item-${item.id}` ? '...' : 'SYNC'}
                                    </button>
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}
