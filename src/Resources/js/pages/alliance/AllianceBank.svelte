<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceSidebar from '../../components/AllianceSidebar.svelte';

    let { payload = {} } = $props();
    let donateAmount = $state(0);
    let withdrawAmount = $state(0);
    let processing = $state(false);
    let message = $state(null);

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    async function handleBankAction(action) {
        processing = true;
        message = null;
        let url = action === 'donate' ? '/api/alliance/bank/donate' : '/api/alliance/bank/withdraw';
        let amount = action === 'donate' ? donateAmount : withdrawAmount;

        try {
            const formData = new FormData();
            formData.append('amount', amount);
            formData.append('_csrf', game.csrf);
            
            const res = await fetch(url, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                message = { type: 'success', text: data.message };
                setTimeout(() => window.location.reload(), 1500);
            } else {
                message = { type: 'error', text: data.error || data.message };
            }
        } catch (e) {
            message = { type: 'error', text: 'Financial Uplink Failed.' };
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advisor={payload?.advisor} />
        <AllianceSidebar active="bank" {payload} />
    </aside>

    <main class="lg:col-span-3 space-y-6">
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md">
            <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Collective Treasury</h1>
            <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Authorized financial exchange and resource pooling.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Contribution Deck -->
            <div class="bg-gray-900/40 border border-white/5 p-6 rounded-lg backdrop-blur-sm space-y-4">
                <header class="border-b border-white/5 pb-2">
                    <h3 class="text-xs font-black text-white uppercase tracking-[2px]">Resource Contribution</h3>
                </header>
                
                <div class="space-y-4 pt-2">
                    <div>
                        <span class="text-[8px] font-black text-gray-600 uppercase block mb-1">Personal Reserves</span>
                        <span class="text-xl font-mono text-white font-bold">{formatNumber(payload.user_credits || 0)} <span class="text-[10px] opacity-40">CP</span></span>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-gray-500 uppercase block">Transfer Amount</label>
                        <input 
                            bind:value={donateAmount}
                            type="number" 
                            class="w-full bg-black/40 border border-white/10 px-4 py-2 text-sm font-mono text-cyan-400 focus:outline-none focus:border-cyan-500/50 transition-all"
                        />
                    </div>

                    <button 
                        onclick={() => handleBankAction('donate')}
                        disabled={processing || donateAmount <= 0}
                        class="w-full bg-cyan-500/10 border border-cyan-500/50 py-3 text-[10px] font-black text-cyan-400 uppercase tracking-widest hover:bg-cyan-500/30 transition-all disabled:opacity-50"
                    >
                        INITIALIZE DEPOSIT
                    </button>
                </div>
            </div>

            <!-- Withdrawal Deck -->
            <div class="bg-gray-900/40 border border-white/5 p-6 rounded-lg backdrop-blur-sm space-y-4 {!payload.can_bank_withdraw ? 'opacity-40 grayscale pointer-events-none' : ''}">
                <header class="border-b border-white/5 pb-2">
                    <h3 class="text-xs font-black text-white uppercase tracking-[2px]">Authorized Withdrawal</h3>
                </header>
                
                <div class="space-y-4 pt-2">
                    <div>
                        <span class="text-[8px] font-black text-gray-600 uppercase block mb-1">Group Wealth</span>
                        <span class="text-xl font-mono text-cyan-400 font-bold">{formatNumber(payload.bank_credits || 0)} <span class="text-[10px] opacity-40">CP</span></span>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-gray-500 uppercase block">Requisition Amount</label>
                        <input 
                            bind:value={withdrawAmount}
                            type="number" 
                            class="w-full bg-black/40 border border-white/10 px-4 py-2 text-sm font-mono text-purple-400 focus:outline-none focus:border-purple-500/50 transition-all"
                        />
                    </div>

                    <button 
                        onclick={() => handleBankAction('withdraw')}
                        disabled={processing || withdrawAmount <= 0}
                        class="w-full bg-purple-500/10 border border-purple-500/50 py-3 text-[10px] font-black text-purple-400 uppercase tracking-widest hover:bg-purple-500/30 transition-all disabled:opacity-50"
                    >
                        INITIALIZE WITHDRAWAL
                    </button>
                </div>
                
                {#if !payload.can_bank_withdraw}
                    <p class="text-[7px] text-red-500 font-black uppercase tracking-widest text-center">Withdrawal Authorization Required</p>
                {/if}
            </div>
        </div>

        {#if message}
            <div class="p-4 rounded border text-center text-[10px] font-black uppercase tracking-widest {message.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/50 text-emerald-400' : 'bg-red-500/10 border-red-500/50 text-red-400'}">
                {message.text}
            </div>
        {/if}

        <!-- Transaction Logs -->
        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <header class="bg-black/40 px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Recent Ledger Entries</h2>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-[10px]">
                    <thead class="bg-black/20 text-gray-600 uppercase">
                        <tr>
                            <th class="px-6 py-3 font-black">Timestamp</th>
                            <th class="px-6 py-3 font-black">Agent</th>
                            <th class="px-6 py-3 font-black">Action</th>
                            <th class="px-6 py-3 text-right font-black">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each payload.logs || [] as log}
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 text-gray-500">{new Date(log.created_at).toLocaleString()}</td>
                                <td class="px-6 py-4">
                                    <span class="text-white font-bold">{log.user?.username}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="uppercase {log.action_type === 'deposit' ? 'text-emerald-500' : 'text-purple-500'}">{log.action_type}</span>
                                    {#if log.comment}
                                        <span class="text-gray-600 text-[8px] block mt-1 italic">{log.comment}</span>
                                    {/if}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-bold {log.action_type === 'deposit' ? 'text-emerald-400' : 'text-purple-400'}">{formatNumber(log.amount)} CP</span>
                                </td>
                            </tr>
                        {:else}
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center opacity-20 italic uppercase tracking-widest">No ledger entries detected.</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
