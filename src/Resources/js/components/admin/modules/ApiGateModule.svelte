<script>
    import { fade, slide } from 'svelte/transition';
    let { 
        apiKeys = [], 
        apiApps = [], 
        apiLogs = [], 
        apiTab = $bindable('keys'),
        savingId = null,
        onUpdateKey,
        onDeleteKey,
        onProcessApp
    } = $props();
</script>

<div in:fade class="space-y-8">
    <div class="flex gap-4 border-b border-white/10 pb-4">
        <button onclick={() => apiTab = 'keys'} class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded {apiTab === 'keys' ? 'bg-cyan-500/20 text-cyan-400' : 'text-gray-500 hover:text-white'}">Key Matrix</button>
        <button onclick={() => apiTab = 'apps'} class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded {apiTab === 'apps' ? 'bg-cyan-500/20 text-cyan-400' : 'text-gray-500 hover:text-white'}">Pending Requests {#if apiApps.length > 0}<span class="text-red-500 ml-1">({apiApps.length})</span>{/if}</button>
        <button onclick={() => apiTab = 'logs'} class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded {apiTab === 'logs' ? 'bg-cyan-500/20 text-cyan-400' : 'text-gray-500 hover:text-white'}">Audit Trail</button>
    </div>

    {#if apiTab === 'keys'}
        <div in:slide class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse font-mono">
                <thead>
                    <tr class="bg-cyan-950/20 border-b border-white/5 text-[9px] font-black text-gray-500 uppercase tracking-widest">
                        <th class="px-8 py-5">Commander</th>
                        <th class="px-8 py-5">Key Identity (Partial)</th>
                        <th class="px-8 py-5">Rate Limit (RPM)</th>
                        <th class="px-8 py-5">Status</th>
                        <th class="px-8 py-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    {#each apiKeys as key}
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-8 py-6 font-title font-black uppercase text-white">{key.user?.username || 'Unknown'}</td>
                            <td class="px-8 py-6 text-[10px] text-cyan-500 break-all">{key.api_token.substring(0, 8)}...{key.api_token.slice(-4)}</td>
                            <td class="px-8 py-6"><input type="number" bind:value={key.rate_limit_per_minute} class="bg-black/60 border border-white/10 rounded px-3 py-2 text-cyan-400 font-mono w-24 focus:border-cyan-500 outline-none" /></td>
                            <td class="px-8 py-6">
                                <select bind:value={key.is_active} class="bg-black/60 border border-white/10 text-[10px] text-gray-500 rounded px-3 py-2 focus:outline-none uppercase font-black">
                                    <option value={true}>ACTIVE</option>
                                    <option value={false}>SUSPENDED</option>
                                </select>
                            </td>
                            <td class="px-8 py-6 text-right space-x-4">
                                <button onclick={() => onUpdateKey(key)} class="text-cyan-500 font-black uppercase text-[10px] tracking-widest opacity-30 group-hover:opacity-100 hover:text-cyan-400 transition-all">{savingId === `api-${key.id}` ? '...' : 'UPDATE'}</button>
                                <button onclick={() => onDeleteKey(key.id)} class="text-red-900 hover:text-red-500 opacity-20 group-hover:opacity-100 transition-all text-xs" title="Revoke">✕</button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {:else if apiTab === 'apps'}
        <div in:slide class="space-y-6">
            {#if apiApps.length === 0}
                <div class="p-12 text-center text-gray-600 font-black uppercase tracking-widest text-[10px]">No pending API applications.</div>
            {/if}
            {#each apiApps as app}
                <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl space-y-6 relative overflow-hidden group">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-title font-black text-white uppercase tracking-tight">{app.project_name}</h3>
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[2px] mt-1">Applicant: {app.username}</p>
                        </div>
                        <span class="text-[9px] text-gray-600 font-mono">{new Date(app.created_at).toLocaleString()}</span>
                    </div>
                    <div class="bg-black/40 border border-white/5 p-6 rounded-xl">
                        <span class="block text-[8px] font-black text-cyan-800 uppercase tracking-widest mb-2">Justification</span>
                        <p class="text-gray-400 text-sm leading-relaxed italic">{app.justification}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                        <div class="space-y-2">
                            <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">High Command Notes</span>
                            <input type="text" bind:value={app.admin_notes} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-xs font-mono text-cyan-400 focus:border-cyan-500" placeholder="Optional feedback..." />
                        </div>
                        <div class="flex gap-4 justify-end">
                            <div class="space-y-2 w-32">
                                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Assign RPM Limit</span>
                                <input type="number" bind:value={app._new_limit} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-xs font-mono text-cyan-400 focus:border-cyan-500" placeholder="60" />
                            </div>
                            <button onclick={() => onProcessApp(app, 'approve')} class="bg-cyan-900/20 border border-cyan-500/50 text-cyan-400 px-6 py-3 rounded-lg font-title font-black text-[10px] uppercase tracking-widest hover:bg-cyan-600 hover:text-white transition-all disabled:opacity-50" disabled={savingId === `app-${app.id}`}>
                                APPROVE
                            </button>
                            <button onclick={() => onProcessApp(app, 'reject')} class="bg-red-900/10 border border-red-900/30 text-red-500 px-6 py-3 rounded-lg font-title font-black text-[10px] uppercase tracking-widest hover:bg-red-900 hover:text-white transition-all disabled:opacity-50" disabled={savingId === `app-${app.id}`}>
                                REJECT
                            </button>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    {:else if apiTab === 'logs'}
        <div in:slide class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-mono">
                    <thead>
                        <tr class="bg-cyan-950/20 border-b border-white/5 text-[9px] font-black text-gray-500 uppercase tracking-widest">
                            <th class="px-6 py-4">Time</th>
                            <th class="px-6 py-4">Commander</th>
                            <th class="px-6 py-4">Method / Endpoint</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">IP / Agent</th>
                            <th class="px-6 py-4 text-right">ms</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each apiLogs as log}
                            <tr class="hover:bg-white/[0.02] transition-colors group {log.status_code >= 400 ? 'bg-red-900/5' : ''}">
                                <td class="px-6 py-4 text-[9px] text-gray-600 whitespace-nowrap">{new Date(log.created_at).toLocaleTimeString()}</td>
                                <td class="px-6 py-4 font-title font-black uppercase {log.api_key ? 'text-white' : 'text-gray-600'}">{log.api_key?.user?.username || 'ANONYMOUS'}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[9px] font-black {log.method === 'GET' ? 'text-cyan-600' : 'text-purple-600'} mr-2">{log.method}</span>
                                    <span class="text-[10px] text-cyan-400">{log.endpoint}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-[9px] font-black {log.status_code === 200 ? 'bg-green-900/20 text-green-500' : (log.status_code === 429 ? 'bg-orange-900/20 text-orange-500' : 'bg-red-900/20 text-red-500')}">
                                        {log.status_code}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-[9px] text-gray-400">{log.ip_address}</div>
                                    <div class="text-[8px] text-gray-600 truncate max-w-[150px]" title={log.user_agent}>{log.user_agent || 'Unknown Agent'}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-[10px] text-gray-500">{log.response_time_ms}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}
</div>
