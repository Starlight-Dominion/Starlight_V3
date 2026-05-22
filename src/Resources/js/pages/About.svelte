<script>
    import { fade, fly } from 'svelte/transition';
</script>

<div in:fade class="max-w-5xl mx-auto py-32 px-6 space-y-20">
    <header in:fly={{ y: 20, duration: 800 }} class="text-center space-y-4">
        <h1 class="text-6xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Command Manual</h1>
        <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[8px] italic">Understanding the Mechanics of Galactic Power</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <section in:fade={{ delay: 200 }} class="bg-dark-translucent border border-white/5 p-10 rounded-3xl space-y-6 relative overflow-hidden group hover:border-cyan-500/30 transition-all">
             <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                PULSE
            </div>
            <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl border-l-4 border-cyan-500 pl-6">The Sector Pulse</h2>
            <p class="text-gray-400 leading-relaxed text-sm italic">
                Starlight Dominion operates on a 15-minute **Sector Pulse (Tick)**. Every cycle, the neural infrastructure processes the state of every kingdom. You receive your income, citizens grow, and tactical turns refresh. There is no instant gratification; total dominion is built in the strategic silence between the pulses.
            </p>
        </section>

        <section in:fade={{ delay: 400 }} class="bg-dark-translucent border border-white/5 p-10 rounded-3xl space-y-6 relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                MATH
            </div>
            <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl border-l-4 border-cyan-500 pl-6">1:1 Military Math</h2>
            <p class="text-gray-400 leading-relaxed text-sm italic">
                An army is only as good as its tactical gear. For every soldier you train, you must provide the necessary loadout from the **Sector Armory**. A soldier without gear operates at significantly reduced efficiency. Mastering the ratio of recruits to armory stock is the key to dominating the Targeting Array.
            </p>
        </section>
    </div>

    <div in:fade={{ delay: 600 }} class="bg-cyan-950/10 border border-cyan-500/20 p-12 rounded-3xl text-center space-y-6">
        <h3 class="text-white font-title font-black uppercase tracking-[4px]">Expansion Protocols</h3>
        <p class="text-gray-500 max-w-2xl mx-auto text-sm leading-relaxed">
            Your Dominion's reach is limited only by your strategic foresight. Invest in structural evolution, maintain high sector integrity, and coordinate with fellow Sovereigns to secure the persistent realm.
        </p>
        <div class="pt-6">
            <a href="/" class="inline-block bg-cyan-600 hover:bg-cyan-400 text-white font-title font-black px-12 py-5 rounded-2xl uppercase tracking-[4px] transition-all shadow-[0_0_30px_rgba(8,145,178,0.2)]">
                Initialize Command
            </a>
        </div>
    </div>
</div>
