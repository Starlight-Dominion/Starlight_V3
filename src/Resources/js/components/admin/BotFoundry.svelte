<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { 
        show = $bindable(false), 
        races = [], 
        profiles = [], 
        onCommission
    } = $props();

    let config = $state({
        name: '',
        race_id: null,
        bot_profile_id: null,
        starting_credits: 25000,
        starting_citizens: 500,
        starting_level: 1
    });

    let loading = $state(false);

    async function handleSubmit() {
        loading = true;
        const formData = new FormData();
        formData.append('name', config.name);
        formData.append('race_id', config.race_id || '');
        formData.append('bot_profile_id', config.bot_profile_id || '');
        formData.append('starting_credits', config.starting_credits);
        formData.append('starting_citizens', config.starting_citizens);
        formData.append('starting_level', config.starting_level);
        formData.append('_csrf', game.csrf);

        const success = await onCommission(formData);
        loading = false;
        if (success) {
            show = false;
        }
    }
</script>

{#if show}
    <div in:fade out:fade class="fixed inset-0 z-[1000] flex items-center justify-center p-4 md:p-12">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" onclick={() => show = false}></div>
        <div class="relative w-full max-w-2xl bg-[#050505] border border-white/10 rounded-[3rem] shadow-[0_0_100px_rgba(0,0,0,1)] overflow-hidden flex flex-col">
            <!-- Header -->
            <header class="p-8 md:px-12 md:py-10 border-b border-white/5 flex justify-between items-center">
                <div class="flex items-center gap-8">
                    <div class="w-16 h-16 bg-red-950/20 rounded-lg flex items-center justify-center border border-red-500/20 text-red-500 font-title font-black text-3xl">
                        ☣
                    </div>
                    <div>
                        <h2 class="text-3xl font-title font-black text-white uppercase tracking-tighter leading-none">Bot Foundry</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[4px] mt-2">SINGLE-UNIT COMMISSION PROTOCOL</p>
                    </div>
                </div>
                <button onclick={() => show = false} class="w-12 h-12 rounded-full bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-red-500 hover:border-red-500 transition-all font-black text-xl">×</button>
            </header>

            <!-- Form Area -->
            <div class="p-12 space-y-8 overflow-y-auto max-h-[60vh] custom-scrollbar">
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Bot Designation (Name)</span>
                        <input type="text" bind:value={config.name} placeholder="Commander Name" class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-red-500 outline-none" />
                    </div>
                    <div class="space-y-2">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Evolutionary Strain (Race)</span>
                        <select bind:value={config.race_id} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-white font-mono focus:border-red-500 outline-none uppercase text-xs font-black">
                            <option value={null} disabled>SELECT STRAIN</option>
                            {#each races as race}
                                <option value={race.id}>{race.name.toUpperCase()}</option>
                            {/each}
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Initial Automation Protocol</span>
                    <select bind:value={config.bot_profile_id} class="w-full bg-black/40 border border-white/10 rounded-xl px-6 py-4 text-emerald-500 font-mono focus:border-emerald-500 outline-none uppercase text-xs font-black">
                        <option value={null}>NO PROTOCOL (MANUAL CONTROL)</option>
                        {#each profiles as profile}
                            <option value={profile.id}>{profile.name.toUpperCase()}</option>
                        {/each}
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Credits</span>
                        <input type="number" bind:value={config.starting_credits} class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-emerald-500 font-mono text-sm" />
                    </div>
                    <div class="space-y-2">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Citizens</span>
                        <input type="number" bind:value={config.starting_citizens} class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-cyan-400 font-mono text-sm" />
                    </div>
                    <div class="space-y-2">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest">Starting Level</span>
                        <input type="number" bind:value={config.starting_level} min="1" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-amber-500 font-mono text-sm" />
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="p-8 border-t border-white/5 bg-white/[0.02] flex justify-end">
                <button 
                    onclick={handleSubmit} 
                    disabled={loading || !config.name || !config.race_id}
                    class="px-12 py-5 bg-red-600 text-white rounded-2xl text-[12px] font-black uppercase tracking-widest hover:bg-red-500 transition-all shadow-[0_0_40px_rgba(220,38,38,0.3)] disabled:opacity-50"
                >
                    {loading ? 'INITIALIZING...' : 'EXECUTE PRODUCTION'}
                </button>
            </footer>
        </div>
    </div>
{/if}
