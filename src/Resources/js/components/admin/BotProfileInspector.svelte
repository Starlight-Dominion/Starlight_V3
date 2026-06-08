<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        data = $bindable(null), 
        savingId = $bindable(null),
        onSave
    } = $props();

</script>

{#if show && data}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-4xl h-full max-h-[80vh] bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 bg-emerald-950/20 rounded-lg flex items-center justify-center border border-emerald-500/20 text-emerald-500 font-title font-black text-3xl">
                        {data.id ? data.name.substring(0,2).toUpperCase() : '++'}
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">{data.id ? data.name : 'New Bot Protocol'}</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">AUTOMATION CALIBRATION // {data.id ? `ID: ${data.id}` : 'INITIALIZING'}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick={() => onSave(data)} class="px-8 py-4 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-400 transition-all shadow-[0_0_30px_rgba(16,185,129,0.3)]" disabled={savingId === (data.id || 'new-profile')}>
                        {savingId === (data.id || 'new-profile') ? 'CALIBRATING...' : 'COMMIT PROTOCOL'}
                    </button>
                    <button onclick={() => show = false} class="w-16 h-16 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-2xl">×</button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-grow overflow-y-auto p-12 custom-scrollbar space-y-12">
                <div in:fade class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Profile Designation (Name)</span>
                            <input type="text" bind:value={data.name} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-emerald-500 outline-none" placeholder="e.g., Aggressive Expansionist" />
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Action Frequency (Minutes)</span>
                            <input type="number" bind:value={data.action_frequency_minutes} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-emerald-500 outline-none" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Strategic Directives (Description)</span>
                        <textarea bind:value={data.description} rows="3" class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-gray-400 font-mono text-sm focus:border-emerald-500 outline-none resize-none" placeholder="Enter behavioral notes or logic summary..."></textarea>
                    </div>
                </div>

                <div class="space-y-8">
                    <h3 class="text-[10px] font-black text-emerald-500 uppercase tracking-[4px] border-b border-emerald-900/20 pb-4">Weighted Decision Matrix</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-black text-red-500 uppercase tracking-widest">Attack Weight</span>
                                <span class="text-xl font-title font-black text-white">{data.weight_attack}%</span>
                            </div>
                            <input type="range" min="0" max="100" bind:value={data.weight_attack} class="w-full h-1 bg-white/10 rounded-full appearance-none cursor-pointer accent-red-500" />
                        </div>
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest">Build Weight</span>
                                <span class="text-xl font-title font-black text-white">{data.weight_build}%</span>
                            </div>
                            <input type="range" min="0" max="100" bind:value={data.weight_build} class="w-full h-1 bg-white/10 rounded-full appearance-none cursor-pointer accent-blue-500" />
                        </div>
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-black text-amber-500 uppercase tracking-widest">Train Weight</span>
                                <span class="text-xl font-title font-black text-white">{data.weight_train}%</span>
                            </div>
                            <input type="range" min="0" max="100" bind:value={data.weight_train} class="w-full h-1 bg-white/10 rounded-full appearance-none cursor-pointer accent-amber-500" />
                        </div>
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-black text-cyan-500 uppercase tracking-widest">Explore Weight</span>
                                <span class="text-xl font-title font-black text-white">{data.weight_explore}%</span>
                            </div>
                            <input type="range" min="0" max="100" bind:value={data.weight_explore} class="w-full h-1 bg-white/10 rounded-full appearance-none cursor-pointer accent-cyan-500" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
