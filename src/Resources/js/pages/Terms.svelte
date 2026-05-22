<script>
    import { fade } from 'svelte/transition';
</script>

<div in:fade class="max-w-4xl mx-auto py-32 px-6 space-y-12">
    <header class="border-b border-cyan-500/20 pb-8 text-center md:text-left">
        <h1 class="text-5xl md:text-6xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Sector Laws</h1>
        <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[6px] mt-4 italic">The Operational Framework of Starlight Dominion</p>
    </header>

    <div class="grid grid-cols-1 gap-8">
        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                INTEGRITY
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-500 text-sm">01.</span> Account Integrity
                </h2>
                <p class="text-gray-400 text-sm leading-relaxed border-l-2 border-cyan-500/20 pl-8 italic">
                    One (1) command identity per Sovereign. Initializing multiple sectors via alternative comms frequencies constitutes espionage against the neural infrastructure and is punishable by total decommissioning.
                </p>
            </section>
        </div>

        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                SOVEREIGNTY
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-500 text-sm">02.</span> Automatic Infiltration
                </h2>
                <p class="text-gray-400 text-sm leading-relaxed border-l-2 border-cyan-500/20 pl-8 italic">
                    Automated scripts, bots, and macros are strictly prohibited. Your sovereignty must be maintained by your own hand. Violation of this protocol results in immediate neural detachment.
                </p>
            </section>
        </div>
    </div>

    <footer class="text-center pt-12">
        <p class="text-[9px] text-gray-700 uppercase tracking-widest font-black italic">
            By initializing command, you agree to adhere to the aforementioned sector laws.
        </p>
    </footer>
</div>
