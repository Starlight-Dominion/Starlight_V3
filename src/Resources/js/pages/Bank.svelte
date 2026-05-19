<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let { transactions = [] } = $props();
    
    let amount = $state(0);
    let loading = $state(false);
    let message = $state(null);

    const maxDeposit = $derived(Math.floor(resources.gold * 0.8));
    const isOverLimit = $derived(amount > maxDeposit);
    const depositRatio = $derived((resources.gold > 0) ? (amount / resources.gold) * 100 : 0);

    async function handleBankAction(action) {
        if (loading || (action === 'deposit' && isOverLimit) || amount <= 0) return;
        
        loading = true;
        message = null;
        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch(`/bank/${action}`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                message = data;
                loading = false;
            }
        } catch (e) {
            message = { success: false, message: "Bank signal lost." };
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8">
    <header class="border-b border-[#2a231e] pb-6">
        <h1 class="text-4xl font-black text-white uppercase tracking-tighter">The Iron Bank</h1>
        <p class="text-gray-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Capital is the lifeblood of conquest.</p>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border bg-red-900/20 border-red-900 text-red-500">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl space-y-8">
            <h2 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">Capital Management</h2>
            
            <div class="space-y-6">
                <div>
                    <label for="bank-amount" class="text-[9px] font-black text-gray-600 uppercase tracking-widest block mb-3">Amount to Process</label>
                    <input id="bank-amount" type="number" bind:value={amount} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white font-mono text-xl focus:outline-none focus:border-[#c5a059]" />
                    
                    {#if amount > 0}
                        <div class="mt-4 p-4 bg-black/40 rounded-xl border border-[#2a231e] space-y-3">
                            <div class="flex justify-between items-center text-[9px] font-black uppercase">
                                <span class="text-gray-500">Deposit Intensity</span>
                                <span class={isOverLimit ? 'text-red-500' : 'text-[#c5a059]'}>{depositRatio.toFixed(1)}%</span>
                            </div>
                            <div class="h-1 w-full bg-black rounded-full overflow-hidden">
                                <div class="h-full {isOverLimit ? 'bg-red-900' : 'bg-[#c5a059]'} transition-all" style="width: {Math.min(100, depositRatio)}%"></div>
                            </div>
                        </div>
                    {/if}
                </div>

                <div class="flex gap-4">
                    <button onclick={() => handleBankAction('deposit')} class="flex-1 btn-primary py-4 disabled:opacity-50" disabled={loading || isOverLimit || amount <= 0}>Secure Assets</button>
                    <button onclick={() => handleBankAction('withdraw')} class="flex-1 bg-[#2a231e] border border-[#3f3028] text-[#c5a059] py-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#3f3028] transition-all" disabled={loading || amount <= 0}>Liquidate</button>
                </div>
            </div>
        </div>

        <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl overflow-hidden shadow-2xl">
            <h2 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px] p-8 pb-4">Audit Log</h2>
            <div class="max-h-[400px] overflow-y-auto">
                <table class="w-full text-left font-mono text-[10px]">
                    <thead class="sticky top-0 bg-[#1a1a1a] text-gray-600 uppercase">
                        <tr>
                            <th class="px-8 py-4">Action</th>
                            <th class="px-8 py-4 text-right">Magnitude</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2a231e]/30">
                        {#each transactions as tx}
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-8 py-4 uppercase font-bold {tx.transaction_type === 'deposit' ? 'text-[#3f6b2f]' : 'text-red-900'}">{tx.transaction_type}</td>
                                <td class="px-8 py-4 text-right text-white">{tx.amount.toLocaleString()} GP</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>