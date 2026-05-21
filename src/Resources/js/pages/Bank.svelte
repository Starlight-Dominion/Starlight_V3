<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { transactions = [] } = $props();
    
    let depositAmount = $state(0);
    let withdrawAmount = $state(0);
    let loading = $state(false);
    let message = $state(null);

    const maxDeposit = $derived(Math.floor(resources.credits * 0.8));
    const maxWithdraw = $derived(resources.bank);
    const depositsLeft = $derived(resources.max_deposits - resources.deposits_today);

    async function handleAction(action, amount) {
        if (loading || amount <= 0) return;
        loading = true;
        message = null;

        const fd = new FormData();
        fd.append('amount', amount);
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
                window.location.reload();
            }
        } catch (e) {
            message = { success: false, message: "Vault connection unstable." };
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="border-b border-cyan-500/20 pb-6 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">The Iron Bank</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2 italic">Securing the liquidity of the Dominion.</p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Daily Authorization</p>
            <p class="text-2xl font-title font-black {depositsLeft > 0 ? 'text-cyan-400' : 'text-red-600'}">{depositsLeft} / {resources.max_deposits}</p>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- ACTION PANELS -->
        <div class="space-y-6">
            <!-- DEPOSIT BOX -->
            <div class="bg-dark-translucent border border-cyan-500/20 rounded-3xl p-8 space-y-6 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <span class="text-4xl font-title font-black text-white italic">DEPOSIT</span>
                </div>
                
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Secure Assets (Credits)</label>
                    <input type="number" bind:value={depositAmount} class="input-terminal text-2xl font-mono text-cyan-400" placeholder="0" />
                    <div class="flex justify-between px-2 pt-1">
                        <span class="text-[8px] font-bold text-gray-600 uppercase tracking-widest">Safe Limit (80%): {maxDeposit.toLocaleString()} CP</span>
                        {#if depositAmount > maxDeposit}
                            <span class="text-[8px] font-black text-red-500 uppercase animate-pulse">Exceeding Security Protocol</span>
                        {/if}
                    </div>
                </div>

                <button 
                    onclick={() => handleAction('deposit', depositAmount)}
                    class="w-full bg-cyan-700/50 hover:bg-cyan-600 border border-cyan-500/50 text-white font-title font-black py-4 rounded-xl uppercase tracking-[3px] transition-all disabled:opacity-30"
                    disabled={loading || depositAmount <= 0 || depositAmount > maxDeposit || depositsLeft <= 0}
                >Secure Assets</button>
            </div>

            <!-- WITHDRAW BOX -->
            <div class="bg-dark-translucent border border-white/5 rounded-3xl p-8 space-y-6 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <span class="text-4xl font-title font-black text-white italic">LIQUIDATE</span>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-black text-gray-600 uppercase tracking-[2px] ml-2">Liquidate Assets (Credits)</label>
                    <input type="number" bind:value={withdrawAmount} class="input-terminal text-2xl font-mono text-gray-400 border-white/10" placeholder="0" />
                    <div class="px-2 pt-1">
                        <span class="text-[8px] font-bold text-gray-600 uppercase tracking-widest">Withdraw Max: {maxWithdraw.toLocaleString()} CP</span>
                    </div>
                </div>

                <button 
                    onclick={() => handleAction('withdraw', withdrawAmount)}
                    class="w-full bg-black/40 hover:bg-black/60 border border-white/5 text-gray-400 py-4 rounded-xl uppercase font-title font-black tracking-[3px] transition-all disabled:opacity-30"
                    disabled={loading || withdrawAmount <= 0 || withdrawAmount > maxWithdraw}
                >Liquidate</button>
            </div>
        </div>

        <!-- HISTORY PANEL -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-3xl overflow-hidden shadow-2xl h-fit">
            <header class="bg-cyan-950/20 px-8 py-4 border-b border-cyan-500/10">
                <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Audit Trail</h2>
            </header>
            <div class="max-h-[580px] overflow-y-auto">
                <table class="w-full text-left font-mono text-[10px]">
                    <thead class="bg-black/40 text-gray-600 uppercase">
                        <tr>
                            <th class="px-8 py-3">Action</th>
                            <th class="px-8 py-3 text-right">Magnitude</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each transactions as tx}
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-8 py-4 uppercase font-bold {tx.transaction_type === 'deposit' ? 'text-cyan-500' : 'text-red-900'}">{tx.transaction_type}</td>
                                <td class="px-8 py-4 text-right text-white">{tx.amount.toLocaleString()} CP</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
