<script>
    import { fade, slide } from 'svelte/transition';
    let { game } = $props();

    let logs = $state([]);
    let page = $state(1);
    let lastPage = $state(1);
    let total = $state(0);
    let loading = $state(false);

    let actionFilter = $state('');
    let dominionIdFilter = $state('');

    async function fetchLogs() {
        loading = true;
        try {
            const params = new URLSearchParams({
                page,
                action: actionFilter,
                dominion_id: dominionIdFilter
            });
            const res = await fetch(`/admin/logs/recruitment?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                logs = data.logs.data;
                page = data.logs.page;
                lastPage = data.logs.last_page;
                total = data.logs.total;
            }
        } catch (e) {
            console.error("Failed to fetch recruitment logs");
        } finally {
            loading = false;
        }
    }

    function handleSearch() {
        page = 1;
        fetchLogs();
    }

    function changePage(p) {
        if (p < 1 || p > lastPage) return;
        page = p;
        fetchLogs();
    }

    function exportToJson() {
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(logs, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href",     dataStr);
        downloadAnchorNode.setAttribute("download", `recruitment_telemetry_${new Date().toISOString()}.json`);
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }

    $effect(() => {
        fetchLogs();
    });
</script>

<div class="space-y-6">
    <header class="flex justify-between items-center bg-emerald-950/20 px-8 py-6 border border-white/5 rounded-3xl">
        <div>
            <h2 class="text-[10px] font-black text-emerald-500 uppercase tracking-[4px]">Recruitment Telemetry</h2>
            <p class="text-[8px] text-gray-600 uppercase mt-1">Real-time civilian mobilization data.</p>
        </div>
        <button onclick={exportToJson} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-emerald-600 hover:text-white transition-all">
            Neural Export (JSON)
        </button>
    </header>

    <!-- Filtering Bar -->
    <div class="px-8 py-6 bg-black/40 border border-white/5 rounded-3xl grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-1">
            <span class="text-[8px] font-black text-gray-700 uppercase tracking-widest">Action Pattern</span>
            <input type="text" bind:value={actionFilter} placeholder="e.g. recruitment_click" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs outline-none focus:border-cyan-500" />
        </div>
        <div class="space-y-1">
            <span class="text-[8px] font-black text-gray-700 uppercase tracking-widest">Dominion ID</span>
            <input type="number" bind:value={dominionIdFilter} placeholder="Filter by ID..." class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs outline-none focus:border-cyan-500" />
        </div>
        <div class="flex items-end">
            <button onclick={handleSearch} class="w-full py-4 bg-emerald-900/40 text-emerald-400 border border-emerald-500/20 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all">Filter Telemetry</button>
        </div>
    </div>

    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-mono">
                <thead>
                    <tr class="bg-emerald-950/20 border-b border-white/5 text-[9px] font-black text-gray-500 uppercase tracking-widest">
                        <th class="px-8 py-5 text-emerald-500 w-24">ID</th>
                        <th class="px-8 py-5 w-48">SOVEREIGN SECTOR</th>
                        <th class="px-8 py-5 w-48">PHASE</th>
                        <th class="px-8 py-5">LOG DIRECTIVE</th>
                        <th class="px-8 py-5 w-32">YIELD</th>
                        <th class="px-8 py-5 text-right w-48">TIMESTAMP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    {#each logs as log}
                        <tr class="group hover:bg-white/[0.02] transition-colors">
                            <td class="px-8 py-6 text-emerald-900 text-xs font-black">#{log.id}</td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-white text-xs font-black uppercase tracking-tighter">{log.dominion_name}</span>
                                    <span class="text-[8px] text-gray-600 font-bold">SEC_{log.dominion_id}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-emerald-900/20 text-emerald-500 border border-emerald-500/30 rounded-full text-[8px] font-black uppercase tracking-widest">{log.action.replace('recruitment_', '')}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs text-gray-300 font-bold">{log.description}</span>
                            </td>
                            <td class="px-8 py-6">
                                {#if log.amount !== null}
                                    <span class="text-xs text-emerald-400 font-black">+{log.amount.toLocaleString()}</span>
                                {:else}
                                    <span class="text-[10px] text-gray-700 font-black">--</span>
                                {/if}
                            </td>
                            <td class="px-8 py-6 text-right text-[10px] text-gray-600 font-black uppercase tracking-widest">{log.created_at}</td>
                        </tr>
                    {:else}
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center">
                                <div class="space-y-2 opacity-20">
                                    <span class="block text-4xl">∅</span>
                                    <span class="block text-[10px] font-black uppercase tracking-[4px]">No Recruitment Telemetry Found</span>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <footer class="px-8 py-6 bg-emerald-950/20 border-t border-white/5 flex justify-between items-center">
            <div class="text-[9px] font-black text-gray-600 uppercase tracking-widest">
                Data Points: {total.toLocaleString()} &bull; Page {page} / {lastPage}
            </div>
            <div class="flex gap-4">
                <button onclick={() => changePage(page - 1)} disabled={page <= 1} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-emerald-600 hover:text-white transition-all disabled:opacity-10">Previous</button>
                <button onclick={() => changePage(page + 1)} disabled={page >= lastPage} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-emerald-600 hover:text-white transition-all disabled:opacity-10">Next</button>
            </div>
        </footer>
    </div>
</div>
