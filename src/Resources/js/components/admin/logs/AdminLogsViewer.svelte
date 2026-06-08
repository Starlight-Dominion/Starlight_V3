<script>
    import { fade, slide } from 'svelte/transition';
    let { game } = $props();

    let logs = $state([]);
    let page = $state(1);
    let lastPage = $state(1);
    let total = $state(0);
    let loading = $state(false);

    let actionFilter = $state('');
    let adminIdFilter = $state('');

    async function fetchLogs() {
        loading = true;
        try {
            const params = new URLSearchParams({
                page,
                action: actionFilter,
                admin_id: adminIdFilter
            });
            const res = await fetch(`/admin/logs/administrative?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                logs = data.logs.data;
                page = data.logs.page;
                lastPage = data.logs.last_page;
                total = data.logs.total;
            }
        } catch (e) {
            console.error("Failed to fetch admin logs");
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
        downloadAnchorNode.setAttribute("download", `admin_logs_${new Date().toISOString()}.json`);
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }

    $effect(() => {
        fetchLogs();
    });
</script>

<div class="space-y-6">
    <header class="flex justify-between items-center bg-cyan-950/20 px-8 py-6 border border-white/5 rounded-3xl">
        <div>
            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Administrative Directives</h2>
            <p class="text-[8px] text-gray-600 uppercase mt-1">Telemetry from internal command operations.</p>
        </div>
        <button onclick={exportToJson} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-cyan-600 hover:text-white transition-all">
            Neural Export (JSON)
        </button>
    </header>

    <!-- Filtering Bar -->
    <div class="px-8 py-6 bg-black/40 border border-white/5 rounded-3xl grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-1">
            <span class="text-[8px] font-black text-gray-700 uppercase tracking-widest">Directive Pattern</span>
            <input type="text" bind:value={actionFilter} placeholder="e.g. UPDATE_SETTING" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs outline-none focus:border-cyan-500" />
        </div>
        <div class="space-y-1">
            <span class="text-[8px] font-black text-gray-700 uppercase tracking-widest">Administrator ID</span>
            <input type="number" bind:value={adminIdFilter} placeholder="Filter by ID..." class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs outline-none focus:border-cyan-500" />
        </div>
        <div class="flex items-end">
            <button onclick={handleSearch} class="w-full py-4 bg-cyan-900/40 text-cyan-400 border border-cyan-500/20 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-600 hover:text-white transition-all">Search Directives</button>
        </div>
    </div>

    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-mono">
                <thead>
                    <tr class="bg-cyan-950/20 border-b border-white/5 text-[9px] font-black text-gray-500 uppercase tracking-widest">
                        <th class="px-8 py-5 text-red-500 w-24">ID</th>
                        <th class="px-8 py-5 w-40">ADMINISTRATOR</th>
                        <th class="px-8 py-5 w-48">OPERATION</th>
                        <th class="px-8 py-5">DIRECTive telemetry</th>
                        <th class="px-8 py-5 text-right w-48">TIMESTAMP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    {#each logs as log}
                        <tr class="group hover:bg-white/[0.02] transition-colors">
                            <td class="px-8 py-6 text-red-900 text-xs font-black">#{log.id}</td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-white text-xs font-black uppercase tracking-tighter">{log.admin_username}</span>
                                    <span class="text-[8px] text-gray-600 font-bold">UID: {log.admin_id}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-cyan-900/20 text-cyan-500 border border-cyan-500/30 rounded-full text-[8px] font-black uppercase tracking-widest">{log.action}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs text-gray-300 font-bold">{log.description}</span>
                                {#if log.metadata}
                                    <div class="mt-2 p-4 bg-black/60 border border-white/5 rounded-xl">
                                        <pre class="text-[8px] text-cyan-900 font-bold uppercase overflow-hidden">{JSON.stringify(log.metadata, null, 2)}</pre>
                                    </div>
                                {/if}
                            </td>
                            <td class="px-8 py-6 text-right text-[10px] text-gray-600 font-black uppercase tracking-widest">{log.created_at}</td>
                        </tr>
                    {:else}
                        <tr>
                            <td colspan="5" class="px-8 py-24 text-center">
                                <div class="space-y-2 opacity-20">
                                    <span class="block text-4xl">∅</span>
                                    <span class="block text-[10px] font-black uppercase tracking-[4px]">No Administrative Directives Found</span>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <footer class="px-8 py-6 bg-cyan-950/20 border-t border-white/5 flex justify-between items-center">
            <div class="text-[9px] font-black text-gray-600 uppercase tracking-widest">
                Transmissions: {total.toLocaleString()} &bull; Page {page} / {lastPage}
            </div>
            <div class="flex gap-4">
                <button onclick={() => changePage(page - 1)} disabled={page <= 1} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-cyan-600 hover:text-white transition-all disabled:opacity-10">Previous</button>
                <button onclick={() => changePage(page + 1)} disabled={page >= lastPage} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-cyan-600 hover:text-white transition-all disabled:opacity-10">Next</button>
            </div>
        </footer>
    </div>
</div>
