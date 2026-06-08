<script>
    import { fade } from 'svelte/transition';

    let { payload = {}, onNavigate } = $props();
    let searchTerm = $state('');

    async function search() {
        onNavigate('list', { search: searchTerm });
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
</script>

<div in:fade class="space-y-6">
    <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
        <div class="relative z-10 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Sector Registry</h1>
                <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Authorized manifest of all registered collectives.</p>
            </div>
            <div class="flex gap-2">
                <input 
                    bind:value={searchTerm} 
                    type="text" 
                    placeholder="SCAN TAG/NAME..." 
                    class="bg-black/40 border border-white/10 px-4 py-2 text-[10px] font-mono text-cyan-400 focus:outline-none focus:border-cyan-500/50 transition-all uppercase"
                />
                <button 
                    onclick={search}
                    class="bg-cyan-500/20 border border-cyan-500/50 px-6 py-2 text-[10px] font-black text-cyan-400 uppercase tracking-widest hover:bg-cyan-500/40 transition-all"
                >
                    Query
                </button>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {#each payload.alliances as alliance}
            <div class="bg-gray-900/40 border border-white/5 p-6 rounded-lg backdrop-blur-sm relative group hover:border-cyan-500/30 transition-all">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none group-hover:opacity-10 transition-opacity">
                    <span class="text-4xl font-title font-black text-white italic">{alliance.tag}</span>
                </div>
                
                <h3 class="text-xl font-title font-black text-white uppercase tracking-tighter group-hover:text-cyan-400 transition-colors">{alliance.name}</h3>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[2px] mt-1 line-clamp-2 h-8">{alliance.description || 'No public mission statement.'}</p>
                
                <div class="mt-6 flex justify-between items-center border-t border-white/5 pt-4">
                    <div class="flex gap-4">
                        <div>
                            <span class="text-[8px] font-black text-gray-600 uppercase block">Personnel</span>
                            <span class="text-sm font-mono text-white font-bold">{alliance.members_count || 0}</span>
                        </div>
                        <div>
                            <span class="text-[8px] font-black text-gray-600 uppercase block">Standing</span>
                            <span class="text-sm font-mono text-emerald-500 font-bold">{formatNumber(alliance.war_prestige)}</span>
                        </div>
                    </div>
                    <button 
                        onclick={() => onNavigate('detail', { id: alliance.id })}
                        class="bg-white/5 border border-white/10 px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:bg-cyan-500/20 hover:text-cyan-400 hover:border-cyan-500/50 transition-all"
                    >
                        Intel
                    </button>
                </div>
            </div>
        {:else}
            <div class="col-span-full py-24 text-center opacity-20 italic uppercase tracking-widest">
                No collectives matching query found.
            </div>
        {/each}
    </div>

    <div class="flex justify-center pt-6">
        <button onclick={() => onNavigate('hub')} class="text-[10px] font-black text-gray-500 uppercase tracking-[4px] hover:text-white transition-all">
            &lt; Return to Command
        </button>
    </div>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
