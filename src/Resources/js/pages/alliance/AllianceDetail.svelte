<script>
    import { fade } from 'svelte/transition';

    let { payload = {}, onNavigate } = $props();
    let alliance = $derived(payload.alliance || {});
    let processing = $state(false);
    let message = $state(null);

    async function apply() {
        processing = true;
        message = null;
        try {
            const formData = new FormData();
            formData.append('alliance_id', alliance.id);
            formData.append('_csrf', game.csrf);
            const res = await fetch('/api/alliance/apply', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                message = { type: 'success', text: 'Application transmitted.' };
            } else {
                message = { type: 'error', text: data.error || data.message };
            }
        } catch (e) {
            message = { type: 'error', text: 'Uplink Failed.' };
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="max-w-3xl mx-auto space-y-6">
    <header class="bg-gray-900/60 border border-white/5 rounded-lg p-8 backdrop-blur-md relative overflow-hidden">
        <div class="absolute top-0 right-0 p-6 opacity-10 pointer-events-none">
            <span class="text-8xl font-title font-black text-white italic">{alliance.tag}</span>
        </div>
        
        <div class="relative z-10 space-y-2">
            <button onclick={() => onNavigate('list')} class="text-[8px] font-black text-gray-500 uppercase tracking-widest hover:text-white transition-all mb-4 block">&lt; RETURN TO REGISTRY</button>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter">{alliance.name}</h1>
            <p class="text-cyan-500/60 text-xs font-bold uppercase tracking-[4px] italic">{alliance.description || 'No public mission statement.'}</p>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-900/40 border border-white/5 p-6 rounded-lg backdrop-blur-sm text-center">
            <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block mb-1">Combat Standing</span>
            <span class="text-2xl font-mono font-bold text-white">{alliance.war_prestige || 0}</span>
        </div>
        <div class="bg-gray-900/40 border border-white/5 p-6 rounded-lg backdrop-blur-sm text-center">
            <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block mb-1">Personnel Strength</span>
            <span class="text-2xl font-mono font-bold text-cyan-400">{alliance.members_count || 0}</span>
        </div>
        <div class="bg-gray-900/40 border border-white/5 p-6 rounded-lg backdrop-blur-sm text-center">
            <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest block mb-1">Enlistment Status</span>
            <span class="text-xs font-black {alliance.is_joinable ? 'text-emerald-500' : 'text-red-500'} uppercase tracking-widest">{alliance.is_joinable ? 'OPEN' : 'RESTRICTED'}</span>
        </div>
    </div>

    {#if message}
        <div class="p-4 rounded border text-center text-[10px] font-black uppercase tracking-widest {message.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/50 text-emerald-400' : 'bg-red-500/10 border-red-500/50 text-red-400'}">
            {message.text}
        </div>
    {/if}

    <div class="flex justify-center pt-6">
        {#if alliance.is_joinable}
            <button 
                onclick={apply}
                disabled={processing}
                class="bg-cyan-500/20 border border-cyan-500/50 px-12 py-4 text-xs font-black text-cyan-400 uppercase tracking-[5px] hover:bg-cyan-500/40 transition-all disabled:opacity-50"
            >
                {processing ? 'TRANSMITTING ENLISTMENT...' : 'ENLIST IN COLLECTIVE'}
            </button>
        {:else}
            <div class="bg-white/5 border border-white/10 px-12 py-4 text-xs font-black text-gray-600 uppercase tracking-[5px] cursor-not-allowed">
                REGISTRATION LOCKED
            </div>
        {/if}
    </div>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
