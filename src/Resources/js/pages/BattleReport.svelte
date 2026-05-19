<script>
    import { fade } from 'svelte/transition';
    let { log, attacker, defender } = $props();

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
</script>

<div in:fade class="space-y-8 pb-20 max-w-4xl mx-auto">
    <header class="flex justify-between items-end border-b border-[#2a231e] pb-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Battle Report</h1>
            <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2">Historical Record of Engagement #{log.id}</p>
        </div>
        <a href="/combat/battlefield" class="text-[9px] font-black text-[#c5a059] uppercase tracking-widest hover:text-white transition-colors">
            &larr; Back to Battlefield
        </a>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Attacker -->
        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4">
                <span class="text-[10px] font-black text-gray-700 uppercase tracking-widest">Attacker</span>
            </div>
            <h2 class="text-2xl font-black text-white mb-1">{attacker.kingdom_name}</h2>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-6">Commander: {attacker.user.username}</p>
            
            <div class="space-y-4">
                <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[2px]">Army Deployed</h3>
                <div class="grid grid-cols-2 gap-4">
                    {#each Object.entries(log.attacker_units) as [type, count]}
                        <div class="bg-[#1a1a1a] p-3 rounded-xl border border-[#2a231e]">
                            <span class="text-[8px] text-gray-600 uppercase block font-bold mb-1">{type}</span>
                            <span class="text-white font-mono font-bold">{formatNumber(count)}</span>
                        </div>
                    {/each}
                </div>
                <div class="mt-6 pt-6 border-t border-[#2a231e]">
                    <div class="flex justify-between items-end">
                        <div>
                            <span class="text-[8px] text-gray-600 uppercase block font-bold">Casualties</span>
                            <span class="text-red-500 font-black text-xl">{log.attacker_loss_percent}%</span>
                        </div>
                        {#if log.result === 'attacker'}
                            <div class="text-right">
                                <span class="text-[8px] text-gray-600 uppercase block font-bold">Looted</span>
                                <span class="text-[#c5a059] font-black text-xl">+{formatNumber(log.gold_looted)} Gold</span>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        <!-- Defender -->
        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4">
                <span class="text-[10px] font-black text-gray-700 uppercase tracking-widest">Defender</span>
            </div>
            <h2 class="text-2xl font-black text-white mb-1">{defender.kingdom_name}</h2>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-6">Commander: {defender.user.username}</p>
            
            <div class="space-y-4">
                <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[2px]">Garrison</h3>
                <div class="grid grid-cols-2 gap-4">
                    {#each Object.entries(log.defender_units) as [type, count]}
                        <div class="bg-[#1a1a1a] p-3 rounded-xl border border-[#2a231e]">
                            <span class="text-[8px] text-gray-600 uppercase block font-bold mb-1">{type}</span>
                            <span class="text-white font-mono font-bold">{formatNumber(count)}</span>
                        </div>
                    {/each}
                </div>
                <div class="mt-6 pt-6 border-t border-[#2a231e]">
                    <div class="flex justify-between items-end">
                        <div>
                            <span class="text-[8px] text-gray-600 uppercase block font-bold">Casualties</span>
                            <span class="text-red-500 font-black text-xl">{log.defender_loss_percent}%</span>
                        </div>
                        {#if log.result === 'attacker'}
                            <div class="text-right">
                                <span class="text-[8px] text-gray-600 uppercase block font-bold">Lost</span>
                                <span class="text-red-900 font-black text-xl">-{formatNumber(log.gold_looted)} Gold</span>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conclusion -->
    <div class="bg-[#1a1a1a] border border-[#2a231e] rounded-3xl p-12 text-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black"></div>
        </div>
        
        <span class="text-[10px] font-black text-gray-600 uppercase tracking-[5px] mb-4 block">Engagement Outcome</span>
        
        {#if log.result === 'attacker'}
            <h2 class="text-6xl font-black text-[#3f6b2f] uppercase tracking-tighter mb-4 italic">Victory</h2>
            <p class="text-gray-400 max-w-md mx-auto text-sm leading-relaxed">
                The assault was successful. Your forces overwhelmed the garrison of {defender.kingdom_name} and secured {formatNumber(log.gold_looted)} gold in spoils.
            </p>
        {:else}
            <h2 class="text-6xl font-black text-red-900 uppercase tracking-tighter mb-4 italic">Defeat</h2>
            <p class="text-gray-400 max-w-md mx-auto text-sm leading-relaxed">
                Your forces were repelled by the defenders of {defender.kingdom_name}. The retreat was costly, and no spoils were recovered.
            </p>
        {/if}
        
        <div class="mt-12 flex justify-center gap-8">
            <div class="text-center">
                <span class="text-[8px] text-gray-600 uppercase block font-bold mb-1">Turns Spent</span>
                <span class="text-white font-mono text-lg">{log.turns_spent}</span>
            </div>
            <div class="text-center">
                <span class="text-[8px] text-gray-600 uppercase block font-bold mb-1">XP Gained</span>
                <span class="text-white font-mono text-lg">{log.result === 'attacker' ? log.turns_spent * 5 : 0}</span>
            </div>
            <div class="text-center">
                <span class="text-[8px] text-gray-600 uppercase block font-bold mb-1">Date</span>
                <span class="text-white font-mono text-lg">{new Date(log.created_at).toLocaleDateString()}</span>
            </div>
        </div>
    </div>
</div>