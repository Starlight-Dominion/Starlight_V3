<script>
    import { game, resources, formattedTick } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    const user = $derived(game.user);
    const kingdom = $derived(user?.kingdom || {});
    
    // Legacy Advice Repository Integration
    const adviceRepo = {
        'dashboard/index': [
            "Central command hub active. Monitor resource flow and fleet readiness.",
            "A robust economy is the armor of a lasting dominion.",
            "Telemetry synchronized. Dominion Time is consistent across all sectors."
        ],
        'default': ["Welcome to Starlight Dominion, Commander."]
    };

    const currentAdvice = $derived.by(() => {
        const list = adviceRepo[game.component] || adviceRepo.default;
        return list[Math.floor(Math.random() * list.length)];
    });
</script>

<aside class="lg:col-span-1 space-y-6">
    <!-- A.I. ADVISOR MODULE -->
    <div class="bg-dark-translucent border border-cyan-500/20 rounded-xl overflow-hidden shadow-2xl relative">
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-cyan-400/30"></div>
        
        <header class="bg-cyan-950/20 px-4 py-3 border-b border-cyan-500/10 flex justify-between items-center">
            <h2 class="text-cyan-400 font-title text-[10px] font-black uppercase tracking-[3px]">A.I. Advisor</h2>
            <div class="flex gap-1">
                <span class="w-1 h-1 bg-cyan-500 rounded-full animate-ping"></span>
            </div>
        </header>

        <div class="p-6 space-y-6">
            <p class="text-gray-300 text-xs italic leading-relaxed font-sans border-l-2 border-cyan-500/30 pl-4">
                "{currentAdvice}"
            </p>

            <!-- XP / LEVEL TELEMETRY -->
            <div class="space-y-2 pt-4 border-t border-white/5">
                <div class="flex justify-between items-end">
                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Level {user?.level} Status</span>
                    <span class="text-[9px] font-mono text-cyan-500">{kingdom.xp?.toLocaleString() || 0} XP</span>
                </div>
                <div class="h-1.5 w-full bg-black/50 rounded-full overflow-hidden border border-cyan-500/10">
                    <div 
                        class="h-full bg-cyan-500 shadow-[0_0_10px_rgba(6,182,212,0.5)] transition-all duration-1000" 
                        style="width: {user?.xpProgress || 0}%"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <!-- VITALS TELEMETRY -->
    <div class="bg-dark-translucent border border-cyan-500/20 rounded-xl overflow-hidden shadow-2xl">
        <header class="bg-cyan-950/20 px-4 py-3 border-b border-cyan-500/10">
            <h2 class="text-gray-500 font-title text-[10px] font-black uppercase tracking-[3px]">Sector Vitals</h2>
        </header>
        
        <div class="p-6 space-y-4 font-mono text-[11px]">
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Credits</span>
                <span class="text-white font-bold group-hover:text-cyan-400 transition-colors">{resources.gold.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Secure Bank</span>
                <span class="text-cyan-600 font-bold group-hover:text-cyan-400 transition-colors">{resources.bank.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group">
                <span class="text-gray-600 uppercase">Population</span>
                <span class="text-white font-bold group-hover:text-cyan-400 transition-colors">{resources.citizens.toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center group border-t border-white/5 pt-4">
                <span class="text-gray-600 uppercase">Sortie Turns</span>
                <span class="text-cyan-400 font-bold">{resources.turns}</span>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="text-gray-600 uppercase text-[9px]">Cycle Refresh</span>
                <span class="text-cyan-400 font-bold animate-pulse">{formattedTick.value}</span>
            </div>
        </div>
    </div>
</aside>