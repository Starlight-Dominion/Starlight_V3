<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    import AiAdvisor from '../components/AiAdvisor.svelte';
    
    let { spyCount = 0 } = $props();
    
    let selectedTarget = $state(null);
    let loading = $state(false);
    let report = $state(null);
    let message = $state(null);

    const players = $derived(game.props?.players || []);

    async function executeRecon() {
        if (!selectedTarget || loading) return;
        loading = true;
        report = null;
        message = null;
        
        const formData = new FormData();
        formData.append('target_id', selectedTarget.kingdom_id);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/spy/reconnaissance/execute', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                report = data.intel_gained;
                // Persistent Intel Storage
                const saved = JSON.parse(localStorage.getItem('shadow_intel') || '{}');
                saved[selectedTarget.kingdom_id] = {
                    name: data.intel_gained.name,
                    credits: data.intel_gained.credits,
                    citizens: data.intel_gained.citizens,
                    army: data.intel_gained.army,
                    level: data.intel_gained.level,
                    ts: Date.now()
                };
                localStorage.setItem('shadow_intel', JSON.stringify(saved));
            } else {
                message = { success: false, message: data.message || "Encryption detected. Link lost." };
            }
            loading = false;
        } catch (e) {
            message = { success: false, message: "Neural link unstable. Operation aborted." };
            loading = false;
        }
    }

    function selectTarget(player) {
        selectedTarget = player;
        const saved = JSON.parse(localStorage.getItem('shadow_intel') || '{}');
        if (saved[player.kingdom_id]) {
            report = saved[player.kingdom_id];
        } else {
            report = null;
        }
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    
    <!-- MAIN ESPIONAGE INTERFACE -->
    <main class="lg:col-span-3 space-y-6">
        
        <!-- Header Panel -->
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-6xl font-title font-black text-white italic">SHADOW_NET</span>
            </div>
            <div class="relative z-10">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow-purple">Intelligence Operations</h1>
                <p class="text-purple-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-1 italic">Authorized sector infiltration and surveillance interface.</p>
            </div>
        </header>

        {#if message}
            <div in:slide class="p-3 rounded border text-[10px] font-black uppercase text-center {message.success ? 'bg-purple-950/40 border-purple-500/50 text-purple-400' : 'bg-red-950/40 border-red-500/50 text-red-500'}">
                {message.message}
            </div>
        {/if}

        <!-- Intel Report Display -->
        {#if report}
            <div in:slide class="bg-purple-900/20 border border-purple-500/30 rounded-lg p-6 backdrop-blur-md shadow-[0_0_30px_rgba(168,85,247,0.1)]">
                <div class="flex justify-between items-center border-b border-purple-500/20 pb-3 mb-6">
                    <h2 class="text-purple-400 font-title font-black uppercase text-xs tracking-[2px]">Intelligence Brief: {report.name}</h2>
                    <button onclick={() => report = null} class="text-[8px] font-black text-purple-700 hover:text-white uppercase tracking-widest transition-colors">Dismiss Report</button>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="space-y-1">
                        <span class="text-[8px] font-black text-purple-900 uppercase tracking-widest block">Liquid Assets</span>
                        <span class="text-xl font-mono font-bold text-white">{formatNumber(report.credits)} <span class="text-[10px] opacity-40">CP</span></span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[8px] font-black text-purple-900 uppercase tracking-widest block">Unskilled Population</span>
                        <span class="text-xl font-mono font-bold text-white">{formatNumber(report.citizens)}</span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[8px] font-black text-purple-900 uppercase tracking-widest block">Total Manpower</span>
                        <span class="text-xl font-mono font-bold text-white">{formatNumber(report.army.total)}</span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[8px] font-black text-purple-900 uppercase tracking-widest block">Transmission</span>
                        <span class="text-xl font-title font-black text-emerald-500 uppercase tracking-tighter">Secure</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 pt-6 border-t border-purple-500/10">
                    <div class="bg-black/40 border border-purple-500/10 p-3 rounded text-center">
                        <span class="block text-[8px] text-gray-600 uppercase font-black mb-1">Soldiers</span>
                        <span class="text-xs font-mono font-bold text-red-500">{formatNumber(report.army.soldiers)}</span>
                    </div>
                    <div class="bg-black/40 border border-purple-500/10 p-3 rounded text-center">
                        <span class="block text-[8px] text-gray-600 uppercase font-black mb-1">Guards</span>
                        <span class="text-xs font-mono font-bold text-blue-500">{formatNumber(report.army.guards)}</span>
                    </div>
                    <div class="bg-black/40 border border-purple-500/10 p-3 rounded text-center">
                        <span class="block text-[8px] text-gray-600 uppercase font-black mb-1">Spies</span>
                        <span class="text-xs font-mono font-bold text-purple-500">{formatNumber(report.army.spies)}</span>
                    </div>
                    <div class="bg-black/40 border border-purple-500/10 p-3 rounded text-center">
                        <span class="block text-[8px] text-gray-600 uppercase font-black mb-1">Sentries</span>
                        <span class="text-xs font-mono font-bold text-orange-500">{formatNumber(report.army.sentries)}</span>
                    </div>
                </div>
            </div>
        {/if}

        <!-- Surveillance Manifest -->
        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-[11px]">
                    <thead class="bg-black/40 text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-4 font-black tracking-widest">Sector Designation</th>
                            <th class="px-6 py-4 font-black tracking-widest">Commander</th>
                            <th class="px-6 py-4 text-right font-black tracking-widest">Protocol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        {#each players as player}
                            <tr 
                                class="hover:bg-purple-500/5 transition-colors group cursor-pointer {selectedTarget?.kingdom_id === player.kingdom_id ? 'bg-purple-500/10' : ''}"
                                onclick={() => selectTarget(player)}
                            >
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-black/60 border border-purple-500/20 flex items-center justify-center text-purple-400 font-title font-black text-xs">
                                            {player.name?.charAt(0)}
                                        </div>
                                        <div>
                                            <span class="text-white font-title font-black text-sm uppercase group-hover:text-purple-400 transition-colors">{player.name}</span>
                                            <span class="block text-[8px] text-gray-600 uppercase tracking-tighter">ID #{player.kingdom_id}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-[10px] text-gray-400 uppercase font-black">LVL {player.level} &bull; {player.race}</span>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <button 
                                        class="text-[9px] font-black uppercase tracking-widest {selectedTarget?.kingdom_id === player.kingdom_id ? 'text-white' : 'text-purple-600 hover:text-white'} transition-colors"
                                        onclick={(e) => { e.stopPropagation(); selectTarget(player); }}
                                    >
                                        {selectedTarget?.kingdom_id === player.kingdom_id ? 'Target Marked' : 'Mark Target'}
                                    </button>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- SIDEBAR CONTROLS -->
    <aside class="lg:col-span-1 space-y-4">
        
        <AiAdvisor 
            stats={[
                { label: 'Active Spies', value: spyCount, highlight: spyCount > 0 }
            ]}
        />

        <div class="bg-gray-900/60 border border-purple-500/20 rounded-lg p-5 backdrop-blur-md shadow-2xl space-y-6">
            <header class="border-b border-white/5 pb-2">
                <h3 class="font-title text-purple-400 uppercase text-[10px] font-black tracking-[3px]">Infiltration Deck</h3>
            </header>

            {#if selectedTarget}
                <div in:fade class="space-y-6">
                    <div class="bg-purple-950/20 border border-purple-500/30 rounded p-3 space-y-1">
                        <p class="text-[8px] uppercase text-purple-500 font-black tracking-widest">Surveillance Target</p>
                        <p class="text-white font-title font-black text-sm uppercase">{selectedTarget.name}</p>
                        <p class="text-[9px] text-gray-500 uppercase font-black tracking-tighter">LVL {selectedTarget.level} {selectedTarget.race}</p>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Mission Protocol</label>
                        <div class="bg-black/40 border border-purple-500/30 rounded p-3">
                            <span class="text-white font-title font-black text-[10px] uppercase tracking-widest">Reconnaissance</span>
                            <p class="text-[8px] text-purple-700 uppercase mt-1">Status: Operational</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button 
                            onclick={executeRecon}
                            disabled={loading || spyCount <= 0}
                            class="w-full bg-purple-900/30 hover:bg-purple-900/50 border border-purple-500/30 text-purple-400 font-title font-black py-4 rounded-lg uppercase text-[11px] tracking-[3px] transition-all disabled:opacity-20 shadow-[0_0_20px_rgba(168,85,247,0.1)]"
                        >
                            {loading ? 'Infiltrating...' : 'Execute Mission'}
                        </button>
                        
                        <button 
                            onclick={() => selectedTarget = null}
                            class="w-full mt-3 text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors"
                        >
                            Abort Operation
                        </button>
                    </div>
                </div>
            {:else}
                <div in:fade class="py-12 text-center space-y-4">
                    <div class="w-12 h-12 rounded-full border-2 border-gray-800 border-dashed mx-auto flex items-center justify-center">
                        <span class="w-2 h-2 bg-purple-900/40 rounded-full animate-ping"></span>
                    </div>
                    <p class="text-[10px] text-gray-600 uppercase font-black italic tracking-widest leading-relaxed px-4">
                        Select a commander from the surveillance manifest to initiate infiltration.
                    </p>
                </div>
            {/if}

            <div class="pt-4 border-t border-white/5">
                <h4 class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2">Shadow Doctrine</h4>
                <p class="text-[9px] text-gray-600 italic leading-relaxed">
                    "True power lies not in the size of your fleet, but in the depth of your knowledge."
                </p>
            </div>
        </div>

    </aside>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
    .text-shadow-glow-purple {
        text-shadow: 0 0 10px rgba(168, 85, 247, 0.5);
    }
</style>
