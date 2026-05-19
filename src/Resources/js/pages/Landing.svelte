<script>
    import { fade, fly } from 'svelte/transition';
    let { world_stats = { players: 0, battles_24h: 0 } } = $props();
</script>

<div class="relative overflow-hidden pt-20 pb-32 min-h-screen flex flex-col justify-center">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-[#3f6b2f]/10 rounded-full blur-[150px] -z-10 animate-pulse"></div>

    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center space-y-12">
            <div in:fly={{ y: 20, duration: 800 }} class="inline-flex items-center gap-3 px-6 py-2 rounded-full bg-[#2a231e]/50 border border-[#c5a059]/20 text-[#c5a059] text-[10px] font-black tracking-[5px] uppercase">
                <span class="w-2 h-2 rounded-full bg-[#c5a059] animate-ping"></span>
                The Realm is Online
            </div>

            <h1 in:fade={{ delay: 200, duration: 1000 }} class="text-7xl md:text-[10rem] font-black tracking-tighter leading-none text-white select-none">
                RULE THE <br />
                <span class="text-transparent bg-clip-text bg-gradient-to-b from-[#c5a059] to-[#5c4a3e]">SHADOWS.</span>
            </h1>

            <div in:fade={{ delay: 600, duration: 1000 }} class="flex flex-col sm:flex-row justify-center gap-6 pt-10">
                <a href="/register" class="btn-primary py-6 px-16 text-sm">Forge Your Kingdom</a>
                <a href="/login" class="bg-black/40 backdrop-blur-sm border border-[#2a231e] text-[#c5a059] px-16 py-6 rounded-lg font-black text-sm tracking-[3px] uppercase hover:bg-[#2a231e]/50 transition-all">Return to Power</a>
            </div>
        </div>

        <div in:fly={{ y: 50, delay: 800 }} class="mt-40 grid grid-cols-2 md:grid-cols-4 gap-12 border-t border-[#2a231e]/50 pt-20">
            <div class="text-center md:text-left space-y-2">
                <div class="text-4xl font-black text-white">{world_stats.players?.toLocaleString() || 0}</div>
                <div class="text-[10px] font-black text-gray-600 uppercase tracking-[3px]">Sovereigns</div>
            </div>
            <div class="text-center md:text-left space-y-2">
                <div class="text-4xl font-black text-white">600s</div>
                <div class="text-[10px] font-black text-gray-600 uppercase tracking-[3px]">Heartbeat</div>
            </div>
            <div class="text-center md:text-left space-y-2">
                <div class="text-4xl font-black text-white">{world_stats.battles_24h?.toLocaleString() || 0}</div>
                <div class="text-[10px] font-black text-gray-600 uppercase tracking-[3px]">Assaults (24h)</div>
            </div>
            <div class="text-center md:text-left space-y-2">
                <div class="text-4xl font-black text-white">EST</div>
                <div class="text-[10px] font-black text-gray-600 uppercase tracking-[3px]">NY Server Time</div>
            </div>
        </div>
    </div>
</div>