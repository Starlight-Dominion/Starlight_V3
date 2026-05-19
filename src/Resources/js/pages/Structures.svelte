<script>
    import { fade } from 'svelte/transition';
    let { structures = [], player_level = 1 } = $props();
</script>

<div in:fade class="space-y-12 pb-24">
    <header class="border-b border-[#2a231e] pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Kingdom Structures</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">The architectural might of your realm.</p>
        </div>

        <div class="bg-[#0f0f0f] border border-[#2a231e] p-4 rounded-xl flex items-center gap-6">
            <div>
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Sovereign Level</span>
                <span class="text-xl font-black text-[#c5a059]">LEVEL {player_level}</span>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {#each structures as structure}
            <a href={structure.link} class="group bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl hover:border-[#c5a059]/50 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="text-6xl font-black text-white uppercase select-none leading-none">{structure.level}</span>
                </div>

                <div class="relative z-10 space-y-6">
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase tracking-tight group-hover:text-[#c5a059] transition-colors">{structure.name}</h3>
                        <p class="text-gray-500 text-[10px] uppercase tracking-widest mt-1">Rank {structure.level} / {structure.max_level}</p>
                    </div>

                    <p class="text-gray-400 text-xs leading-relaxed">{structure.description}</p>

                    <div class="pt-6 border-t border-[#2a231e] flex justify-between items-center">
                        <span class="text-[8px] font-black uppercase tracking-widest {structure.can_upgrade ? 'text-[#3f6b2f]' : 'text-gray-600'}">{structure.status}</span>
                        <span class="text-[#c5a059] text-[10px] font-black uppercase tracking-[2px] group-hover:translate-x-2 transition-transform">Manage &rarr;</span>
                    </div>
                </div>
            </a>
        {/each}
    </div>
</div>
