<script>
    import { fade } from 'svelte/transition';
    let { 
        gameSettings = [], 
        savingId = null, 
        onSaveSetting 
    } = $props();
</script>

<div in:fade class="space-y-6">
    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <header class="bg-cyan-950/20 px-8 py-4 border-b border-cyan-500/10">
            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Sector Documentation</h2>
        </header>
        <div class="p-8 space-y-8">
            {#each gameSettings.filter(s => s.setting_key === 'official_rules') as setting}
                <div class="space-y-4">
                    <div class="flex justify-between items-end">
                        <div>
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Official Rules (Markdown)</span>
                            <p class="text-[8px] text-gray-600 italic leading-tight">Primary protocols displayed on the public /rules page.</p>
                        </div>
                        <button onclick={() => onSaveSetting(setting)} class="px-8 py-3 bg-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all disabled:opacity-30" disabled={savingId === setting.setting_key}>
                            {savingId === setting.setting_key ? 'UPLOADING...' : 'COMMIT CHANGES'}
                        </button>
                    </div>
                    <textarea bind:value={setting.setting_value} class="w-full bg-black/60 border border-white/10 rounded-2xl p-8 text-cyan-400 font-mono text-sm focus:border-cyan-500 focus:outline-none min-h-[600px] leading-relaxed" placeholder="# Neural Protocols..."></textarea>
                </div>
            {/each}
        </div>
    </div>
</div>
