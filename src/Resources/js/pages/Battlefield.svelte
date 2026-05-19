<script>
    import { fade } from 'svelte/transition';
    import { game } from '../stores/gameStore.svelte.js';

    let { players = [] } = $props();
    
    // Persistent Intelligence logic
    const savedIntel = $state(JSON.parse(typeof localStorage !== 'undefined' ? localStorage.getItem('shadow_intel') || '{}' : '{}'));

    let selectedTurns = $state({}); // kingdomId -> turns

    function getIntel(kingdomId) {
        return savedIntel[kingdomId] || null;
    }

    async function handleAttack(targetId) {
        const turns = selectedTurns[targetId] || 1;
        
        try {
            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('turns', turns);
            formData.append('_csrf', game.csrf);

            const response = await fetch('/combat/battlefield/attack', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Redirect to battle report
                window.location.href = `/combat/battlefield/report/${result.battle_id}`;
            } else {
                alert(result.message || 'Attack failed');
            }
        } catch (error) {
            console.error('Attack error:', error);
            alert('An error occurred while launching the attack.');
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="flex justify-between items-end border-b border-[#2a231e] pb-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">The Battlefield</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2">Active Targets in your vicinity.</p>
        </div>
        <div class="bg-[#3f6b2f]/10 px-4 py-2 rounded border border-[#3f6b2f]/30">
            <span class="text-[9px] font-black text-[#3f6b2f] uppercase tracking-widest">War Stance: Active</span>
        </div>
    </header>

    <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl overflow-hidden shadow-2xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#1a1a1a] text-[9px] font-black text-gray-600 uppercase tracking-widest">
                    <th class="px-6 py-4">Sovereign</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-center">Tactical Intel</th>
                    <th class="px-6 py-4 text-right">Assault</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a231e]">
                {#each players as player}
                    <tr class="hover:bg-[#141414] transition-colors group">
                        <td class="px-6 py-6">
                            <span class="text-white font-black block">{player.name}</span>
                            <span class="text-[9px] text-gray-600 uppercase font-bold">Lvl {player.level} &bull; {player.username}</span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#3f6b2f]"></span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Shielded</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-center">
                            {#if getIntel(player.kingdom_id)}
                                <div class="flex justify-center gap-4">
                                    <div>
                                        <span class="text-[8px] text-gray-600 uppercase block font-bold">Army</span>
                                        <span class="text-[#c5a059] font-mono text-xs">{getIntel(player.kingdom_id).total_army}</span>
                                    </div>
                                    <div>
                                        <span class="text-[8px] text-gray-600 uppercase block font-bold">Gold</span>
                                        <span class="text-[#c5a059] font-mono text-xs">{getIntel(player.kingdom_id).gold.toLocaleString()}</span>
                                    </div>
                                </div>
                            {:else}
                                <span class="text-[9px] text-gray-700 font-black uppercase tracking-[2px] italic">No Recon</span>
                            {/if}
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex items-center justify-end gap-4">
                                <div class="flex flex-col items-end">
                                    <span class="text-[8px] text-gray-600 uppercase font-bold mb-1">Turns</span>
                                    <input 
                                        type="number" 
                                        min="1" 
                                        max="10" 
                                        bind:value={selectedTurns[player.kingdom_id]} 
                                        class="bg-[#1a1a1a] border border-[#2a231e] text-white text-xs px-2 py-1 rounded w-16 text-center focus:border-[#3f6b2f] outline-none"
                                        placeholder="1"
                                    />
                                </div>
                                <button 
                                    onclick={() => handleAttack(player.kingdom_id)}
                                    class="bg-[#2a231e] border border-[#3f3028] text-gray-500 px-6 py-2 rounded font-black text-[10px] uppercase tracking-widest hover:border-red-900 hover:text-red-500 transition-all group-hover:scale-105"
                                >
                                    Attack
                                </button>
                            </div>
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</div>