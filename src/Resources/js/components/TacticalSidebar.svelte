<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    const user = $derived(game.user);
    const kingdom = $derived(user?.kingdom || {});
</script>

<aside class="lg:col-span-1 space-y-6">
    <div class="bg-dark-translucent border border-cyan-500/20 rounded-xl overflow-hidden shadow-2xl relative">
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-cyan-400/30"></div>
        <header class="bg-cyan-950/20 px-4 py-3 border-b border-cyan-500/10 flex justify-between items-center">
            <h2 class="text-cyan-400 font-title text-[10px] font-black uppercase tracking-[3px]">A.I. Advisor</h2>
            <span class="w-1 h-1 bg-cyan-500 rounded-full animate-ping"></span>
        </header>
        <div class="p-6">
            <p class="text-gray-300 text-xs italic border-l-2 border-cyan-500/30 pl-4">
                "{user?.advice || 'Welcome, Commander.'}"
            </p>
        </div>
    </div>

    <div class="bg-dark-translucent border border-cyan-500/20 rounded-xl overflow-hidden shadow-2xl">
        <header class="bg-cyan-950/20 px-4 py-3 border-b border-cyan-500/10">
            <h2 class="text-gray-500 font-title text-[10px] font-black uppercase tracking-[3px]">Sector Vitals</h2>
        </header>
        <div class="p-6 space-y-4 font-mono text-[11px]">
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Credits</span>
                <span class="text-white font-bold group-hover:text-cyan-400 transition-colors">{resources.credits.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Secure Bank</span>
                <span class="text-cyan-600 font-bold group-hover:text-cyan-400 transition-colors">{resources.bank.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="text-gray-600 uppercase text-[9px]">Cycle Refresh</span>
                <!-- FIX: Referencing game.formattedTick directly -->
                <span class="text-cyan-400 font-bold animate-pulse">{game.formattedTick}</span>
            </div>
        </div>
    </div>
</aside>