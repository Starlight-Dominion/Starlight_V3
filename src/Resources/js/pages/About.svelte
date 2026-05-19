<script>
    import { fade, fly } from 'svelte/transition';
</script>

<div class="max-w-4xl mx-auto py-20 px-6">
    <div in:fly={{ y: 20, duration: 800 }} class="text-center space-y-4 mb-20">
        <h1 class="text-5xl font-black text-white uppercase tracking-tighter">The Manual</h1>
        <p class="text-[#c5a059] font-bold uppercase tracking-[4px] text-sm">Understanding the Mechanics of Power</p>
    </div>

    <div class="space-y-16">
        <section in:fade={{ delay: 200 }} class="grid md:grid-cols-3 gap-8 items-start">
            <div class="md:col-span-1">
                <h2 class="text-white font-black uppercase tracking-widest border-l-4 border-[#3f6b2f] pl-4">The Heartbeat</h2>
            </div>
            <div class="md:col-span-2 text-gray-500 leading-relaxed text-sm">
                Shadowreign operates on a 10-minute "Tick." Every 600 seconds, the server processes the state of every kingdom. You receive your mining yield, citizens grow, and turns refresh. There is no instant gratification; wealth is built in the quiet moments between the ticks.
            </div>
        </section>

        <section in:fade={{ delay: 400 }} class="grid md:grid-cols-3 gap-8 items-start">
            <div class="md:col-span-1">
                <h2 class="text-white font-black uppercase tracking-widest border-l-4 border-[#c5a059] pl-4">1:1 Military Math</h2>
            </div>
            <div class="md:col-span-2 text-gray-500 leading-relaxed text-sm">
                An army is only as good as its steel. For every Soldier you train, you must provide the necessary loadout from the Royal Armory. A Soldier without gear fights with a massive power penalty. Mastering the ratio of recruits to armory stock is the key to conquering the battlefield.
            </div>
        </section>
    </div>
</div>