<script>
    import { fade } from 'svelte/transition';
    let { 
        races = [],
        onInspect
    } = $props();
</script>

<div in:fade class="space-y-12">
    <div class="flex justify-between items-center">
        <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Evolutionary Strains</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        {#each races as race}
            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-purple-500/30 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                    {race.name.substring(0,3)}
                </div>
                <div class="space-y-6 relative z-10">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-purple-950/20 border border-purple-900/30 rounded-2xl flex items-center justify-center text-purple-600 text-2xl font-black">
                            {race.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{race.name}</h4>
                            <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">{race.bonus_type}</p>
                        </div>
                    </div>
                    <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                        <span class="block text-[7px] font-black text-purple-900 uppercase tracking-widest mb-1">Neural Multiplier</span>
                        <span class="text-sm font-mono text-purple-400 font-black uppercase">{race.bonus_value}x</span>
                    </div>
                </div>
                <div class="mt-8 relative z-10">
                    <button onclick={() => onInspect(race)} class="w-full py-4 bg-purple-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-700 transition-all shadow-[0_0_20px_rgba(147,51,234,0.2)]">Genetic Calibration</button>
                </div>
            </div>
        {/each}
    </div>
</div>
