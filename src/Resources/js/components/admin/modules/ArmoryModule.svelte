<script>
    import { fade } from 'svelte/transition';
    let { 
        armoryItems = [], 
        unitTypes = [], 
        categories = [],
        onInspect,
        onDelete,
        onAdd
    } = $props();
</script>

<div in:fade class="space-y-12">
    {#each unitTypes as uType}
        <section class="space-y-6">
            <div class="flex justify-between items-end border-b border-white/10 pb-4">
                <div>
                    <h3 class="text-2xl font-title font-black text-white uppercase tracking-tighter">{uType.name} Armament</h3>
                    <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[4px] mt-1">{uType.title}</p>
                </div>
            </div>

            {#each categories.filter(c => c.unit_type_id === uType.id) as cat}
                <div class="space-y-4">
                    <div class="flex justify-between items-center px-4">
                        <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">{cat.name}</h4>
                        <button onclick={() => onAdd(uType.slug, cat.id)} class="text-[9px] font-black text-gray-700 uppercase hover:text-cyan-400 transition-colors tracking-widest">+ NEW ASSET</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        {#each armoryItems.filter(i => i.category_id === cat.id) as item}
                            <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl flex justify-between items-center group hover:border-amber-500/30 transition-all">
                                <div class="flex items-center gap-6">
                                    <div class="w-12 h-12 bg-amber-950/20 rounded-lg flex items-center justify-center border border-amber-500/10 text-amber-500 font-black">
                                        {item.slug.substring(0,2).toUpperCase()}
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-white uppercase tracking-tight">{item.name}</h4>
                                        <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">{item.cost.toLocaleString()} CP</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button onclick={() => onDelete(item.id)} class="w-10 h-10 rounded-lg bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                                    <button onclick={() => onInspect(item)} class="px-5 py-3 bg-amber-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-amber-400 transition-all shadow-[0_0_15px_rgba(217,119,6,0.2)]">Calibrate Asset</button>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>
            {/each}
        </section>
    {/each}
</div>
