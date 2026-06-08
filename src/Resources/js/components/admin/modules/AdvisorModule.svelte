<script>
    import { fade, slide } from 'svelte/transition';
    let { 
        gameSettings = [], 
        savingId = null, 
        onSaveSetting 
    } = $props();

    const advisorMessages = $derived(gameSettings.find(s => s.setting_key === 'ai_advisor_messages'));
    const dominionNews = $derived(gameSettings.find(s => s.setting_key === 'dominion_news'));
    const pulseEnabled = $derived(gameSettings.find(s => s.setting_key === 'ai_advisor_pulse_enabled'));
</script>

<div in:fade class="space-y-8">
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">A.I. Advisor Panel</h3>
            <p class="text-gray-500 font-bold uppercase tracking-[4px] text-[10px] mt-2 italic">Curate the Dominion's neural guidance and broadcast sector intelligence.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Advisor Messages -->
        <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
            <header class="bg-cyan-950/20 px-8 py-5 border-b border-white/5 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Neural Guidance Matrix</h2>
                {#if advisorMessages}
                    <button onclick={() => onSaveSetting(advisorMessages)} class="text-[9px] font-black text-cyan-500 hover:text-white uppercase tracking-widest disabled:opacity-30" disabled={savingId === advisorMessages.setting_key}>
                        {savingId === advisorMessages.setting_key ? 'SYNCING...' : 'COMMIT ADVICE'}
                    </button>
                {/if}
            </header>
            <div class="p-8 flex-grow space-y-4">
                <span class="block text-[8px] font-black text-gray-500 uppercase tracking-widest mb-2">Random Advice Pool (One per line)</span>
                {#if advisorMessages}
                    <textarea 
                        bind:value={advisorMessages.setting_value} 
                        class="w-full h-96 bg-black/60 border border-white/10 rounded-2xl p-6 text-cyan-400 font-mono text-sm focus:border-cyan-500 focus:outline-none leading-relaxed"
                        placeholder="Enter messages to be randomly displayed by the advisor..."
                    ></textarea>
                {:else}
                    <div class="h-96 flex items-center justify-center text-gray-700 font-black text-[10px] uppercase tracking-widest">Settings Link Lost</div>
                {/if}
            </div>
        </div>

        <!-- Dominion News & UI Toggles -->
        <div class="space-y-8">
            <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
                <header class="bg-emerald-950/20 px-8 py-5 border-b border-white/5 flex justify-between items-center">
                    <h2 class="text-[10px] font-black text-emerald-500 uppercase tracking-[4px]">Dominion News Wire</h2>
                    {#if dominionNews}
                        <button onclick={() => onSaveSetting(dominionNews)} class="text-[9px] font-black text-emerald-500 hover:text-white uppercase tracking-widest disabled:opacity-30" disabled={savingId === dominionNews.setting_key}>
                            {savingId === dominionNews.setting_key ? 'SYNCING...' : 'PUBLISH NEWS'}
                        </button>
                    {/if}
                </header>
                <div class="p-8 space-y-4">
                    <span class="block text-[8px] font-black text-gray-500 uppercase tracking-widest mb-2">Active Intelligence Broadcast</span>
                    {#if dominionNews}
                        <textarea 
                            bind:value={dominionNews.setting_value} 
                            class="w-full h-48 bg-black/60 border border-white/10 rounded-2xl p-6 text-emerald-500 font-mono text-sm focus:border-emerald-500 focus:outline-none leading-relaxed"
                            placeholder="Enter current sector news..."
                        ></textarea>
                    {:else}
                        <div class="h-48 flex items-center justify-center text-gray-700 font-black text-[10px] uppercase tracking-widest">Settings Link Lost</div>
                    {/if}
                </div>
            </div>

            <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                <header class="bg-purple-950/20 px-8 py-5 border-b border-white/5 flex justify-between items-center">
                    <h2 class="text-[10px] font-black text-purple-500 uppercase tracking-[4px]">Neural Pulse Configuration</h2>
                </header>
                <div class="p-8 space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-[9px] font-black text-white uppercase tracking-widest">Notification Pulse</span>
                            <p class="text-[8px] text-gray-600 italic mt-1">Control the blinking 'animate-ping' effect on the A.I. Advisor header.</p>
                        </div>
                        {#if pulseEnabled}
                            <div class="flex items-center gap-4">
                                <select 
                                    bind:value={pulseEnabled.setting_value} 
                                    class="bg-black/60 border border-white/10 text-[10px] text-cyan-400 rounded-lg px-4 py-2 focus:outline-none uppercase font-black"
                                >
                                    <option value="1">ENABLED (PULSING)</option>
                                    <option value="0">DISABLED (STATIC)</option>
                                </select>
                                <button 
                                    onclick={() => onSaveSetting(pulseEnabled)} 
                                    class="px-6 py-2 bg-purple-900/20 border border-purple-500/30 text-purple-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-purple-500 hover:text-white transition-all disabled:opacity-30"
                                    disabled={savingId === pulseEnabled.setting_key}
                                >
                                    {savingId === pulseEnabled.setting_key ? '...' : 'COMMIT'}
                                </button>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
