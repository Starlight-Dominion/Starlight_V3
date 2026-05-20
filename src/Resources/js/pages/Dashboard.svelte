<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, fly, slide } from 'svelte/transition';

    const user = $derived(game.user);
    const kingdom = $derived(user?.kingdom || {});
    
    // UI Module Toggle State
    let activeModules = $state({
        eco: true,
        mil: true,
        pop: true,
        fleet: true,
        sec: true
    });

    const toggle = (key) => activeModules[key] = !activeModules[key];

    // Calculated Telemetry
    const totalMilitary = $derived(
        (kingdom.stabled_unit_guards || 0) + 
        (kingdom.stabled_unit_soldiers || 0) + 
        (kingdom.stabled_unit_spies || 0) + 
        (kingdom.stabled_unit_sentries || 0)
    );

    const totalPopulation = $derived(totalMilitary + (resources.citizens || 0) + (kingdom.miners || 0));

    // Formatted Data for HUD
    const goldPerTick = $derived(kingdom.base_gold_per_tick || 100);
</script>

<div in:fade class="space-y-6">
    <!-- COMMANDER PROFILE HEADER -->
    <div class="bg-dark-translucent border-2 border-cyan-500/20 rounded-2xl p-8 relative overflow-hidden shadow-2xl">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <span class="text-8xl font-title font-black text-white select-none">HUD_01</span>
        </div>

        <div class="flex flex-col md:flex-row gap-10 items-center relative z-10">
            <div class="relative group">
                <div class="w-32 h-32 rounded-full border-4 border-cyan-900/50 overflow-hidden bg-black flex items-center justify-center group-hover:border-cyan-400 transition-all duration-500">
                    <span class="text-cyan-900 font-title font-black text-5xl uppercase select-none group-hover:text-cyan-400">
                        {user?.username?.charAt(0)}
                    </span>
                </div>
                <div class="absolute inset-0 border-2 border-cyan-400/20 rounded-full animate-pulse"></div>
            </div>

            <div class="flex-grow space-y-1 text-center md:text-left">
                <h1 class="text-5xl font-title font-black text-white tracking-widest uppercase text-shadow-glow">
                    {user?.username}
                </h1>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 items-center mt-2">
                    <span class="text-cyan-400 font-title text-sm font-bold uppercase tracking-[3px]">Level {user?.level}</span>
                    <span class="w-1 h-1 bg-gray-700 rounded-full"></span>
                    <span class="text-gray-400 font-bold text-xs uppercase tracking-widest">{user?.raceName} Commander</span>
                    <span class="w-1 h-1 bg-gray-700 rounded-full"></span>
                    <span class="text-cyan-600 font-bold text-xs uppercase tracking-widest">Sector: {user?.kingdomName}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TACTICAL MODULES GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- ECONOMIC OVERVIEW -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden transition-all hover:border-cyan-500/30">
            <header class="bg-cyan-950/10 px-6 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                <h3 class="font-title text-cyan-400 text-[10px] font-black uppercase tracking-[3px] flex items-center gap-3">
                    <span class="w-2 h-2 bg-cyan-500 rounded-sm rotate-45"></span>
                    Economic Ledger
                </h3>
                <button onclick={() => toggle('eco')} class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest">
                    {activeModules.eco ? 'Minimize' : 'Expand'}
                </button>
            </header>
            {#if activeModules.eco}
                <div in:slide class="p-8 space-y-4 font-mono">
                    <div class="flex justify-between items-end border-b border-white/5 pb-2">
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest">Liquid Credits</span>
                        <span class="text-xl font-black text-white">{resources.gold.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase">Cycle Growth</span>
                        <span class="text-cyan-400 font-bold">+{goldPerTick.toLocaleString()} CP</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase">Net Worth Rating</span>
                        <span class="text-white font-bold">{(kingdom.net_worth || 0).toLocaleString()}</span>
                    </div>
                </div>
            {/if}
        </div>

        <!-- MILITARY COMMAND -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden transition-all hover:border-cyan-500/30">
            <header class="bg-cyan-950/10 px-6 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                <h3 class="font-title text-cyan-400 text-[10px] font-black uppercase tracking-[3px] flex items-center gap-3">
                    <span class="w-2 h-2 bg-red-600 rounded-sm rotate-45"></span>
                    Military Command
                </h3>
                <button onclick={() => toggle('mil')} class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest">
                    {activeModules.mil ? 'Minimize' : 'Expand'}
                </button>
            </header>
            {#if activeModules.mil}
                <div in:slide class="p-8 space-y-4 font-mono">
                    <div class="flex justify-between items-end border-b border-white/5 pb-2">
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest">Active Sorties</span>
                        <span class="text-xl font-black text-cyan-400">{resources.turns}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase">Offense Power</span>
                        <span class="text-white font-bold">{(kingdom.offense_power || 0).toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-red-900">
                        <span class="uppercase">Combat Losses</span>
                        <span class="font-bold">0.00%</span>
                    </div>
                </div>
            {/if}
        </div>

        <!-- POPULATION CENSUS -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden transition-all hover:border-cyan-500/30">
            <header class="bg-cyan-950/10 px-6 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                <h3 class="font-title text-cyan-400 text-[10px] font-black uppercase tracking-[3px] flex items-center gap-3">
                    <span class="w-2 h-2 bg-cyan-400 rounded-sm rotate-45"></span>
                    Population Census
                </h3>
                <button onclick={() => toggle('pop')} class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest">
                    {activeModules.pop ? 'Minimize' : 'Expand'}
                </button>
            </header>
            {#if activeModules.pop}
                <div in:slide class="p-8 space-y-4 font-mono">
                    <div class="flex justify-between items-end border-b border-white/5 pb-2">
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest">Total Sustained</span>
                        <span class="text-xl font-black text-white">{totalPopulation.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase">Idle Citizens</span>
                        <span class="text-cyan-400 font-bold">{resources.citizens.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase">Mining Division</span>
                        <span class="text-white font-bold">{(kingdom.miners || 0).toLocaleString()}</span>
                    </div>
                </div>
            {/if}
        </div>

        <!-- SECURITY TERMINAL -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden transition-all hover:border-cyan-500/30">
            <header class="bg-cyan-950/10 px-6 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                <h3 class="font-title text-cyan-400 text-[10px] font-black uppercase tracking-[3px] flex items-center gap-3">
                    <span class="w-2 h-2 bg-yellow-500 rounded-sm rotate-45"></span>
                    Security Terminal
                </h3>
                <button onclick={() => toggle('sec')} class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest">
                    {activeModules.sec ? 'Minimize' : 'Expand'}
                </button>
            </header>
            {#if activeModules.sec}
                <div in:slide class="p-8 space-y-4 font-mono text-[10px]">
                    <div class="space-y-1">
                        <span class="text-gray-600 uppercase tracking-tighter block">Relay Uplink IP</span>
                        <span class="text-white font-bold">SYST_NOMINAL_X402</span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-gray-600 uppercase tracking-tighter block">Last Verified Connection</span>
                        <span class="text-cyan-800 font-bold italic">Timestamp: {new Date().toLocaleTimeString()}</span>
                    </div>
                </div>
            {/if}
        </div>

    </div>
</div>

<style>
    /* Add specific HUD decorative classes if not global */
    .text-shadow-glow {
        text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
    }
</style>