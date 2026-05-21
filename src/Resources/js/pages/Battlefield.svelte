<script>
    import { fade, slide } from 'svelte/transition';
    import { game, resources } from '../stores/gameStore.svelte.js';

    let { players = [] } = $props();
    
    // Persistent Intelligence logic (SDO Recon System)
    const savedIntel = $state(JSON.parse(typeof localStorage !== 'undefined' ? localStorage.getItem('shadow_intel') || '{}' : '{}'));

    let selectedTurns = $state({}); // kingdomId -> turns
    let loading = $state(false);
    let message = $state(null);

    function getIntel(kingdomId) {
        return savedIntel[kingdomId] || null;
    }

    async function handleAttack(targetId) {
        const turns = selectedTurns[targetId] || 1;
        if (loading || resources.turns < turns) return;

        loading = true;
        message = null;
        
        try {
            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('turns', turns);
            formData.append('_csrf', game.csrf);

            const response = await fetch('/combat/battlefield/attack', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (result.success) {
                // Redirect to battle report
                window.location.href = `/combat/battlefield/report/${result.battle_id}`;
            } else {
                message = { success: false, message: result.message || 'Assault failed.' };
                loading = false;
            }
        } catch (error) {
            message = { success: false, message: 'Neural link unstable. Assault aborted.' };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-24">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-end border-b border-cyan-500/20 pb-6 gap-6">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Targeting Array</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2 italic">Active Dominion sectors within operational range.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-cyan-950/20 px-4 py-2 rounded-xl border border-cyan-500/30 flex flex-col items-center">
                <span class="text-[7px] font-black text-cyan-600 uppercase tracking-widest">Strike Capacity</span>
                <span class="text-xl font-title font-black text-white">{resources.turns} <span class="text-[8px] text-gray-600">TURNS</span></span>
            </div>
            <div class="bg-red-950/20 px-4 py-2 rounded-xl border border-red-500/30 flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                <span class="text-[9px] font-black text-red-500 uppercase tracking-[2px]">War Stance: ENGAGED</span>
            </div>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl relative">
        <div class="absolute inset-0 pointer-events-none opacity-[0.02] overflow-hidden">
             <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_50%_50%,#00ffff_0%,transparent_70%)]"></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-mono">
                <thead>
                    <tr class="bg-cyan-950/20 text-[10px] font-black text-gray-500 uppercase tracking-[3px] border-b border-white/5">
                        <th class="px-8 py-5">Dominion Sector</th>
                        <th class="px-8 py-5">Status</th>
                        <th class="px-8 py-5 text-center">Neural Intel</th>
                        <th class="px-8 py-5 text-right">Operational Directive</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    {#each players as player}
                        <tr class="hover:bg-white/5 transition-all group">
                            <td class="px-8 py-8">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-black/60 border border-white/10 flex items-center justify-center text-cyan-400 font-title font-black">
                                        #{player.kingdom_id}
                                    </div>
                                    <div>
                                        <span class="text-white font-title font-black block text-lg uppercase tracking-wider group-hover:text-cyan-400 transition-colors">{player.name}</span>
                                        <span class="text-[9px] text-gray-600 uppercase font-black tracking-widest italic">Tier {player.level} &bull; Commander {player.username}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-8">
                                <div class="flex items-center gap-3">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 shadow-[0_0_8px_#00ffff]"></span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Online</span>
                                </div>
                            </td>
                            <td class="px-8 py-8 text-center">
                                {#if getIntel(player.kingdom_id)}
                                    <div class="flex justify-center gap-6">
                                        <div class="text-center">
                                            <span class="text-[8px] text-gray-700 uppercase block font-black mb-1">Manpower</span>
                                            <span class="text-white font-black text-xs">{getIntel(player.kingdom_id).total_army.toLocaleString()}</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="text-[8px] text-gray-700 uppercase block font-black mb-1">Liquidity</span>
                                            <span class="text-cyan-500 font-black text-xs">{getIntel(player.kingdom_id).gold.toLocaleString()} <span class="text-[8px] text-cyan-900">CP</span></span>
                                        </div>
                                    </div>
                                {:else}
                                    <div class="inline-block px-3 py-1 rounded bg-black/40 border border-white/5">
                                        <span class="text-[9px] text-gray-800 font-black uppercase tracking-[3px] italic">Recon Required</span>
                                    </div>
                                {/if}
                            </td>
                            <td class="px-8 py-8 text-right">
                                <div class="flex items-center justify-end gap-6">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[8px] text-gray-700 uppercase font-black mb-1 tracking-widest">Assault Magnitude</span>
                                        <div class="relative">
                                            <input 
                                                type="number" 
                                                min="1" 
                                                max="10" 
                                                bind:value={selectedTurns[player.kingdom_id]} 
                                                class="bg-black/60 border border-white/10 text-cyan-400 font-black text-sm px-4 py-2 rounded-lg w-20 text-center focus:border-cyan-500 outline-none transition-all"
                                                placeholder="1"
                                            />
                                            <span class="absolute -right-1 -top-1 w-2 h-2 bg-red-600 rounded-full animate-ping opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                        </div>
                                    </div>
                                    <button 
                                        onclick={() => handleAttack(player.kingdom_id)}
                                        class="bg-black/60 border border-white/10 text-gray-500 px-8 py-3 rounded-xl font-title font-black text-[10px] uppercase tracking-[3px] hover:border-red-500/50 hover:text-red-500 hover:bg-red-950/20 transition-all group-hover:translate-x-[-4px] disabled:opacity-20"
                                        disabled={loading || (selectedTurns[player.kingdom_id] > resources.turns)}
                                    >
                                        Execute Strike
                                    </button>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </div>

    <!-- FOOTER LEGEND -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[2px] block mb-2">Tactical Note #01</span>
            <p class="text-[10px] text-gray-500 italic">Deploying higher turn magnitudes increases total damage output but consumes operational capacity rapidly.</p>
        </div>
        <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[2px] block mb-2">Tactical Note #02</span>
            <p class="text-[10px] text-gray-500 italic">Reconnaissance data remains valid for a limited window. Perform fresh scans before high-stakes engagements.</p>
        </div>
        <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[2px] block mb-2">Tactical Note #03</span>
            <p class="text-[10px] text-gray-500 italic">Target shields regenerate over time. Coordination with other Sovereigns can overwhelm heavy defenses.</p>
        </div>
    </div>
</div>
