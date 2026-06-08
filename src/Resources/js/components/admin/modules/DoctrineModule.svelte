<script>
    let { gameSettings = [], savingId = null, onSaveSetting } = $props();
</script>

<div class="space-y-6">
    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <header class="bg-red-950/20 px-8 py-4 border-b border-red-500/10 flex justify-between items-center">
            <h2 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">War Doctrine Calibration</h2>
        </header>
        <div class="p-8 space-y-6">
            {#each gameSettings.filter(s => s.setting_key.startsWith('battle_')) as setting}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center border-b border-white/5 pb-6 last:border-0 last:pb-0">
                    <div class="md:col-span-1">
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">{setting.setting_key.replace(/battle_/g, '').replace(/_/g, ' ')}</span>
                        <p class="text-[8px] text-gray-600 italic leading-tight">{setting.description}</p>
                    </div>
                    <div class="md:col-span-1">
                        <input type="text" bind:value={setting.setting_value} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-red-500 font-mono text-sm focus:border-red-500 focus:outline-none" />
                    </div>
                    <div class="md:col-span-1 text-right">
                        <button onclick={() => onSaveSetting(setting)} class="px-6 py-2 bg-red-900/20 border border-red-500/30 text-red-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all disabled:opacity-30" disabled={savingId === setting.setting_key}>
                            {savingId === setting.setting_key ? 'CALIBRATING...' : 'COMMIT'}
                        </button>
                    </div>
                </div>
            {/each}
        </div>
    </div>
</div>
