<script>
    import { fade } from 'svelte/transition';
    let { 
        searchQuery = $bindable(''), 
        searchResults = [], 
        loading = false,
        onSearch,
        onImpersonate,
        onInspect,
        onShowBotFoundry
    } = $props();
</script>

<div in:fade class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Sovereign Oversight</h3>
            <p class="text-gray-500 font-bold uppercase tracking-[4px] text-[10px] mt-2 italic">Monitor and manage all active sectors and automated drones.</p>
        </div>
        <div class="flex gap-4">
            <button 
                onclick={onShowBotFoundry}
                class="px-8 py-4 bg-red-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-[0_0_20px_rgba(127,29,29,0.2)]"
            >
                Bot Foundry
            </button>
        </div>
    </div>
    <div class="flex gap-4">
        <input 
            type="text" 
            bind:value={searchQuery} 
            placeholder="Search kingdoms..." 
            class="flex-grow bg-black/60 border border-white/10 rounded-xl px-6 py-4 text-white focus:border-red-900 focus:outline-none font-mono" 
            onkeydown={(e) => e.key === 'Enter' && onSearch()} 
        />
        <button onclick={onSearch} class="bg-red-900 text-white px-8 rounded-xl font-title font-black text-[10px] uppercase tracking-widest hover:bg-red-700 transition-all">Search</button>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        {#each searchResults as kingdom}
            <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl flex justify-between items-center group hover:border-cyan-500/30 transition-all">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-full bg-cyan-950/30 border border-cyan-500/20 flex items-center justify-center text-cyan-500 font-title font-black">
                        {kingdom.id}
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white uppercase tracking-tight">{kingdom.name}</h4>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">CDR: {kingdom.user.username}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick={() => onImpersonate(kingdom.user.id)} class="px-4 py-2 bg-red-950/20 text-red-500 border border-red-900/30 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all opacity-0 group-hover:opacity-100">Impersonate</button>
                    <button onclick={() => onInspect(kingdom.id)} class="px-6 py-3 bg-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all shadow-[0_0_15px_rgba(6,182,212,0.2)]">Sovereign Inspector</button>
                </div>
            </div>
        {/each}
    </div>
</div>
