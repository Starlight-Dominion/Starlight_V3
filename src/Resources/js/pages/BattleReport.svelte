<script>
    import { fade, slide } from 'svelte/transition';
    let { log, attacker, defender } = $props();

    function n(num) { return new Intl.NumberFormat().format(num); }
</script>

<div in:fade class="space-y-8 pb-20 max-w-4xl mx-auto">
    <header class="flex justify-between items-end border-b border-cyan-500/20 pb-6">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">
                Engagement Log #{log.id}
            </h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2">
                Telemetry Synchronized
            </p>
        </div>
        <a href="/combat/battlefield" class="text-[10px] font-black text-cyan-400 uppercase tracking-widest hover:text-white transition-all">
            &larr; Return to War Room
        </a>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Attacker Card -->
        <div class="bg-dark-translucent border border-cyan-500/20 rounded-3xl p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-4xl font-title font-black text-white italic">OFFENSE</span>
            </div>
            <h2 class="text-2xl font-title font-black text-white uppercase">{attacker.name}</h2>
            
            <div class="mt-8 space-y-6">
                <div>
                    <span class="text-[9px] text-gray-500 uppercase font-black tracking-widest block mb-1">Effective Firepower</span>
                    <span class="text-3xl font-mono font-bold text-white">{n(log.attacker_damage)}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                    <div>
                        <span class="text-[8px] text-gray-600 uppercase font-bold">Fatigue Casualties</span>
                        <span class="block text-red-500 font-mono font-bold">{n(log.attacker_soldiers_lost)}</span>
                    </div>
                    <div>
                        <span class="text-[8px] text-gray-600 uppercase font-bold">XP Harvested</span>
                        <span class="block text-cyan-400 font-mono font-bold">+{n(log.attacker_xp_gained)}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Defender Card -->
        <div class="bg-dark-translucent border border-cyan-500/20 rounded-3xl p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-4xl font-title font-black text-white italic">DEFENSE</span>
            </div>
            <h2 class="text-2xl font-title font-black text-white uppercase">{defender.name}</h2>

            <div class="mt-8 space-y-6">
                <div>
                    <span class="text-[9px] text-gray-500 uppercase font-black tracking-widest block mb-1">Barrier Efficiency</span>
                    <span class="text-3xl font-mono font-bold text-white">{n(log.defender_damage)}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                    <div>
                        <span class="text-[8px] text-gray-600 uppercase font-bold">Garrison Losses</span>
                        <span class="block text-red-500 font-mono font-bold">-{n(log.guards_lost)}</span>
                    </div>
                    <div>
                        <span class="text-[8px] text-gray-600 uppercase font-bold">Loot Siphon</span>
                        <span class="block text-red-900 font-mono font-bold">-{n(log.credits_stolen)} CP</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Outcome -->
    <div class="bg-black/60 border border-cyan-500/30 rounded-3xl p-12 text-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-5 pointer-events-none bg-[url('/images/grid.png')]"></div>
        
        {#if log.outcome === 'victory'}
            <h2 class="text-7xl font-title font-black text-cyan-400 uppercase tracking-tighter italic mb-4">Strategic Victory</h2>
            <p class="text-gray-400 max-w-md mx-auto text-sm leading-relaxed">
                Primary objectives achieved. Secured <span class="text-white font-bold">{n(log.credits_stolen)} Credits</span> from the target sector.
            </p>
            {#if log.loot_factor < 1.0}
                <div in:slide class="mt-6 inline-block px-4 py-1 bg-red-900/20 border border-red-500/50 rounded-full">
                    <span class="text-[10px] font-black text-red-400 uppercase tracking-widest">Anti-Farm Siphon Active: {log.loot_factor * 100}% Yield</span>
                </div>
            {/if}
        {:else}
            <h2 class="text-7xl font-title font-black text-red-900 uppercase tracking-tighter italic mb-4">Assault Failed</h2>
            <p class="text-gray-400 max-w-md mx-auto text-sm leading-relaxed">
                The target's barriers held. Expeditionary forces forced into emergency warp-out.
            </p>
        {/if}
    </div>
</div>