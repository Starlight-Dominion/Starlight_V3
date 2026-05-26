<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        data = $bindable(null), 
        tab = $bindable('identity'),
        savingId = $bindable(null),
        onSave
    } = $props();

</script>

{#if show && data}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-4xl h-full max-h-[70vh] bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 rounded-full bg-purple-950/30 border border-purple-500/20 flex items-center justify-center text-purple-500 font-title font-black text-3xl">
                        {data.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">{data.name}</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">EVOLUTIONARY CALIBRATION // ID: {data.id}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick={() => onSave(data)} class="px-8 py-4 bg-purple-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-400 transition-all shadow-[0_0_30px_rgba(147,51,234,0.3)]" disabled={savingId === data.id}>
                        {savingId === data.id ? 'MUTATING...' : 'COMMIT GENETICS'}
                    </button>
                    <button onclick={() => show = false} class="w-16 h-16 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-2xl">×</button>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <nav class="flex border-b border-white/5 bg-white/[0.02]">
                {#each [
                    { id: 'identity', name: 'Identity & Lore' },
                    { id: 'bonuses', name: 'Neural Bonuses' }
                ] as t}
                    <button 
                        onclick={() => tab = t.id}
                        class="px-10 py-6 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 {tab === t.id ? 'text-purple-400 border-purple-500 bg-purple-500/5' : 'text-gray-600 border-transparent hover:text-gray-400'}"
                    >
                        {t.name}
                    </button>
                {/each}
            </nav>

            <!-- Content Area -->
            <div class="flex-grow overflow-y-auto p-12 custom-scrollbar">
                {#if tab === 'identity'}
                    <div in:fade class="space-y-8">
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Strain Designation (Name)</span>
                            <input type="text" bind:value={data.name} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-purple-500 outline-none" />
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Evolutionary History (Description)</span>
                            <textarea bind:value={data.description} rows="6" class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-gray-400 font-mono text-sm focus:border-purple-500 outline-none resize-none leading-relaxed" placeholder="Enter strain origins and biological traits..."></textarea>
                        </div>
                    </div>
                {:else if tab === 'bonuses'}
                    <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Neural Bonus Class</span>
                            <select bind:value={data.bonus_type} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-purple-500 outline-none uppercase text-xs font-black">
                                <option value="income">Economic Surplus (Income)</option>
                                <option value="production">Industrial Output (Unit Prod)</option>
                                <option value="military">Tactical Prowess (Combat)</option>
                                <option value="citizens">Growth Acceleration (Citizens)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Neural Multiplier (Bonus Value)</span>
                            <input type="number" step="0.01" bind:value={data.bonus_value} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-purple-400 font-title font-black text-3xl focus:border-purple-500 outline-none text-center" />
                            <p class="text-[8px] text-gray-600 italic uppercase mt-2 text-center">Decimal multiplier applied to the base resource logic.</p>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}
