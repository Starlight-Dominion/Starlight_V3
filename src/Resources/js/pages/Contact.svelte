<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fly } from 'svelte/transition';
</script>

<div class="max-w-2xl mx-auto py-20 px-6">
    <div in:fly={{ y: 20 }} class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-10 shadow-2xl">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter mb-2">Signal Command</h1>
        <p class="text-gray-500 text-sm mb-10 uppercase tracking-widest">Report anomalies or seek diplomatic ties.</p>

        <form class="space-y-6">
            <input type="hidden" name="_csrf" value={game.csrf} />
            
            <div class="space-y-2">
                <label for="contact-email" class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Frequency (Email)</label>
                <input id="contact-email" type="email" class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059] transition-all" />
            </div>

            <div class="space-y-2">
                <label for="contact-subj" class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Subject</label>
                <select id="contact-subj" class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059] transition-all">
                    <option>Diplomatic Inquiry</option>
                    <option>Sabotage Report (Bug)</option>
                    <option>Clan Partnership</option>
                </select>
            </div>

            <div class="space-y-2">
                <label for="contact-msg" class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Transmission</label>
                <textarea id="contact-msg" rows="5" class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059] transition-all resize-none"></textarea>
            </div>

            <button type="submit" class="w-full btn-primary py-5">
                Transmit
            </button>
        </form>
    </div>
</div>