<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { 
        tactical = {}, 
        production_total = 0, 
        production_base = 0, 
        production_mines = 0 
    } = $props();

    const user = $derived(game.user);
    
    let activeModules = $state({
        eco: true,
        mil: true,
        manpower: true,
        sec: true
    });

    const toggle = (key) => activeModules[key] = !activeModules[key];

    // Aligning with new TacticalService response
    const totalMilitary = $derived(
        Object.values(tactical.army || {}).reduce((a, b) => a + b, 0)
    );

    const totalPopulation = $derived(totalMilitary + (resources.citizens || 0));

    // Unit classification helper for visual styling
    const getUnitClass = (slug) => {
        if (slug === 'soldiers') return 'text-red-500 border-red-500/20 bg-red-500/5';
        if (slug === 'guards') return 'text-blue-500 border-blue-500/20 bg-blue-500/5';
        if (slug === 'spies') return 'text-purple-500 border-purple-500/20 bg-purple-500/5';
        if (slug === 'sentries') return 'text-orange-500 border-orange-500/20 bg-orange-500/5';
        if (slug === 'workers') return 'text-emerald-500 border-emerald-500/20 bg-emerald-500/5';
        return 'text-gray-500 border-gray-500/20 bg-gray-500/5';
    };
</script>

<div in:fade class="space-y-6">
    <!-- Header Hero Section -->
    <div class="bg-dark-translucent border-2 border-cyan-500/20 rounded-2xl p-8 relative overflow-hidden shadow-2xl">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <span class="text-8xl font-title font-black text-white select-none uppercase tracking-tighter">HUD_01</span>
        </div>

        <div class="flex flex-col md:flex-row gap-10 items-center relative z-10">
            <div class="relative group">
                <div class="w-32 h-32 rounded-full border-4 border-cyan-900/50 overflow-hidden bg-black flex items-center justify-center group-hover:border-cyan-400 transition-all duration-500 shadow-[0_0_30px_rgba(6,182,212,0.2)]">
                    {#if user?.avatar_path}
                        <img src={user.avatar_path} alt="Commander Sigil" class="w-full h-full object-cover" />
                    {:else}
                        <span class="text-cyan-900 font-title font-black text-5xl uppercase select-none group-hover:text-cyan-400">
                            {user?.username?.charAt(0)}
                        </span>
                    {/if}
                </div>
                <div class="absolute inset-0 border-2 border-cyan-400/20 rounded-full animate-pulse pointer-events-none"></div>
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

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Economic Ledger -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden shadow-lg">
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
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest">Credits</span>
                        <span class="text-xl font-black text-white">{resources.credits.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase font-bold">Income</span>
                        <span class="text-cyan-400 font-bold">+{production_total.toLocaleString()} CP</span>
                    </div>
                    <div class="flex justify-between items-center text-[9px] text-gray-700">
                        <span class="uppercase">Base: {production_base}</span>
                        <span class="uppercase">Bonus: {production_mines}</span>
                    </div>
                </div>
            {/if}
        </div>

        <!-- Military Command -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden shadow-lg">
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
                        <span class="text-[10px] text-gray-500 uppercase tracking-widest">Active Sorties (Turns)</span>
                        <span class="text-xl font-black text-cyan-400">{resources.turns}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase font-bold">Offense Power</span>
                        <span class="text-white font-bold">{tactical.ratings?.offense?.toLocaleString() || 0}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-600 uppercase font-bold">Defense Power</span>
                        <span class="text-white font-bold">{tactical.ratings?.defense?.toLocaleString() || 0}</span>
                    </div>
                </div>
            {/if}
        </div>

        <!-- Manpower Roster -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-xl overflow-hidden shadow-lg col-md-2 lg:col-span-1">
            <header class="bg-cyan-950/10 px-6 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                <h3 class="font-title text-cyan-400 text-[10px] font-black uppercase tracking-[3px] flex items-center gap-3">
                    <span class="w-2 h-2 bg-emerald-500 rounded-sm rotate-45"></span>
                    Manpower Roster
                </h3>
                <button onclick={() => toggle('manpower')} class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest">
                    {activeModules.manpower ? 'Minimize' : 'Expand'}
                </button>
            </header>
            {#if activeModules.manpower}
                <div in:slide class="p-6 space-y-3 font-mono">
                    <div class="grid grid-cols-2 gap-2">
                        {#if tactical.manpower && tactical.manpower.length > 0}
                            {#each tactical.manpower as unit}
                                <div class="p-3 border rounded-lg {getUnitClass(unit.slug)} flex flex-col justify-between">
                                    <span class="text-[9px] font-bold uppercase tracking-tighter opacity-60 leading-none mb-2">{unit.name}</span>
                                    <span class="text-lg font-black leading-none">{unit.quantity.toLocaleString()}</span>
                                </div>
                            {/each}
                        {:else}
                            <div class="col-span-2 py-4 text-center text-[10px] text-gray-600 uppercase tracking-widest italic">
                                No active manpower recorded
                            </div>
                        {/if}
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-white/5 flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[9px] text-gray-600 uppercase font-bold tracking-widest">Total Civilian</span>
                            <span class="text-sm font-bold text-white">{resources.citizens?.toLocaleString() || 0}</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-[9px] text-gray-600 uppercase font-bold tracking-widest">Total Pop</span>
                            <span class="text-sm font-bold text-cyan-400">{totalPopulation.toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>

<style>
    .text-shadow-glow {
        text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
    }
</style>
