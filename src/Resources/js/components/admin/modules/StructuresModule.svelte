<script>
    import { fade } from 'svelte/transition';
    let { 
        structures = [],
        onAdd,
        onDelete,
        onInspect
    } = $props();
</script>

<div in:fade class="space-y-12">
    <div class="flex justify-between items-center">
        <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Structural Engineering</h3>
        <button onclick={onAdd} class="bg-white text-black font-title font-black text-[10px] px-8 py-3 rounded-xl hover:bg-cyan-500 transition-all uppercase tracking-widest">Commission New Blueprint</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {#each structures as s}
            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-cyan-500/30 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                    {s.details.slug.substring(0,3)}
                </div>
                <div class="space-y-6 relative z-10">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-cyan-950/20 border border-cyan-500/30 rounded-2xl flex items-center justify-center text-cyan-600 text-2xl font-black">
                            {s.details.slug.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{s.details.name}</h4>
                            <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">MAX RANK: {s.details.max_level}</p>
                        </div>
                    </div>
                    <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                        <span class="block text-[7px] font-black text-cyan-900 uppercase tracking-widest mb-1">Upgrade Matrix Status</span>
                        <span class="text-sm font-mono text-cyan-400 font-black uppercase">{s.levels.length} Ranks Configured</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-8 relative z-10">
                    <button onclick={() => onDelete(s.details.id)} class="w-12 h-12 rounded-xl bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                    <button onclick={() => onInspect(s)} class="flex-grow py-4 bg-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all shadow-[0_0_20px_rgba(6,182,212,0.2)]">Structural Calibration</button>
                </div>
            </div>
        {/each}
    </div>
</div>
