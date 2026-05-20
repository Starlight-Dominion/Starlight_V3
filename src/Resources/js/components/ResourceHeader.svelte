<script>
    import { game, resources, formattedTick } from '../stores/gameStore.svelte.js';
    import { fade } from 'svelte/transition';
    
    let activeMenu = $state(null);

    const toggleMenu = (menu, e) => {
        e.stopPropagation();
        activeMenu = activeMenu === menu ? null : menu;
    };

    const closeAll = () => activeMenu = null;
    const goldPerTick = $derived(game.user?.kingdom?.base_gold_per_tick || 100);
</script>

<svelte:window onclick={closeAll} />

<!-- Higher Z-Index and clear overflow context -->
<header class="sticky top-0 z-[100] bg-dark-translucent border-b border-cyan-500/20 shadow-[0_4px_30px_rgba(0,0,0,0.5)]">
    <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center relative">
        
        <div class="flex items-center gap-12">
            <a href="/dashboard" class="group flex flex-col">
                <span class="text-cyan-400 font-title font-black text-xl tracking-[4px] uppercase text-shadow-glow group-hover:text-white transition-all">
                    Starlight Dominion
                </span>
                <span class="text-[7px] font-mono text-cyan-900 uppercase tracking-[2px] -mt-1">Sector Command Interface</span>
            </a>
            
            <nav class="hidden md:flex items-center gap-8">
                <div class="relative">
                    <button 
                        onclick={(e) => toggleMenu('home', e)}
                        class="text-[9px] font-title font-bold tracking-[2px] uppercase flex items-center gap-2 transition-colors {activeMenu === 'home' ? 'text-cyan-400' : 'text-gray-500 hover:text-gray-300'}"
                    >
                        Command <span class="text-[7px] opacity-40">▼</span>
                    </button>
                    {#if activeMenu === 'home'}
                        <div in:fade={{ duration: 100 }} class="absolute top-full left-0 mt-4 w-48 bg-[#060a19] border border-cyan-500/30 rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.8)] py-2 backdrop-blur-xl z-[110]">
                            <a href="/dashboard" class="block px-4 py-3 text-[9px] font-bold uppercase tracking-widest text-gray-400 hover:bg-cyan-500/10 hover:text-cyan-400 transition-all">Dashboard</a>
                            <a href="/settings" class="block px-4 py-3 text-[9px] font-bold uppercase tracking-widest text-gray-400 hover:bg-cyan-500/10 hover:text-cyan-400 transition-all">System Settings</a>
                        </div>
                    {/if}
                </div>

                <div class="relative">
                    <button 
                        onclick={(e) => toggleMenu('structures', e)}
                        class="text-[9px] font-title font-bold tracking-[2px] uppercase flex items-center gap-2 transition-colors {activeMenu === 'structures' ? 'text-cyan-400' : 'text-gray-500 hover:text-gray-300'}"
                    >
                        Infrastructure <span class="text-[7px] opacity-40">▼</span>
                    </button>
                    {#if activeMenu === 'structures'}
                        <div in:fade={{ duration: 100 }} class="absolute top-full left-0 mt-4 w-56 bg-[#060a19] border border-cyan-500/30 rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.8)] py-2 backdrop-blur-xl z-[110]">
                            <a href="/structures" class="block px-4 py-3 text-[9px] font-black uppercase tracking-widest text-cyan-400 border-b border-white/5 mb-1">Structural Overview</a>
                            <a href="/structures/foundation" class="block px-4 py-3 text-[9px] font-bold uppercase tracking-widest text-gray-400 hover:bg-cyan-500/10 hover:text-cyan-400 transition-all">Foundation</a>
                            <a href="/structures/armory" class="block px-4 py-3 text-[9px] font-bold uppercase tracking-widest text-gray-400 hover:bg-cyan-500/10 hover:text-cyan-400 transition-all">Sector Armory</a>
                            <a href="/structures/stable" class="block px-4 py-3 text-[9px] font-bold uppercase tracking-widest text-gray-400 hover:bg-cyan-500/10 hover:text-cyan-400 transition-all">Unit Stable</a>
                            <a href="/bank" class="block px-4 py-3 text-[9px] font-bold uppercase tracking-widest text-gray-400 hover:bg-cyan-500/10 hover:text-cyan-400 transition-all">The Iron Bank</a>
                        </div>
                    {/if}
                </div>
            </nav>
        </div>

        <div class="flex items-center gap-8">
            <div class="hidden lg:flex gap-8 items-center border-l border-white/5 pl-8">
                <div class="flex flex-col items-end">
                    <span class="text-[7px] font-black text-cyan-900 uppercase tracking-widest">Operational Credits</span>
                    <div class="flex items-center gap-2">
                        <span class="text-white font-mono font-bold text-sm">{resources.gold.toLocaleString()}</span>
                        <span class="text-cyan-600 text-[8px] font-bold">+{goldPerTick}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-black/40 px-4 py-2 rounded border border-cyan-500/20 flex flex-col items-center min-w-[90px]">
                    <span class="text-[7px] font-black text-cyan-800 uppercase tracking-widest">Cycle Sync</span>
                    <span class="text-cyan-400 font-mono font-bold text-sm leading-none mt-1">{formattedTick.value}</span>
                </div>
                <a href="/logout" class="text-[8px] font-black text-red-900 uppercase hover:text-red-500 transition-all tracking-tighter">Terminate Link</a>
            </div>
        </div>
    </div>
</header>