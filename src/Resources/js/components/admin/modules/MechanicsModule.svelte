<script>
    let { gameSettings = [], savingId = null, onSaveSetting } = $props();
</script>

<div class="space-y-8">
    <!-- Comms Relay -->
    <div class="bg-red-950/20 border border-red-900/30 rounded-3xl overflow-hidden shadow-2xl">
        <header class="bg-red-900/20 px-8 py-5 border-b border-red-900/30 flex justify-between items-center">
            <h2 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">Sector Comms Relay (Global Broadcast)</h2>
        </header>
        <div class="p-8 space-y-4">
            {#each gameSettings.filter(s => s.setting_key === 'global_broadcast') as broadcast}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                    <div class="md:col-span-3">
                        <span class="block text-[8px] font-black text-gray-500 uppercase tracking-widest mb-2">Announcement / Maintenance Signal</span>
                        <input type="text" bind:value={broadcast.setting_value} class="w-full bg-black/60 border border-red-900/30 rounded-xl px-6 py-4 text-red-400 font-mono text-sm focus:border-red-500 outline-none" placeholder="Enter global transmission..." />
                    </div>
                    <div class="md:col-span-1 text-right">
                        <button onclick={() => onSaveSetting(broadcast)} class="w-full md:w-auto px-8 py-4 bg-red-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-[0_0_15px_rgba(153,27,27,0.3)]">Transmit</button>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- Recruitment Parameters -->
    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <header class="bg-cyan-950/20 px-8 py-5 border-b border-white/5 flex justify-between items-center">
            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Recruitment Parameters</h2>
        </header>
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {#each [
                { key: 'recruitment_sessions_per_day', label: 'Daily Sessions', icon: '📅' },
                { key: 'recruitment_sessions_per_3days', label: '72H Allocation', icon: '⏳' },
                { key: 'recruitment_clicks_per_session', label: 'Clicks Per Session', icon: '🖱' },
                { key: 'recruitment_click_cooldown_ms', label: 'Click Cooldown (ms)', icon: '⏱' }
            ] as param}
                {@const setting = gameSettings.find(s => s.setting_key === param.key)}
                {#if setting}
                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest">{param.icon} {param.label}</span>
                            <button onclick={() => onSaveSetting(setting)} class="text-[8px] font-black text-cyan-500 hover:text-white uppercase tracking-widest disabled:opacity-30" disabled={savingId === setting.setting_key}>Commit</button>
                        </div>
                        <input type="number" bind:value={setting.setting_value} class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs focus:border-cyan-500 outline-none" />
                    </div>
                {/if}
            {/each}
        </div>
    </div>

    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <header class="bg-cyan-950/20 px-8 py-4 border-b border-cyan-500/10 flex justify-between items-center">
            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Global Balance Parameters</h2>
        </header>
        <div class="p-8 space-y-6">
            {#each gameSettings.filter(s => !s.setting_key.startsWith('battle_') && s.setting_key !== 'global_broadcast' && !s.setting_key.startsWith('recruitment_') && !s.setting_key.startsWith('ai_advisor_') && s.setting_key !== 'dominion_news' && s.setting_key !== 'official_rules') as setting}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center border-b border-white/5 pb-6 last:border-0 last:pb-0">
                    <div class="md:col-span-1">
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">{setting.setting_key.replace(/_/g, ' ')}</span>
                        <p class="text-[8px] text-gray-600 italic leading-tight">{setting.description}</p>
                    </div>
                    <div class="md:col-span-1">
                        <input type="text" bind:value={setting.setting_value} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-cyan-400 font-mono text-sm focus:border-cyan-500 focus:outline-none" />
                    </div>
                    <div class="md:col-span-1 text-right">
                        <button onclick={() => onSaveSetting(setting)} class="px-6 py-2 bg-cyan-900/20 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-cyan-500 hover:text-black transition-all disabled:opacity-30" disabled={savingId === setting.setting_key}>
                            {savingId === setting.setting_key ? 'SYNCING...' : 'UPDATE'}
                        </button>
                    </div>
                </div>
            {/each}
        </div>
    </div>
</div>
