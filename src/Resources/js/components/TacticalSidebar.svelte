<script>
    import { game, resources, formattedTick } from '../stores/gameStore.svelte.js';
    import { fade } from 'svelte/transition';

    const user = $derived(game.user);
    const kingdom = $derived(user?.kingdom || {});
    const turnsCapped = $derived(resources.turns >= 200);
</script>

<aside class="lg:col-span-1 space-y-6">
    <div class="bg-[#0f0f0f] border border-[#2a231e] border-l-4 border-l-[#3f6b2f] shadow-2xl relative overflow-hidden">
        <div class="bg-[#161616] px-4 py-2 border-b border-[#2a231e] flex justify-between items-center">
            <h2 class="text-[#c5a059] text-[10px] font-black uppercase tracking-[3px]">The Advisor</h2>
        </div>
        <div class="p-6 space-y-4">
            {#if turnsCapped}
                <div in:fade class="bg-[#8b0000]/10 border border-[#8b0000]/30 p-3 rounded-sm">
                    <p class="text-[#8b0000] text-[10px] font-black uppercase tracking-widest leading-tight">Turns are capped! Strike!</p>
                </div>
            {/if}
            <p class="text-[#d1d5db] text-xs italic leading-relaxed font-serif">"{user?.advice || 'Scanning horizons...'}"</p>
            <div class="space-y-1 pt-4">
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-gray-600">Level {user?.level}</span>
                    <span class="text-[#c5a059]">{kingdom.xp?.toLocaleString() || 0} XP</span>
                </div>
                <div class="h-1 w-full bg-black rounded-full overflow-hidden border border-[#2a231e]">
                    <div class="h-full bg-gradient-to-r from-[#5c4a3e] to-[#c5a059]" style="width: {user?.xpProgress || 0}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[#0f0f0f] border border-[#2a231e] shadow-2xl">
        <div class="bg-[#161616] px-4 py-2 border-b border-[#2a231e]"><h2 class="text-gray-500 text-[10px] font-black uppercase tracking-[3px]">Vitals</h2></div>
        <div class="p-6 space-y-4 font-mono text-xs">
            <div class="flex justify-between border-b border-[#2a231e]/50 pb-2"><span class="text-gray-600">Gold:</span><span class="text-white font-bold">{resources.gold.toLocaleString()}</span></div>
            <div class="flex justify-between border-b border-[#2a231e]/50 pb-2"><span class="text-gray-600">Banked:</span><span class="text-[#c5a059] font-bold">{resources.bank.toLocaleString()}</span></div>
            <div class="flex justify-between border-b border-[#2a231e]/50 pb-2"><span class="text-gray-600">Citizens:</span><span class="text-white font-bold">{resources.citizens.toLocaleString()}</span></div>
            <div class="flex justify-between border-b border-[#2a231e]/50 pb-2"><span class="text-gray-600">Turns:</span><span class="text-[#3f6b2f] font-bold">{resources.turns}</span></div>
            <div class="flex justify-between pt-2"><span class="text-gray-600">Next Heartbeat:</span><span class="text-[#3f6b2f] font-bold">{formattedTick.value}</span></div>
        </div>
    </div>
</aside>