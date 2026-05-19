<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    let { stats = {} } = $props();

    let activeModule = $state('overview');
    let searchQuery = $state('');
    let searchResults = $state([]);
    let units = $state([]);
    let structures = $state([]);
    let armoryItems = $state([]);
    let unitTypes = $state([]);
    let categories = $state([]);
    let battleLogs = $state([]);
    let activeStructureId = $state(null);
    let loading = $state(false);
    let savingId = $state(null);

    const modules = [
        { id: 'overview', name: 'Command Overview', icon: '◈' },
        { id: 'armory', name: 'Armory Forge', icon: '⚔' },
        { id: 'units', name: 'War Room (Units)', icon: '👥' },
        { id: 'structures', name: 'Structural Engineering', icon: '🏛' },
        { id: 'players', name: 'Sovereign Oversight', icon: '👁' },
        { id: 'logs', name: 'Battle Records', icon: '📜' }
    ];

    async function fetchUnits() {
        loading = true;
        try {
            const res = await fetch('/admin/units');
            const data = await res.json();
            units = data.units;
        } catch (e) { console.error("Failed to fetch units"); } finally { loading = false; }
    }

    async function fetchStructures() {
        loading = true;
        try {
            const res = await fetch('/admin/structures');
            const data = await res.json();
            structures = data.structures;
            if (!activeStructureId && structures.length > 0) activeStructureId = structures[0].details.id;
        } catch (e) { console.error("Failed to fetch structures"); } finally { loading = false; }
    }

    async function fetchArmoryItems() {
        loading = true;
        try {
            const res = await fetch('/admin/armory-items');
            const data = await res.json();
            armoryItems = data.items;
            unitTypes = data.unit_types;
            categories = data.categories;
        } catch (e) { console.error("Failed to fetch armory"); } finally { loading = false; }
    }

    async function fetchBattleLogs() {
        loading = true;
        try {
            const res = await fetch('/admin/battle-logs');
            const data = await res.json();
            battleLogs = data.logs;
        } catch (e) { console.error("Failed to fetch logs"); } finally { loading = false; }
    }

    $effect(() => {
        if (activeModule === 'units') fetchUnits();
        if (activeModule === 'structures') fetchStructures();
        if (activeModule === 'armory') fetchArmoryItems();
        if (activeModule === 'logs') fetchBattleLogs();
    });

    async function handleSearch() {
        if (!searchQuery) return;
        loading = true;
        try {
            const res = await fetch(`/admin/search?q=${searchQuery}`);
            const data = await res.json();
            searchResults = data.results;
        } catch (e) { console.error("Search failed"); } finally { loading = false; }
    }

    async function saveKingdom(kingdom) {
        savingId = kingdom.id;
        const formData = new FormData();
        formData.append('id', kingdom.id);
        formData.append('gold', kingdom.gold);
        formData.append('xp', kingdom.xp);
        formData.append('turns', kingdom.turns);
        formData.append('citizens', kingdom.citizens);
        formData.append('_csrf', game.csrf);
        try {
            await fetch('/admin/update-kingdom', { method: 'POST', body: formData });
        } catch (e) { console.error("Update failed"); } finally { savingId = null; }
    }

    async function saveUnit(unit) {
        savingId = unit.id;
        const formData = new FormData();
        formData.append('id', unit.id);
        formData.append('name', unit.name);
        formData.append('slug', unit.slug);
        formData.append('cost_gold', unit.cost_gold);
        formData.append('cost_citizens', unit.cost_citizens);
        formData.append('cost_turns', unit.cost_turns);
        formData.append('power_offense', unit.power_offense);
        formData.append('power_defense', unit.power_defense);
        formData.append('foundation_level_req', unit.foundation_level_req);
        formData.append('stable_level_req', unit.stable_level_req);
        formData.append('requirement_slug', unit.requirement_slug || '');
        formData.append('description', unit.description);
        formData.append('_csrf', game.csrf);
        try {
            await fetch('/admin/update-unit', { method: 'POST', body: formData });
        } catch (e) { console.error("Unit update failed"); } finally { savingId = null; }
    }

    async function addUnit() {
        const formData = new FormData();
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/add-unit', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchUnits();
        } catch (e) { console.error("Failed to add unit"); }
    }

    async function deleteUnit(id) {
        if (!confirm("Confirm decommissioning of this unit class?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/delete-unit', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchUnits();
        } catch (e) { console.error("Failed to delete unit"); }
    }

    async function saveStructureDetails(s) {
        savingId = s.id;
        const formData = new FormData();
        formData.append('id', s.id);
        formData.append('name', s.name);
        formData.append('upgrade_slots', s.upgrade_slots);
        formData.append('max_level', s.max_level);
        formData.append('description', s.description);
        formData.append('_csrf', game.csrf);
        try { await fetch('/admin/update-structure', { method: 'POST', body: formData }); } catch (e) { console.error("Structure update failed"); } finally { savingId = null; }
    }

    async function addStructure() {
        const formData = new FormData();
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/add-structure', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchStructures();
        } catch (e) { console.error("Failed to add structure"); }
    }

    async function deleteStructure(id) {
        if (!confirm("Permanently demolish this structure and all its ranks?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/delete-structure', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                activeStructureId = null;
                fetchStructures();
            }
        } catch (e) { console.error("Failed to delete structure"); }
    }

    async function saveStructureLevel(sId, row) {
        savingId = `level-${sId}-${row.level}`;
        const formData = new FormData();
        formData.append('structure_id', sId);
        formData.append('level', row.level);
        Object.entries(row).forEach(([k, v]) => {
            if (k !== 'structure_id' && k !== 'level') formData.append(k, v || 0);
        });
        formData.append('_csrf', game.csrf);
        try { await fetch('/admin/update-structure-level', { method: 'POST', body: formData }); } catch (e) { console.error("Level update failed"); } finally { savingId = null; }
    }

    async function addStructureLevel(sId) {
        const s = structures.find(st => st.details.id === sId);
        const nextLvl = (s.levels.length > 0) ? (Math.max(...s.levels.map(l => l.level)) + 1) : 1;
        
        const formData = new FormData();
        formData.append('structure_id', sId);
        formData.append('level', nextLvl);
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/add-structure-level', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchStructures();
        } catch (e) { console.error("Failed to add level"); }
    }

    async function saveArmoryItem(item) {
        savingId = item.id;
        const formData = new FormData();
        formData.append('id', item.id);
        formData.append('name', item.name);
        formData.append('slug', item.slug);
        formData.append('attack_bonus', item.attack_bonus);
        formData.append('defense_bonus', item.defense_bonus);
        formData.append('cost', item.cost);
        formData.append('requirement_slug', item.requirement_slug || '');
        formData.append('armory_level_req', item.armory_level_req);
        formData.append('_csrf', game.csrf);
        try { await fetch('/admin/update-armory-item', { method: 'POST', body: formData }); } catch (e) { console.error("Armory update failed"); } finally { savingId = null; }
    }

    async function addArmoryItem(unitType, categoryId) {
        const formData = new FormData();
        formData.append('unit_type', unitType);
        formData.append('category_id', categoryId);
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/add-armory-item', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchArmoryItems();
        } catch (e) { console.error("Failed to add item"); }
    }

    async function deleteArmoryItem(id) {
        if (!confirm("Confirm decommission of this asset?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/delete-armory-item', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchArmoryItems();
        } catch (e) { console.error("Failed to delete item"); }
    }

    const currentStructure = $derived(structures.find(s => s.details.id === activeStructureId) || null);
</script>

<div in:fade class="space-y-8 pb-24">
    <header class="border-b border-red-900/50 pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 bg-red-900/5 -mx-6 px-6 pt-6">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Command Center</h1>
            <p class="text-red-500 font-bold uppercase tracking-[3px] text-[10px] mt-2 italic">Global authority & administration hub.</p>
        </div>

        <div class="flex gap-4">
            <div class="bg-black border border-red-900/30 p-3 rounded-lg text-center min-w-[120px]">
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Server Time</span>
                <span class="text-sm font-black text-red-500 font-mono">{stats.server_time || '00:00:00'}</span>
            </div>
            <div class="bg-black border border-red-900/30 p-3 rounded-lg text-center min-w-[100px]">
                <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Total Users</span>
                <span class="text-xl font-black text-white">{stats.total_users || 0}</span>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1 space-y-2">
            {#each modules as mod}
                <button 
                    onclick={() => activeModule = mod.id}
                    class="w-full text-left px-6 py-4 rounded-xl border transition-all flex items-center gap-4 {activeModule === mod.id ? 'bg-red-900/20 border-red-900 text-white font-black' : 'bg-[#0f0f0f] border-[#2a231e] text-gray-500 hover:border-red-900/30 hover:text-gray-300'}"
                >
                    <span class="text-lg opacity-50">{mod.icon}</span>
                    <span class="text-[10px] uppercase tracking-widest">{mod.name}</span>
                </button>
            {/each}
        </aside>

        <main class="lg:col-span-3 space-y-8">
            {#if activeModule === 'overview'}
                <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl space-y-4">
                        <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">System Status</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center py-2 border-b border-[#2a231e]"><span class="text-[10px] uppercase text-gray-500">Database</span><span class="text-[10px] font-black text-[#3f6b2f] uppercase">Connected</span></div>
                            <div class="flex justify-between items-center py-2 border-b border-[#2a231e]"><span class="text-[10px] uppercase text-gray-500">Heartbeat</span><span class="text-[10px] font-black text-[#3f6b2f] uppercase">Synchronized</span></div>
                        </div>
                    </div>
                    <div class="bg-red-900/5 border border-red-900/20 p-8 rounded-3xl space-y-4">
                        <h3 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">Global Warning</h3>
                        <p class="text-gray-400 text-[10px] leading-relaxed italic">Changes made within this terminal affect the live production database immediately.</p>
                    </div>
                </div>

            {:else if activeModule === 'players'}
                <div in:fade class="space-y-6">
                    <div class="flex gap-4">
                        <input type="text" bind:value={searchQuery} placeholder="Search kingdoms..." class="flex-grow bg-black border border-[#2a231e] rounded-xl px-6 py-4 text-white focus:border-red-900 focus:outline-none" onkeydown={(e) => e.key === 'Enter' && handleSearch()} />
                        <button onclick={handleSearch} class="bg-red-900 text-white px-8 rounded-xl font-black text-[10px] uppercase tracking-widest">Search</button>
                    </div>
                    <div class="space-y-4">
                        {#each searchResults as kingdom}
                            <div class="bg-[#0f0f0f] border border-[#2a231e] p-6 rounded-2xl flex flex-col gap-6">
                                <div class="flex justify-between items-center">
                                    <div><h3 class="text-xl font-black text-white uppercase tracking-tight">{kingdom.kingdom_name}</h3><p class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">Sovereign: {kingdom.user.username}</p></div>
                                    <button onclick={() => saveKingdom(kingdom)} class="bg-[#3f6b2f] text-white px-4 py-2 rounded text-[8px] font-black uppercase tracking-widest hover:bg-white hover:text-[#3f6b2f] transition-all disabled:opacity-50" disabled={savingId === kingdom.id}>
                                        {savingId === kingdom.id ? 'Saving...' : 'Save Sovereignty'}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-[#2a231e]">
                                    {#each ['gold', 'xp', 'turns', 'citizens'] as field}
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-600 uppercase">{field}</span>
                                            <input type="number" bind:value={kingdom[field]} class="w-full bg-black border border-[#2a231e] rounded px-2 py-1 text-xs font-mono text-white" />
                                        </div>
                                    {/each}
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>

            {:else if activeModule === 'armory'}
                <div in:fade class="space-y-12">
                    {#each unitTypes as uType}
                        <section class="space-y-6">
                            <div class="flex justify-between items-end border-b border-[#2a231e] pb-4">
                                <div>
                                    <h3 class="text-xl font-black text-white uppercase tracking-tighter">{uType.name} Loadout</h3>
                                    <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">{uType.title}</p>
                                </div>
                            </div>

                            {#each categories.filter(c => c.unit_type_id === uType.id) as cat}
                                <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl overflow-hidden">
                                    <div class="bg-black/50 px-6 py-4 flex justify-between items-center border-b border-[#2a231e]">
                                        <h4 class="text-[10px] font-black text-[#c5a059] uppercase tracking-[3px]">{cat.name}</h4>
                                        <button onclick={() => addArmoryItem(uType.slug, cat.id)} class="text-[8px] font-black text-gray-500 uppercase hover:text-white transition-colors">+ Add Item</button>
                                    </div>
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-black/20">
                                                <th class="px-6 py-3 text-[8px] font-black text-gray-600 uppercase tracking-widest">Item Name / Slug</th>
                                                <th class="px-6 py-3 text-[8px] font-black text-gray-600 uppercase tracking-widest">Atk / Def</th>
                                                <th class="px-6 py-3 text-[8px] font-black text-gray-600 uppercase tracking-widest">Cost (GP)</th>
                                                <th class="px-6 py-3 text-[8px] font-black text-gray-600 uppercase tracking-widest">Prereq / Lvl</th>
                                                <th class="px-6 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {#each armoryItems.filter(i => i.category_id === cat.id) as item}
                                                <tr class="border-b border-[#2a231e]/50 group hover:bg-white/[0.01]">
                                                    <td class="px-6 py-4">
                                                        <input type="text" bind:value={item.name} class="bg-transparent border-none p-0 text-white text-xs font-bold w-full focus:ring-0" />
                                                        <input type="text" bind:value={item.slug} class="bg-transparent border-none p-0 text-[8px] text-gray-600 uppercase font-mono w-full focus:ring-0" />
                                                    </td>
                                                    <td class="px-6 py-4 flex gap-2">
                                                        <input type="number" bind:value={item.attack_bonus} class="bg-black/40 border border-[#2a231e] px-2 py-1 text-red-500 text-[10px] font-mono w-14 rounded" title="Attack" />
                                                        <input type="number" bind:value={item.defense_bonus} class="bg-black/40 border border-[#2a231e] px-2 py-1 text-[#3f6b2f] text-[10px] font-mono w-14 rounded" title="Defense" />
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <input type="number" bind:value={item.cost} class="bg-transparent border-none p-0 text-[#c5a059] text-xs font-mono w-24 focus:ring-0" />
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <select bind:value={item.requirement_slug} class="bg-black border border-[#2a231e] text-[9px] text-gray-500 rounded px-1 py-1 focus:outline-none">
                                                            <option value="">None</option>
                                                            {#each armoryItems.filter(i => i.id !== item.id && i.unit_type === item.unit_type) as p}
                                                                <option value={p.slug}>{p.name}</option>
                                                            {/each}
                                                        </select>
                                                        <input type="number" bind:value={item.armory_level_req} class="bg-black/40 border border-[#2a231e] px-2 py-1 text-white text-[10px] font-mono w-12 rounded mt-1 block" title="Armory Level Req" />
                                                    </td>
                                                    <td class="px-6 py-4 text-right space-x-2">
                                                        <button onclick={() => saveArmoryItem(item)} class="text-[#3f6b2f] font-black uppercase text-[10px] opacity-0 group-hover:opacity-100 transition-all">{savingId === item.id ? '...' : 'SAVE'}</button>
                                                        <button onclick={() => deleteArmoryItem(item.id)} class="text-red-900 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all text-[10px]">✕</button>
                                                    </td>
                                                </tr>
                                            {/each}
                                        </tbody>
                                    </table>
                                </div>
                            {/each}
                        </section>
                    {/each}
                </div>

            {:else if activeModule === 'units'}
                <div in:fade class="space-y-12">
                    <div class="flex justify-between items-center">
                        <h3 class="text-2xl font-black text-white uppercase tracking-tighter">Combat Doctrine</h3>
                        <button onclick={addUnit} class="btn-primary py-2 px-6 text-[9px]">Enlist New Class</button>
                    </div>

                    <div class="grid grid-cols-1 gap-8">
                        {#each units as unit}
                            <div class="bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl space-y-8 relative group">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-6">
                                        <div class="w-16 h-16 bg-red-900/10 border border-red-900/30 rounded-2xl flex items-center justify-center text-red-500 text-2xl font-black">
                                            {unit.slug.charAt(0).toUpperCase()}
                                        </div>
                                        <div class="flex-grow">
                                            <input type="text" bind:value={unit.name} class="bg-transparent border-none text-2xl font-black text-white uppercase focus:ring-0 p-0 w-full" />
                                            <div class="flex items-center gap-4 mt-1">
                                                <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">Internal ID: {unit.slug}</p>
                                                <input type="text" bind:value={unit.slug} class="bg-black/40 border border-[#2a231e] rounded px-2 py-0.5 text-[8px] font-mono text-gray-400 w-32" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick={() => saveUnit(unit)} class="bg-[#3f6b2f] text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-white hover:text-[#3f6b2f] transition-all disabled:opacity-50" disabled={savingId === unit.id}>
                                            {savingId === unit.id ? 'Recording Stats...' : 'Save Unit Class'}
                                        </button>
                                        <button onclick={() => deleteUnit(unit.id)} class="text-red-900 hover:text-red-500 p-3">✕</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 pt-8 border-t border-[#2a231e]">
                                    <div class="space-y-4">
                                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Recruitment Costs</h4>
                                        <div class="grid grid-cols-3 gap-4">
                                            {#each ['cost_gold', 'cost_citizens', 'cost_turns'] as field}
                                                <div class="space-y-1">
                                                    <span class="block text-[8px] font-black text-gray-600 uppercase">{field.split('_')[1]}</span>
                                                    <input type="number" bind:value={unit[field]} class="w-full bg-black border border-[#2a231e] rounded px-3 py-2 text-xs font-mono text-white" />
                                                </div>
                                            {/each}
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Combat Power</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <span class="block text-[8px] font-black text-red-900 uppercase">Offense</span>
                                                <input type="number" bind:value={unit.power_offense} class="w-full bg-black border border-red-900/20 rounded px-3 py-2 text-xs font-mono text-red-500" />
                                            </div>
                                            <div class="space-y-1">
                                                <span class="block text-[8px] font-black text-[#3f6b2f] uppercase">Defense</span>
                                                <input type="number" bind:value={unit.power_defense} class="w-full bg-black border border-[#3f6b2f]/20 rounded px-3 py-2 text-xs font-mono text-[#3f6b2f]" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-[4px]">Advancement</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <span class="block text-[8px] font-black text-[#c5a059] uppercase">Found.</span>
                                                <input type="number" bind:value={unit.foundation_level_req} class="w-full bg-black border border-[#2a231e] rounded px-3 py-2 text-xs font-mono text-white" />
                                            </div>
                                            <div class="space-y-1">
                                                <span class="block text-[8px] font-black text-[#c5a059] uppercase">Stable</span>
                                                <input type="number" bind:value={unit.stable_level_req} class="w-full bg-black border border-[#2a231e] rounded px-3 py-2 text-xs font-mono text-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <span class="block text-[8px] font-black text-gray-600 uppercase">Field Intelligence (Description)</span>
                                    <textarea bind:value={unit.description} class="w-full bg-black border border-[#2a231e] rounded-xl p-4 text-[10px] text-gray-400 focus:border-red-900 focus:outline-none min-h-[80px]"></textarea>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>

            {:else if activeModule === 'structures'}
                <div in:fade class="space-y-8">
                    <div class="flex justify-between items-center">
                        <div class="flex gap-2">
                            {#each structures as s}
                                <button onclick={() => activeStructureId = s.details.id} class="px-6 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest border {activeStructureId === s.details.id ? 'bg-red-900 border-red-900 text-white' : 'bg-black border-[#2a231e] text-gray-500 hover:border-red-900/30'}">{s.details.name}</button>
                            {/each}
                        </div>
                        <button onclick={addStructure} class="text-[#c5a059] font-black text-[9px] uppercase tracking-widest hover:text-white transition-colors">+ Add New Building Type</button>
                    </div>

                    {#if currentStructure}
                        <div in:slide class="bg-[#0f0f0f] border border-[#2a231e] p-8 rounded-3xl space-y-8 relative group">
                            <div class="flex justify-between items-start">
                                <h3 class="text-xs font-black text-gray-500 uppercase tracking-[4px]">Structural Configuration</h3>
                                <div class="flex gap-3">
                                    <button onclick={() => saveStructureDetails(currentStructure.details)} class="bg-[#3f6b2f] text-white px-6 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-white hover:text-[#3f6b2f] transition-all disabled:opacity-50" disabled={savingId === currentStructure.details.id}>
                                        {savingId === currentStructure.details.id ? 'Saving Plan...' : 'Save General Config'}
                                    </button>
                                    <button onclick={() => deleteStructure(currentStructure.details.id)} class="text-red-900 hover:text-red-500 text-[9px] font-black uppercase">Demolish ✕</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <div class="space-y-1">
                                        <span class="block text-[8px] font-black text-gray-600 uppercase">Building Name</span>
                                        <input type="text" bind:value={currentStructure.details.name} class="w-full bg-black border border-[#2a231e] rounded px-3 py-2 text-xs font-bold text-white" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-600 uppercase">Upgrade Slots</span>
                                            <input type="number" bind:value={currentStructure.details.upgrade_slots} class="w-full bg-black border border-[#2a231e] rounded px-3 py-2 text-xs font-mono text-white" />
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[8px] font-black text-gray-600 uppercase">Max Rank</span>
                                            <input type="number" bind:value={currentStructure.details.max_level} class="w-full bg-black border border-[#2a231e] rounded px-3 py-2 text-xs font-mono text-white" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <span class="block text-[8px] font-black text-gray-600 uppercase">Directives (Description)</span>
                                    <textarea bind:value={currentStructure.details.description} class="w-full bg-black border border-[#2a231e] rounded-xl p-4 text-[10px] text-gray-400 focus:border-red-900 focus:outline-none min-h-[80px]"></textarea>
                                </div>
                            </div>

                            <div class="space-y-4 pt-8 border-t border-[#2a231e]">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-[10px] font-black text-[#c5a059] uppercase tracking-[4px]">Rank Evolution & Buffs</h4>
                                    <button onclick={() => addStructureLevel(currentStructure.details.id)} class="text-[8px] font-black text-gray-500 hover:text-white uppercase tracking-widest">+ New Rank</button>
                                </div>
                                <div class="bg-black/50 rounded-2xl overflow-hidden overflow-x-auto">
                                    <table class="w-full text-left border-collapse min-w-[1000px]">
                                        <thead>
                                            <tr class="bg-black/80">
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">Level</th>
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">Label</th>
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">Cost (GP)</th>
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">HP</th>
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">ATK / DEF</th>
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">CAPACITY</th>
                                                <th class="px-4 py-3 text-[8px] font-black text-gray-600 uppercase">P.Lvl</th>
                                                <th class="px-4 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {#each currentStructure.levels as row}
                                                <tr class="border-b border-[#2a231e]/50 hover:bg-white/[0.01]">
                                                    <td class="px-4 py-3 text-red-500 font-mono text-xs font-bold">{row.level}</td>
                                                    <td class="px-4 py-3"><input type="text" bind:value={row.buff_name} class="bg-transparent border-none p-0 text-white text-[10px] font-bold focus:ring-0 w-24" /></td>
                                                    <td class="px-4 py-3"><input type="number" bind:value={row.cost} class="bg-transparent border-none p-0 text-[#c5a059] text-[10px] font-mono focus:ring-0 w-24" /></td>
                                                    <td class="px-4 py-3"><input type="number" bind:value={row.buff_hp} class="bg-transparent border-none p-0 text-gray-300 text-[10px] font-mono focus:ring-0 w-16" /></td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex gap-1">
                                                            <input type="number" bind:value={row.buff_offense} class="bg-transparent border-none p-0 text-red-500 text-[10px] font-mono focus:ring-0 w-8" />
                                                            <input type="number" bind:value={row.buff_defense} class="bg-transparent border-none p-0 text-[#3f6b2f] text-[10px] font-mono focus:ring-0 w-8" />
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3"><input type="number" bind:value={row.capacity} class="bg-transparent border-none p-0 text-blue-400 text-[10px] font-mono focus:ring-0 w-16" /></td>
                                                    <td class="px-4 py-3"><input type="number" bind:value={row.player_level_req} class="bg-transparent border-none p-0 text-white text-[10px] font-mono focus:ring-0 w-8" /></td>
                                                    <td class="px-4 py-3 text-right">
                                                        <button onclick={() => saveStructureLevel(currentStructure.details.id, row)} class="text-[#c5a059] font-black uppercase text-[10px]">
                                                            {savingId === `level-${currentStructure.details.id}-${row.level}` ? '...' : 'SAVE'}
                                                        </button>
                                                    </td>
                                                </tr>
                                            {/each}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    {/if}
                </div>

            {:else if activeModule === 'logs'}
                <div in:fade class="space-y-6">
                    <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-black/50 border-b border-[#2a231e]">
                                    <th class="px-6 py-4 text-[9px] font-black text-gray-500 uppercase tracking-widest">Time (UTC)</th>
                                    <th class="px-6 py-4 text-[9px] font-black text-gray-500 uppercase tracking-widest">Assault</th>
                                    <th class="px-6 py-4 text-[9px] font-black text-gray-500 uppercase tracking-widest">Result</th>
                                    <th class="px-6 py-4 text-[9px] font-black text-gray-500 uppercase tracking-widest">Loot</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each battleLogs as log}
                                    <tr class="border-b border-[#2a231e]/50 hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4 text-[10px] font-mono text-gray-500">{new Date(log.created_at).toLocaleString()}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-white uppercase">{log.attacker_name}</span>
                                                <span class="text-[10px] text-gray-600 font-black tracking-widest">VS</span>
                                                <span class="text-xs font-bold text-white uppercase">{log.defender_name}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[9px] font-black uppercase {log.result === 'attacker' ? 'text-[#3f6b2f]' : 'text-red-900'}">
                                                {log.result === 'attacker' ? 'Attacker Victory' : 'Defender Repelled'}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono text-[#c5a059] font-bold">+{log.gold_looted.toLocaleString()} GP</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </div>
            {/if}
        </main>
    </div>
</div>
