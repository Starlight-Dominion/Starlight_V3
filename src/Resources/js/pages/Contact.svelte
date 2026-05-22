<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, fly } from 'svelte/transition';

    let loading = $state(false);
    let message = $state(null);

    let formData = $state({
        email: '',
        subject: 'Diplomatic Inquiry',
        transmission: ''
    });

    async function handleTransmit(e) {
        e.preventDefault();
        loading = true;
        message = { success: true, message: "Transmission received. Signal stable." };
        setTimeout(() => { loading = false; message = null; }, 3000);
    }
</script>

<div in:fade class="max-w-4xl mx-auto py-32 px-6">
    <div class="bg-dark-translucent border border-white/5 rounded-3xl p-12 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-5 pointer-events-none font-title font-black text-white italic text-6xl uppercase">
            SIGNAL
        </div>

        <header class="mb-12 relative z-10">
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Signal Command</h1>
            <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-2 italic">Report anomalies or seek diplomatic ties.</p>
        </header>

        {#if message}
            <div in:fly={{ y: -20 }} class="mb-8 p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
                {message.message}
            </div>
        {/if}

        <form onsubmit={handleTransmit} class="space-y-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label for="contact-email" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Frequency (Email)</label>
                    <input id="contact-email" type="email" bind:value={formData.email} required class="w-full bg-black/60 border border-white/10 rounded-xl px-6 py-4 text-cyan-400 font-mono text-sm focus:border-cyan-500 focus:outline-none transition-all" placeholder="commander@relay.net" />
                </div>

                <div class="space-y-2">
                    <label for="contact-subj" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Protocol (Subject)</label>
                    <div class="relative">
                        <select id="contact-subj" bind:value={formData.subject} class="w-full bg-black/60 border border-white/10 rounded-xl px-6 py-4 text-white appearance-none cursor-pointer focus:border-cyan-500 focus:outline-none uppercase font-black text-[10px] tracking-widest">
                            <option>Diplomatic Inquiry</option>
                            <option>Sabotage Report (Bug)</option>
                            <option>Clan Partnership</option>
                            <option>High Command Signal</option>
                        </select>
                        <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-gray-600">▼</div>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label for="contact-msg" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Transmission</label>
                <textarea id="contact-msg" rows="8" bind:value={formData.transmission} required class="w-full bg-black/60 border border-white/10 rounded-2xl px-6 py-6 text-gray-300 focus:border-cyan-500 focus:outline-none transition-all resize-none leading-relaxed italic" placeholder="Enter tactical update..."></textarea>
            </div>

            <button type="submit" class="w-full bg-cyan-700/50 hover:bg-cyan-500 border border-cyan-500/50 text-white font-title font-black py-6 rounded-2xl uppercase tracking-[4px] transition-all disabled:opacity-30 shadow-[0_0_20px_rgba(8,145,178,0.1)]" disabled={loading}>
                {loading ? 'TRANSMITTING...' : 'Initialize Uplink'}
            </button>
        </form>
    </div>

    <footer class="mt-12 text-center">
        <p class="text-[8px] text-gray-700 uppercase tracking-[4px] font-black">Secure neural channel encrypted via RSA-4096 &bull; Status: STABLE</p>
    </footer>
</div>
