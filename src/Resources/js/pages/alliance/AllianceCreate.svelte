<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';

    let { onNavigate } = $props();

    let name = $state('');
    let tag = $state('');
    let description = $state('');
    let processing = $state(false);
    let error = $state(null);

    async function create() {
        if (!name || !tag) {
            error = "Designation and Tag are mandatory.";
            return;
        }

        processing = true;
        error = null;

        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('tag', tag);
            formData.append('description', description);
            formData.append('_csrf', game.csrf);

            const res = await fetch('/api/alliance/create', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                onNavigate('hub');
            } else {
                error = data.error || data.message || "Establishment failed.";
            }
        } catch (e) {
            error = "Collective Uplink Failed.";
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="max-w-2xl mx-auto space-y-6">
    <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md">
        <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Found New Order</h1>
        <p class="text-emerald-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Establish a sovereign collective under your command.</p>
    </header>

    <div class="bg-gray-900/40 border border-white/5 p-8 rounded-lg backdrop-blur-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-3 space-y-1">
                <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest block">Alliance Designation</label>
                <input 
                    bind:value={name}
                    type="text" 
                    placeholder="e.g. THE STARLIGHT HEGEMONY" 
                    class="w-full bg-black/40 border border-white/10 px-4 py-3 text-xs font-mono text-cyan-400 focus:outline-none focus:border-cyan-500/50 transition-all uppercase"
                />
            </div>
            <div class="space-y-1">
                <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest block">Identifier Tag</label>
                <input 
                    bind:value={tag}
                    type="text" 
                    maxlength="5"
                    placeholder="TAG" 
                    class="w-full bg-black/40 border border-white/10 px-4 py-3 text-xs font-mono text-cyan-400 focus:outline-none focus:border-cyan-500/50 transition-all uppercase text-center"
                />
            </div>
        </div>

        <div class="space-y-1">
            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest block">Public Mission Statement</label>
            <textarea 
                bind:value={description}
                placeholder="A brief overview of your collective's objectives..." 
                rows="4"
                class="w-full bg-black/40 border border-white/10 px-4 py-3 text-xs font-mono text-gray-400 focus:outline-none focus:border-cyan-500/50 transition-all uppercase"
            ></textarea>
        </div>

        {#if error}
            <div class="bg-red-500/10 border border-red-500/50 p-4 rounded text-[10px] font-black text-red-400 uppercase tracking-widest text-center">
                {error}
            </div>
        {/if}

        <div class="pt-4 flex flex-col items-center gap-4">
            <button 
                onclick={create}
                disabled={processing}
                class="w-full max-w-sm bg-emerald-600/20 border border-emerald-500/50 py-4 text-xs font-black text-emerald-400 uppercase tracking-[5px] hover:bg-emerald-500/40 transition-all disabled:opacity-50"
            >
                {processing ? 'ESTABLISHING PROTOCOL...' : 'INITIALIZE COLLECTIVE'}
            </button>
            <p class="text-[8px] text-gray-600 uppercase font-bold tracking-widest">Initialization Cost: 1,000,000 Credits</p>
        </div>
    </div>

    <div class="flex justify-center">
        <button onclick={() => onNavigate('hub')} class="text-[10px] font-black text-gray-500 uppercase tracking-[4px] hover:text-white transition-all">
            &lt; Cancel Protocol
        </button>
    </div>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
