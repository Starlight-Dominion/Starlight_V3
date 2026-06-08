<script>
    import { fade, slide } from 'svelte/transition';
    import { game, resources } from '../stores/gameStore.svelte.js';
    import AiAdvisor from '../components/AiAdvisor.svelte';

    let { players = [] } = $props();
    
    // Persistent Intelligence logic (SDO Recon System)
    const savedIntel = $state(JSON.parse(typeof localStorage !== 'undefined' ? localStorage.getItem('shadow_intel') || '{}' : '{}'));

    let selectedTarget = $state(null);
    let attackTurns = $state(1);
    let loading = $state(false);
    let message = $state(null);

    function getIntel(kingdomId) {
        return savedIntel[kingdomId] || null;
    }

    async function handleAttack() {
        if (!selectedTarget || loading || resources.turns < attackTurns) return;

        loading = true;
        message = null;
        
        try {
            const formData = new FormData();
            formData.append('target_id', selectedTarget.kingdom_id);
            formData.append('turns', attackTurns);
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

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    
    <!-- MAIN TARGET LIST -->
    <main class="lg:col-span-3 space-y-4">
        
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-6xl font-title font-black text-white italic">COMBAT_NET</span>
            </div>
            <div class="relative z-10">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Targeting Array</h1>
                <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-1 italic">Active sectors detected within operational range.</p>
            </div>
        </header>

        {#if message}
            <div in:slide class="p-3 rounded border text-[10px] font-black uppercase text-center {message.success ? 'bg-cyan-950/40 border-cyan-500/50 text-cyan-400' : 'bg-red-950/40 border-red-500/50 text-red-500'}">
                {message.message}
            </div>
        {/if}

        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-[11px]">
                    <thead class="bg-black/40 text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-4 font-black tracking-widest">Sector</th>
                            <th class="px-6 py-4 font-black tracking-widest">Profile</th>
                            <th class="px-6 py-4 text-center font-black tracking-widest">Intel</th>
                            <th class="px-6 py-4 text-right font-black tracking-widest">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each players as player}
                            <tr 
                                class="hover:bg-cyan-500/5 transition-colors group cursor-pointer {selectedTarget?.kingdom_id === player.kingdom_id ? 'bg-cyan-500/10' : ''}"
                                onclick={() => selectedTarget = player}
                            >
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-black/60 border border-white/10 flex items-center justify-center text-cyan-400 font-title font-black">
                                            #{player.kingdom_id}
                                        </div>
                                        <span class="text-white font-title font-black text-sm uppercase group-hover:text-cyan-400 transition-colors">{player.name}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-[10px] text-gray-400 uppercase font-black">LVL {player.level} &bull; {player.race}</span>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    {#if getIntel(player.kingdom_id)}
                                        <div class="flex justify-center gap-4 text-[10px]">
                                            <span class="text-white font-bold">{formatNumber(getIntel(player.kingdom_id).total_army)} <span class="text-[8px] text-gray-600">UNIT</span></span>
                                            <span class="text-cyan-500 font-bold">{formatNumber(getIntel(player.kingdom_id).gold)} <span class="text-[8px] text-cyan-900">CP</span></span>
                                        </div>
                                    {:else}
                                        <span class="text-[9px] text-gray-700 uppercase italic">RECON_REQUIRED</span>
                                    {/if}
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <button 
                                        class="text-[9px] font-black uppercase tracking-widest {selectedTarget?.kingdom_id === player.kingdom_id ? 'text-white' : 'text-cyan-600 hover:text-white'} transition-colors"
                                        onclick={(e) => { e.stopPropagation(); selectedTarget = player; }}
                                    >
                                        {selectedTarget?.kingdom_id === player.kingdom_id ? 'Target Locked' : 'Lock Target'}
                                    </button>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- SIDEBAR CONTROLS -->
    <aside class="lg:col-span-1 space-y-4">
        
        <AiAdvisor />

        <div class="bg-gray-900/60 border border-cyan-500/20 rounded-lg p-5 backdrop-blur-md shadow-2xl space-y-6">
            <header class="border-b border-white/5 pb-2">
                <h3 class="font-title text-cyan-400 uppercase text-[10px] font-black tracking-[3px]">Tactical Hub</h3>
            </header>

            {#if selectedTarget}
                <div in:fade class="space-y-6">
                    <div class="bg-cyan-950/20 border border-cyan-500/30 rounded p-3 space-y-1">
                        <p class="text-[8px] uppercase text-cyan-500 font-black tracking-widest">Locked Target</p>
                        <p class="text-white font-title font-black text-sm uppercase">{selectedTarget.name}</p>
                        <p class="text-[9px] text-gray-500 uppercase font-black tracking-tighter">LVL {selectedTarget.level} {selectedTarget.race} Sovereign</p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-end">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Attack Intensity</label>
                            <span class="text-white font-mono font-black text-sm">{attackTurns} <span class="text-[9px] text-gray-600">TURNS</span></span>
                        </div>
                        <input 
                            type="range" 
                            min="1" 
                            max={Math.min(10, resources.turns)} 
                            bind:value={attackTurns}
                            class="w-full accent-cyan-500 h-1 bg-black/60 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-[8px] font-black text-gray-700 uppercase">
                            <span>Precision</span>
                            <span>Overwhelming</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button 
                            onclick={handleAttack}
                            disabled={loading || attackTurns > resources.turns}
                            class="w-full bg-red-900/30 hover:bg-red-900/50 border border-red-500/30 text-red-500 font-title font-black py-4 rounded-lg uppercase text-[11px] tracking-[3px] transition-all disabled:opacity-20 shadow-[0_0_20px_rgba(239,68,68,0.1)]"
                        >
                            {loading ? 'Executing...' : 'Execute Strike'}
                        </button>
                        
                        <button 
                            onclick={() => selectedTarget = null}
                            class="w-full mt-3 text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors"
                        >
                            Abort Lock
                        </button>
                    </div>
                </div>
            {:else}
                <div in:fade class="py-12 text-center space-y-4">
                    <div class="w-12 h-12 rounded-full border-2 border-gray-800 border-dashed mx-auto flex items-center justify-center">
                        <span class="w-2 h-2 bg-gray-800 rounded-full animate-ping"></span>
                    </div>
                    <p class="text-[10px] text-gray-600 uppercase font-black italic tracking-widest leading-relaxed px-4">
                        Awaiting sector selection from tactical targeting array.
                    </p>
                </div>
            {/if}

            <div class="pt-4 border-t border-white/5">
                <h4 class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2">Combat Protocol</h4>
                <p class="text-[9px] text-gray-600 italic leading-relaxed">
                    Deploying multiple tactical turns increases casualty projection but degrades operational efficiency. High-intensity strikes are recommended for well-fortified sectors.
                </p>
            </div>
        </div>

    </aside>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
    .text-shadow-glow {
        text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
    }
</style>
