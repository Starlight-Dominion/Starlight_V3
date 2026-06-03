<script>
    import { fade } from 'svelte/transition';
    let { 
        battleLogs = []
    } = $props();
</script>

<div in:fade class="space-y-6">
    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <table class="w-full text-left border-collapse font-mono">
            <thead>
                <tr class="bg-cyan-950/20 border-b border-white/5 text-[10px] font-black text-gray-500 uppercase tracking-widest">
                    <th class="px-8 py-5">Time (UTC)</th>
                    <th class="px-8 py-5">Engagement</th>
                    <th class="px-8 py-5">Outcome</th>
                    <th class="px-8 py-5 text-right">Credits Siphoned</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                {#each battleLogs as log}
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="px-8 py-6 text-[10px] font-mono text-gray-600">{new Date(log.created_at).toLocaleString()}</td>
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-black text-white uppercase group-hover:text-cyan-400 transition-colors">{log.attacker_name}</span>
                                <span class="text-[9px] text-gray-700 font-black tracking-[3px]">VS</span>
                                <span class="text-sm font-black text-white uppercase group-hover:text-red-500 transition-colors">{log.defender_name}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-[10px] font-black uppercase tracking-widest {log.result === 'attacker' ? 'text-cyan-500' : 'text-red-900'}">
                                {log.result === 'attacker' ? 'Offensive Victory' : 'Sector Repelled'}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right font-mono text-cyan-400 font-black">+{log.gold_looted.toLocaleString()} CP</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</div>
