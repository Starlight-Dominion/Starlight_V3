<script>
    import { fade } from 'svelte/transition';
    let { structures = [], player_level = 1 } = $props();
</script>

<div in:fade class="space-y-12 pb-24">
    <header class="border-b border-cyan-500/20 pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Structural Command</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2 italic">The architectural infrastructure of the Dominion.</p>
        </div>

        <div class="bg-dark-translucent border border-cyan-500/20 p-4 rounded-xl flex items-center gap-6">
            <div>
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Sovereign Tier</span>
                <span class="text-xl font-black text-cyan-400">LEVEL {player_level}</span>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {#each structures as structure}
            <a href={structure.link} class="group bg-dark-translucent border border-white/5 p-8 rounded-3xl hover:border-cyan-500/30 transition-all relative overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="text-7xl font-title font-black text-white uppercase select-none leading-none italic">{structure.level}</span>
                </div>

                <div class="relative z-10 space-y-6">
                    <div>
                        <h3 class="text-2xl font-title font-black text-white uppercase tracking-tight group-hover:text-cyan-400 transition-colors">{structure.name}</h3>
                        <p class="text-gray-600 text-[9px] font-black uppercase tracking-widest mt-1">Operational Rank: {structure.level} / {structure.max_level}</p>
                    </div>

                    <p class="text-gray-400 text-[10px] leading-relaxed italic">{structure.description}</p>

                    <div class="pt-6 border-t border-white/5 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full {structure.level > 0 ? 'bg-cyan-500 animate-pulse shadow-[0_0_8px_#00ffff]' : 'bg-gray-800'}"></span>
                            <span class="text-[8px] font-black uppercase tracking-widest {structure.level > 0 ? 'text-cyan-500' : 'text-gray-600'}">
                                {structure.level > 0 ? 'Operational' : 'Awaiting Construction'}
                            </span>
                        </div>
                        <span class="text-white font-title text-[10px] font-black uppercase tracking-[2px] group-hover:text-cyan-400 group-hover:translate-x-1 transition-all">Manage &raquo;</span>
                    </div>
                </div>
            </a>
        {/each}
    </div>
</div>
