<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade } from 'svelte/transition';

    const user = $derived(game.user);
    const kingdom = $derived(user?.kingdom || {});
    const goldPerTick = $derived(kingdom.base_gold_per_tick || 100);
    const unitCount = $derived((kingdom.unit_soldiers || 0) + (kingdom.unit_guards || 0));
</script>

<div in:fade class="space-y-8">
    <div class="card-medieval p-8 bg-gradient-to-r from-[#1a1a1a] to-transparent">
        <div class="flex flex-col md:flex-row gap-10 items-center">
            <div class="relative">
                <div class="w-32 h-32 rounded-full border-4 border-[#2a231e] overflow-hidden bg-black flex items-center justify-center">
                    <span class="text-[#2a231e] font-black text-5xl uppercase select-none">SR</span>
                </div>
                <div class="absolute inset-0 border-2 border-[#c5a059]/20 rounded-full"></div>
            </div>

            <div class="flex-grow space-y-2 text-center md:text-left">
                <h1 class="text-5xl font-black text-white tracking-tighter uppercase">{user?.username}</h1>
                <p class="text-gold font-bold text-xs uppercase tracking-[4px]">Level {user?.level} &bull; Sovereign of {user?.kingdomName}</p>
                
                <div class="flex flex-wrap justify-center md:justify-start gap-8 mt-6">
                    <div>
                        <span class="block text-[8px] text-gray-600 font-black uppercase tracking-widest">Population</span>
                        <span class="text-lg font-bold text-white font-mono">{resources.citizens.toLocaleString()}</span>
                    </div>
                    <div>
                        <span class="block text-[8px] text-gray-600 font-black uppercase tracking-widest">Yield/Tick</span>
                        <span class="text-lg font-bold text-green font-mono">+{goldPerTick}</span>
                    </div>
                    <div>
                        <span class="block text-[8px] text-gray-600 font-black uppercase tracking-widest">Mine Workers</span>
                        <span class="text-lg font-bold text-white font-mono">{kingdom.miners?.toLocaleString() || 0}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="card-medieval">
            <div class="card-header-medieval">
                <h3 class="text-gold text-[10px] font-black uppercase tracking-[3px]">The Ledger</h3>
            </div>
            <div class="p-8 space-y-6">
                <div class="flex justify-between items-end">
                    <span class="text-xs text-gray-600 uppercase tracking-widest font-bold">Liquid Gold:</span>
                    <span class="text-2xl font-black text-white font-mono">{resources.gold.toLocaleString()}</span>
                </div>
                <div class="space-y-4 pt-4 border-t border-[#2a231e]">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-gray-600 uppercase">Gross Income</span>
                        <span class="text-green font-mono font-bold">+{goldPerTick.toLocaleString()} GP</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-medieval">
            <div class="card-header-medieval">
                <h3 class="text-rust text-[10px] font-black uppercase tracking-[3px]">War Council</h3>
            </div>
            <div class="p-8 space-y-8">
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs text-gray-600 uppercase tracking-widest font-bold">Fortification Rating</span>
                        <span class="text-3xl font-black text-white font-mono">{kingdom.foundation_hp?.toLocaleString() || 0}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-0.5 rounded bg-[#3f6b2f]/10 border border-[#3f6b2f]/30 text-green text-[8px] font-bold uppercase">Foundation Lvl {kingdom.foundation_level}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>