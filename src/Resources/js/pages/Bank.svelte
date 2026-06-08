<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    import AiAdvisor from '../components/AiAdvisor.svelte';

    let { transactions = [] } = $props();
    
    let depositAmount = $state(0);
    let withdrawAmount = $state(0);
    let loading = $state(false);
    let message = $state(null);

    let panels = $state({
        deposit: true,
        withdraw: true,
        history: true
    });

    const maxDeposit = $derived(Math.floor(resources.credits * 0.8));
    const maxWithdraw = $derived(resources.bank);
    const isOverLimit = $derived(depositAmount > maxDeposit);
    const depositsLeft = $derived(resources.max_deposits - resources.deposits_today);

    async function handleAction(action, amount) {
        const numericAmount = Number(amount);
        if (loading || numericAmount <= 0) return;
        loading = true;
        message = null;

        const fd = new FormData();
        fd.append('amount', numericAmount);
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/bank/${action}`, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            message = data;
            if (data.success) {
                // Instead of reload, we rely on the store refresh if possible, 
                // but since the controller index is what provides 'transactions', 
                // a reload or re-fetch of page data is needed for history sync.
                setTimeout(() => window.location.reload(), 1000);
            }
        } catch (e) {
            message = { success: false, message: "Vault connection unstable." };
        } finally {
            loading = false;
        }
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-4 pb-20">
    <!-- SIDEBAR ADVISOR -->
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor 
            stats={[
                { label: 'Deposit Capacity', value: depositsLeft, suffix: '/' + resources.max_deposits, highlight: depositsLeft > 0 }
            ]}
        />
    </aside>

    <!-- MAIN BANK CONTENT -->
    <main class="lg:col-span-3 space-y-4">
        
        <!-- Header Panel -->
        <div class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-6xl font-title font-black text-white italic">VAULT_A</span>
            </div>
            
            <div class="relative z-10">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">The Iron Bank</h1>
                <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1">Starlight Financial Integrity Protocol</p>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mt-6">
                    <div class="space-y-1">
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest block">Available Credits</span>
                        <span class="text-xl font-mono font-bold text-white">{formatNumber(resources.credits)} <span class="text-[10px] opacity-40">CP</span></span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest block">Secure Holdings</span>
                        <span class="text-xl font-mono font-bold text-cyan-400">{formatNumber(resources.bank)} <span class="text-[10px] opacity-40">CP</span></span>
                    </div>
                    <div class="space-y-1 col-span-2 md:col-span-1">
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest block">Daily Clearances</span>
                        <span class="text-xl font-title font-black {depositsLeft > 0 ? 'text-emerald-500' : 'text-red-500'}">{depositsLeft} / {resources.max_deposits}</span>
                    </div>
                </div>
            </div>
        </div>

        {#if message}
            <div in:slide class="p-3 rounded border text-[10px] font-black uppercase text-center {message.success ? 'bg-cyan-950/40 border-cyan-500/50 text-cyan-400' : 'bg-red-950/40 border-red-500/50 text-red-500'}">
                {message.message}
            </div>
        {/if}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- DEPOSIT PANEL -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-4 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-title text-cyan-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-cyan-500 rounded-full mr-2"></span>
                        Deposit Protocol
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.deposit = !panels.deposit}>
                        {panels.deposit ? 'Hide' : 'Show'}
                    </button>
                </div>
                
                {#if panels.deposit}
                    <div in:slide class="space-y-4">
                        <p class="text-[10px] text-gray-400 italic leading-relaxed">Secure up to 80% of on-hand assets per clearance.</p>
                        
                        <div class="space-y-2">
                            <input 
                                type="number" 
                                bind:value={depositAmount} 
                                class="w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-xl font-mono text-cyan-400 focus:outline-none focus:border-cyan-500 transition-all"
                                placeholder="0"
                            />
                            <div class="flex justify-between items-center px-1">
                                <button 
                                    class="text-[9px] font-bold text-cyan-600 hover:text-cyan-400 uppercase transition-colors"
                                    onclick={() => depositAmount = maxDeposit}
                                >Max Safe Limit: {formatNumber(maxDeposit)}</button>
                                {#if isOverLimit}
                                    <span class="text-[8px] font-black text-red-500 uppercase animate-pulse">Warning: Risk Detected</span>
                                {/if}
                            </div>
                        </div>

                        <button 
                            onclick={() => handleAction('deposit', depositAmount)}
                            class="w-full bg-cyan-900/30 hover:bg-cyan-900/50 border border-cyan-500/30 text-cyan-400 font-title font-black py-3 rounded-lg uppercase text-[10px] tracking-[2px] transition-all disabled:opacity-20"
                            disabled={loading || depositAmount <= 0 || isOverLimit || depositsLeft <= 0}
                        >Execute Deposit</button>
                    </div>
                {/if}
            </div>

            <!-- WITHDRAW PANEL -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-4 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-title text-gray-400 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full mr-2"></span>
                        Liquidate Holdings
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.withdraw = !panels.withdraw}>
                        {panels.withdraw ? 'Hide' : 'Show'}
                    </button>
                </div>

                {#if panels.withdraw}
                    <div in:slide class="space-y-4">
                        <p class="text-[10px] text-gray-400 italic leading-relaxed">Liquidate secured credits for active deployment.</p>

                        <div class="space-y-2">
                            <input 
                                type="number" 
                                bind:value={withdrawAmount} 
                                class="w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-xl font-mono text-white focus:outline-none focus:border-white/20 transition-all"
                                placeholder="0"
                            />
                            <div class="px-1">
                                <button 
                                    class="text-[9px] font-bold text-gray-600 hover:text-gray-400 uppercase transition-colors"
                                    onclick={() => withdrawAmount = maxWithdraw}
                                >Withdraw Max: {formatNumber(maxWithdraw)}</button>
                            </div>
                        </div>

                        <button 
                            onclick={() => handleAction('withdraw', withdrawAmount)}
                            class="w-full bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 font-title font-black py-3 rounded-lg uppercase text-[10px] tracking-[2px] transition-all disabled:opacity-20"
                            disabled={loading || withdrawAmount <= 0 || withdrawAmount > maxWithdraw}
                        >Execute Liquidation</button>
                    </div>
                {/if}
            </div>
        </div>

        <!-- AUDIT TRAIL (HISTORY) -->
        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm">
            <header class="bg-[#030712]/60 px-6 py-3 border-b border-white/5 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Audit Trail</h2>
                <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.history = !panels.history}>
                    {panels.history ? 'Hide' : 'Show'}
                </button>
            </header>
            
            {#if panels.history}
                <div in:slide class="overflow-x-auto">
                    <table class="w-full text-left font-mono text-[10px]">
                        <thead class="bg-black/40 text-gray-600 uppercase">
                            <tr>
                                <th class="px-6 py-3 font-black">Transaction ID</th>
                                <th class="px-6 py-3 font-black">Protocol</th>
                                <th class="px-6 py-3 text-right font-black">Magnitude</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            {#each transactions as tx}
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 text-gray-500 uppercase">#{tx.id.toString().padStart(6, '0')}</td>
                                    <td class="px-6 py-4 uppercase font-bold {tx.transaction_type === 'deposit' ? 'text-cyan-500' : 'text-red-500'}">
                                        {tx.transaction_type}
                                    </td>
                                    <td class="px-6 py-4 text-right text-white font-bold">
                                        {formatNumber(tx.amount)} CP
                                    </td>
                                </tr>
                            {:else}
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-600 italic uppercase tracking-widest">
                                        No active transaction logs found.
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>

    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
    .text-shadow-glow {
        text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
    }
</style>
