<script>
    import { fade } from 'svelte/transition';
    import { game, resources } from '../stores/gameStore.svelte.js';

    let { advisor = null, advice = '', stats = [], children } = $props();

    function formatNumber(num) {
        if (typeof num !== 'number') return num;
        return new Intl.NumberFormat().format(num);
    }

    const level = $derived(Math.floor(Math.sqrt((resources.xp || 0) / 100)) + 1);
    const currentThreshold = $derived(Math.pow(level - 1, 2) * 100);
    const nextThreshold = $derived(Math.pow(level, 2) * 100);
    const xpProgress = $derived(
        nextThreshold > currentThreshold 
            ? Math.max(0, Math.min(100, (((resources.xp || 0) - currentThreshold) / (nextThreshold - currentThreshold)) * 100))
            : 0
    );
</script>

<div in:fade class="content-box rounded-lg p-4 space-y-4 shadow-2xl border-cyan-500/20 bg-gray-900/80 backdrop-blur-md">
    <div class="border-b border-gray-700 pb-2 flex justify-between items-center">
        <h3 class="font-title text-cyan-400 uppercase text-[10px] font-black tracking-[3px] flex items-center">
            <span class="w-2 h-2 bg-cyan-500 rounded-sm rotate-45 mr-2"></span>
            A.I. Advisor
        </h3>
        {#if advisor && advisor.server_time}
            <span class="text-[8px] font-mono text-gray-500 uppercase tracking-tighter">ET: {advisor.server_time.formatted_et}</span>
        {/if}
    </div>
    
    <div class="space-y-4">
        {#if children}
            {@render children()}
        {:else if advisor && advisor.advice}
            <p class="text-[11px] leading-relaxed text-gray-300 italic border-l-2 border-cyan-500/30 pl-3">"{advisor.advice}"</p>
        {:else if advice}
            <p class="text-[11px] leading-relaxed text-gray-300 italic border-l-2 border-cyan-500/30 pl-3">"{advice}"</p>
        {/if}

        {#if advisor && advisor.latest_news}
            <div class="bg-cyan-900/10 border border-cyan-500/10 rounded p-3">
                <p class="text-[8px] uppercase font-black text-cyan-600 mb-1 tracking-[2px]">Latest Intelligence</p>
                <p class="text-[10px] text-white font-bold leading-tight">{advisor.latest_news.title}</p>
                <p class="text-[8px] text-gray-500 mt-1 uppercase font-mono">{advisor.latest_news.date}</p>
            </div>
        {/if}

        <div class="pt-3 border-t border-gray-800">
            <div class="flex justify-between text-[9px] uppercase font-black mb-1 tracking-widest">
                <span class="text-gray-400">Level {level} Progress</span>
                <span class="text-cyan-600">{formatNumber(resources.xp || 0)} / {formatNumber(nextThreshold)} XP</span>
            </div>
            <div class="w-full bg-black/60 rounded-full h-1.5 overflow-hidden border border-white/5">
                <div class="bg-cyan-500 h-full rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(6,182,212,0.5)]" style="width: {xpProgress}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2 pt-4 border-t border-gray-800">
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Credits</span>
                <span class="text-white font-bold text-[10px] font-mono">{formatNumber(resources.credits || 0)} <span class="text-[8px] opacity-40">CP</span></span>
            </div>
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Income</span>
                <span class="text-emerald-500 font-bold text-[10px] font-mono">+{formatNumber(resources.income_per_tick || 0)} <span class="text-[8px] opacity-40">CP</span></span>
            </div>
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Secure Bank</span>
                <span class="text-cyan-600 font-bold text-[10px] font-mono">{formatNumber(resources.bank || 0)} <span class="text-[8px] opacity-40">CP</span></span>
            </div>
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Citizens</span>
                <span class="text-white font-bold text-[10px] font-mono">{formatNumber(resources.citizens || 0)}</span>
            </div>
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Growth Rate</span>
                <span class="text-emerald-500 font-bold text-[10px] font-mono">+{formatNumber(resources.citizens_per_tick || 0)} <span class="text-[8px] opacity-40">CIT/CYC</span></span>
            </div>
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Tactical Turns</span>
                <span class="text-cyan-400 font-bold text-[10px] font-mono">{formatNumber(resources.turns || 0)}</span>
            </div>
            <div class="bg-black/20 rounded p-2 border border-white/5 flex justify-between items-center transition-all hover:bg-black/40">
                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Cycle Refresh</span>
                <span class="text-emerald-500 font-bold text-[10px] font-mono animate-pulse">{game.formattedTick}</span>
            </div>

            {#if stats && stats.length > 0}
                {#each stats as stat}
                    <div class="bg-cyan-900/5 rounded p-2 border border-cyan-500/10 flex justify-between items-center transition-all hover:bg-cyan-900/10 mt-1">
                        <span class="text-[9px] uppercase font-bold text-cyan-700 tracking-widest">{stat.label}</span>
                        <span class="{stat.highlight ? 'text-cyan-400' : 'text-white'} font-bold text-[10px] font-mono">
                            {formatNumber(stat.value)}
                            {#if stat.suffix}<span class="text-[8px] ml-0.5 opacity-60 uppercase">{stat.suffix}</span>{/if}
                        </span>
                    </div>
                {/each}
            {/if}
        </div>
    </div>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
