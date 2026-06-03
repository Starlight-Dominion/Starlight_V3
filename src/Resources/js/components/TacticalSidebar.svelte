<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    const user = $derived(game.user);

    // Reactive XP Calculations
    const level = $derived(Math.floor(Math.sqrt(resources.xp / 100)) + 1);
    const currentThreshold = $derived(Math.pow(level - 1, 2) * 100);
    const nextThreshold = $derived(Math.pow(level, 2) * 100);
    const xpProgress = $derived(
        nextThreshold > currentThreshold 
            ? Math.max(0, Math.min(100, ((resources.xp - currentThreshold) / (nextThreshold - currentThreshold)) * 100))
            : 0
    );
</script>

<aside class="lg:col-span-1 space-y-6">
    <div class="bg-dark-translucent border border-cyan-500/20 rounded-xl overflow-hidden shadow-2xl relative">
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-cyan-400/30"></div>
        <header class="bg-cyan-950/20 px-4 py-3 border-b border-cyan-500/10 flex justify-between items-center">
            <h2 class="text-cyan-400 font-title text-[10px] font-black uppercase tracking-[3px]">A.I. Advisor</h2>
            {#if game.props.ai_advisor_pulse_enabled}
                <span class="w-1 h-1 bg-cyan-500 rounded-full animate-ping"></span>
            {/if}
        </header>
        <div class="p-6 space-y-4">
            <p class="text-gray-300 text-xs italic border-l-2 border-cyan-500/30 pl-4">
                "{user?.advice || 'Welcome, Commander.'}"
            </p>

            {#if game.props.dominion_news}
                <div class="pt-4 border-t border-white/5 space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                        <span class="text-[8px] font-black text-emerald-500 uppercase tracking-[3px]">Dominion News Wire</span>
                    </div>
                    <div class="bg-emerald-950/5 p-3 rounded border border-emerald-500/10">
                        <p class="text-[9px] text-gray-400 leading-relaxed font-mono">
                            {game.props.dominion_news}
                        </p>
                    </div>
                </div>
            {/if}
        </div>
    </div>

    <div class="bg-dark-translucent border border-cyan-500/20 rounded-xl overflow-hidden shadow-2xl">
        <header class="bg-cyan-950/20 px-4 py-3 border-b border-cyan-500/10">
            <h2 class="text-gray-500 font-title text-[10px] font-black uppercase tracking-[3px]">Sector Vitals</h2>
        </header>
        <div class="p-6 space-y-4 font-mono text-[11px]">
            <!-- Commander Level & XP Progression -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-cyan-400 font-bold uppercase tracking-widest text-[10px]">Level {level}</span>
                    <span class="text-gray-500 text-[9px] uppercase tracking-widest">{resources.xp.toLocaleString()} XP</span>
                </div>
                <div class="w-full bg-black/60 rounded-full h-1.5 border border-white/5 overflow-hidden">
                    <div 
                        class="bg-cyan-500 h-1.5 rounded-full shadow-[0_0_8px_#00ffff] transition-all duration-1000 ease-out" 
                        style="width: {xpProgress}%"
                    ></div>
                </div>
            </div>

            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Credits</span>
                <span class="text-white font-bold group-hover:text-cyan-400 transition-colors">{resources.credits.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Income</span>
                <span class="text-cyan-400 font-bold group-hover:text-white transition-colors">+{resources.income_per_tick?.toLocaleString() || 0} CP</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Secure Bank</span>
                <span class="text-cyan-600 font-bold group-hover:text-cyan-400 transition-colors">{resources.bank.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Citizens</span>
                <span class="text-white font-bold group-hover:text-cyan-400 transition-colors">{resources.citizens.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Growth Rate</span>
                <span class="text-white font-bold group-hover:text-cyan-400 transition-colors">+{resources.citizens_per_tick.toLocaleString()} <span class="text-[8px] text-gray-700">CIT/CYC</span></span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Tactical Turns</span>
                <span class="text-cyan-400 font-bold group-hover:text-white transition-colors">{resources.turns.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="text-gray-600 uppercase text-[9px]">Cycle Refresh</span>
                <!-- FIX: Referencing game.formattedTick directly -->
                <span class="text-cyan-400 font-bold animate-pulse">{game.formattedTick}</span>
            </div>
        </div>
    </div>

    {#if user?.is_admin}
        <div in:fade class="bg-red-950/10 border border-red-900/30 rounded-xl overflow-hidden shadow-2xl">
            <header class="bg-red-900/10 px-4 py-3 border-b border-red-900/20 flex justify-between items-center">
                <h2 class="text-red-500 font-title text-[10px] font-black uppercase tracking-[3px]">High Command</h2>
                <span class="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse shadow-[0_0_8px_#ff0000]"></span>
            </header>
            <div class="p-4">
                <a href="/admin" class="block w-full bg-red-900/20 hover:bg-red-900/40 border border-red-900/30 text-red-500 text-center py-2 rounded-lg font-title font-black text-[9px] uppercase tracking-[3px] transition-all">
                    Access Command Center
                </a>
            </div>
        </div>
    {/if}
</aside>