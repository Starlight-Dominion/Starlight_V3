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

<header class="sticky top-0 z-50 bg-black/95 backdrop-blur-md border-b border-[#2a231e] shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
        
        <div class="flex items-center gap-10">
            <a href="/dashboard" class="text-[#c5a059] text-xl font-black tracking-tighter uppercase select-none">
                Shadow Reign
            </a>
            
            <nav class="hidden md:flex items-center gap-6">
                <!-- HOME GROUP -->
                <div class="relative">
                    <button 
                        onclick={(e) => toggleMenu('home', e)}
                        class="text-[10px] font-black tracking-[2px] uppercase flex items-center gap-1 transition-colors {activeMenu === 'home' ? 'text-white' : 'text-gray-500 hover:text-gray-300'}"
                    >
                        Home <span class="text-[8px] opacity-50">▼</span>
                    </button>
                    {#if activeMenu === 'home'}
                        <div in:fade={{ duration: 100 }} class="absolute top-full left-0 mt-4 w-48 bg-[#0f0f0f] border border-[#2a231e] rounded-xl shadow-2xl py-2 overflow-hidden">
                            <a href="/dashboard" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Command Center</a>
                            <a href="/settings" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Kingdom Settings</a>
                        </div>
                    {/if}
                </div>

                <!-- STRUCTURES GROUP -->
                <div class="relative">
                    <button 
                        onclick={(e) => toggleMenu('structures', e)}
                        class="text-[10px] font-black tracking-[2px] uppercase flex items-center gap-1 transition-colors {activeMenu === 'structures' ? 'text-white' : 'text-gray-500 hover:text-gray-300'}"
                    >
                        Structures <span class="text-[8px] opacity-50">▼</span>
                    </button>
                    {#if activeMenu === 'structures'}
                        <div in:fade={{ duration: 100 }} class="absolute top-full left-0 mt-4 w-56 bg-[#0f0f0f] border border-[#2a231e] rounded-xl shadow-2xl py-2 overflow-hidden">
                            <a href="/structures" class="block px-4 py-3 text-[10px] font-black uppercase tracking-widest text-[#c5a059] bg-[#1a1a1a] hover:bg-[#222] transition-all">Structures Overview</a>
                            <a href="/structures/foundation" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">The Foundation</a>
                            <a href="/structures/armory" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Royal Armory</a>
                            <a href="/structures/stable" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Unit Stable</a>
                            <a href="/structures/mines" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Deep Mines</a>
                            <a href="/bank" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Iron Bank</a>
                            <a href="/structures/upgrades" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Imperial Upgrades</a>
                        </div>
                    {/if}
                </div>

                <!-- COMBAT GROUP -->
                <div class="relative">
                    <button 
                        onclick={(e) => toggleMenu('combat', e)}
                        class="text-[10px] font-black tracking-[2px] uppercase flex items-center gap-1 transition-colors {activeMenu === 'combat' ? 'text-white' : 'text-gray-500 hover:text-gray-300'}"
                    >
                        Combat <span class="text-[8px] opacity-50">▼</span>
                    </button>
                    {#if activeMenu === 'combat'}
                        <div in:fade={{ duration: 100 }} class="absolute top-full left-0 mt-4 w-52 bg-[#0f0f0f] border border-[#2a231e] rounded-xl shadow-2xl py-2 overflow-hidden">
                            <a href="/combat/battlefield" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">The Battlefield</a>
                            <a href="/spy" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Espionage Hub</a>
                            <a href="/combat/training" class="block px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-[#1a1a1a] hover:text-[#c5a059] transition-all">Army Training</a>
                        </div>
                    {/if}
                </div>
            </nav>
        </div>

        <div class="flex items-center gap-8">
            <div class="hidden lg:flex gap-8 items-center border-l border-[#2a231e] pl-8">
                <div class="flex flex-col items-end">
                    <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">Liquid Gold</span>
                    <div class="flex items-center gap-2">
                        <span class="text-white font-mono font-bold text-sm">{resources.gold.toLocaleString()}</span>
                        <span class="text-[#3f6b2f] text-[9px] font-bold">+{goldPerTick}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">Citizens</span>
                    <span class="text-white font-mono font-bold text-sm">{resources.citizens.toLocaleString()}</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-[#1a1a1a] px-4 py-2 rounded-lg border border-[#2a231e] flex flex-col items-center min-w-[90px]">
                    <span class="text-[7px] font-black text-gray-600 uppercase tracking-widest">Next Tick</span>
                    <span class="text-[#3f6b2f] font-mono font-bold text-sm leading-none mt-1">{formattedTick.value}</span>
                </div>
                <a href="/logout" class="text-[9px] font-black text-red-900 uppercase hover:text-red-500 transition-colors tracking-tighter">Exit</a>
            </div>
        </div>
    </div>
</header>