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
    let gameSettings = $state([]);
    let activeStructureId = $state(null);
    let loading = $state(false);
    let savingId = $state(null);

    const modules = [
        { id: 'overview', name: 'Command Overview', icon: '◈' },
        { id: 'mechanics', name: 'Global Mechanics', icon: '⚙' },
        { id: 'doctrine', name: 'Battle Doctrine', icon: '⚔' },
        { id: 'armory', name: 'Armory Forge', icon: '🛠' },
        { id: 'units', name: 'War Room (Units)', icon: '👥' },
        { id: 'structures', name: 'Structural Engineering', icon: '🏛' },
        { id: 'players', name: 'Sovereign Oversight', icon: '👁' },
        { id: 'api', name: 'Neural API Gate', icon: '📡' },
        { id: 'docs', name: 'Documentation', icon: '📝' },
        { id: 'logs', name: 'Battle Records', icon: '📜' }
    ];

    let apiKeys = $state([]);
    let apiLogs = $state([]);
    let apiApps = $state([]);
    let apiTab = $state('keys'); // 'keys', 'apps', 'logs'

    async function fetchSettings() {
        loading = true;
        try {
            const res = await fetch('/admin/settings');
            const data = await res.json();
            gameSettings = data.settings;
        } catch (e) { console.error("Failed to fetch settings"); } finally { loading = false; }
    }

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

    async function fetchAllKingdoms() {
        loading = true;
        try {
            const res = await fetch('/admin/kingdoms');
            const data = await res.json();
            searchResults = data.results;
        } catch (e) { console.error("Failed to fetch kingdoms"); } finally { loading = false; }
    }

    async function fetchApiData() {
        loading = true;
        try {
            const keysRes = await fetch('/admin/api/keys');
            apiKeys = (await keysRes.json()).keys;

            const appsRes = await fetch('/admin/api/applications');
            apiApps = (await appsRes.json()).applications;

            const logsRes = await fetch('/admin/api/logs');
            apiLogs = (await logsRes.json()).logs;
        } catch (e) { console.error("Failed to fetch API data"); } finally { loading = false; }
    }

    $effect(() => {
        if (activeModule === 'mechanics' || activeModule === 'doctrine' || activeModule === 'docs') fetchSettings();
        if (activeModule === 'units') fetchUnits();
        if (activeModule === 'structures') fetchStructures();
        if (activeModule === 'armory') fetchArmoryItems();
        if (activeModule === 'logs') fetchBattleLogs();
        if (activeModule === 'players') fetchAllKingdoms();
        if (activeModule === 'api') fetchApiData();
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

    async function saveSetting(setting) {
        savingId = setting.setting_key;
        const formData = new FormData();
        formData.append('key', setting.setting_key);
        formData.append('value', setting.setting_value);
        formData.append('_csrf', game.csrf);
        try {
            await fetch('/admin/update-setting', { method: 'POST', body: formData });
        } catch (e) { console.error("Update failed"); } finally { savingId = null; }
    }

    async function saveKingdom(kingdom) {
        savingId = kingdom.id;
        const formData = new FormData();
        formData.append('id', kingdom.id);
        formData.append('name', kingdom.name);
        formData.append('username', kingdom.user.username);
        formData.append('credits', kingdom.credits);
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
        formData.append('cost_credits', unit.cost_credits);
        formData.append('cost_citizens', unit.cost_citizens);
        formData.append('cost_turns', unit.cost_turns);
        formData.append('power_offense', unit.power_offense);
        formData.append('power_defense', unit.power_defense);
        formData.append('foundation_level_req', unit.foundation_level_req);
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

    // --- API Gate Functions ---
    async function issueApiKey(userId) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('rate_limit', 60);
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/api/issue', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchApiData();
        } catch (e) { console.error("Failed to issue key"); }
    }

    async function updateApiKey(key) {
        savingId = `api-${key.id}`;
        const formData = new FormData();
        formData.append('id', key.id);
        formData.append('rate_limit', key.rate_limit_per_minute);
        formData.append('is_active', key.is_active);
        formData.append('_csrf', game.csrf);
        try {
            await fetch('/admin/api/update', { method: 'POST', body: formData });
        } catch (e) { console.error("Update failed"); } finally { savingId = null; }
    }

    async function deleteApiKey(id) {
        if (!confirm("Permanently revoke this access token?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        try {
            await fetch('/admin/api/delete', { method: 'POST', body: formData });
            fetchApiData();
        } catch (e) { console.error("Revocation failed"); }
    }

    async function processApp(app, action) {
        savingId = `app-${app.id}`;
        const formData = new FormData();
        formData.append('id', app.id);
        formData.append('action', action);
        formData.append('rate_limit', app._new_limit || 60);
        formData.append('notes', app.admin_notes || '');
        formData.append('_csrf', game.csrf);
        try {
            const res = await fetch('/admin/api/process-app', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchApiData();
        } catch (e) { console.error("Processing failed"); } finally { savingId = null; }
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
        formData.append('field', 'all'); // Special flag for multiple fields if needed, but we'll use individual
        
        const fields = ['name', 'slug', 'attack_bonus', 'defense_bonus', 'cost', 'requirement_slug', 'armory_level_req'];
        fields.forEach(f => {
            const fd = new FormData();
            fd.append('id', item.id);
            fd.append('field', f);
            fd.append('value', item[f]);
            fd.append('_csrf', game.csrf);
            fetch('/admin/update-armory-item', { method: 'POST', body: fd });
        });
        
        setTimeout(() => savingId = null, 500);
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
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow-red">Command Center</h1>
            <p class="text-red-500 font-bold uppercase tracking-[4px] text-[10px] mt-2 italic">Global authority & administration hub.</p>
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
                    class="w-full text-left px-6 py-4 rounded-xl border transition-all flex items-center gap-4 {activeModule === mod.id ? 'bg-red-900/20 border-red-900 text-white font-black shadow-[0_0_15px_rgba(127,29,29,0.3)]' : 'bg-dark-translucent border-white/5 text-gray-500 hover:border-red-900/30 hover:text-gray-300'}"
                >
                    <span class="text-lg opacity-50">{mod.icon}</span>
                    <span class="text-[10px] uppercase tracking-widest">{mod.name}</span>
                </button>
            {/each}
        </aside>

        <main class="lg:col-span-3 space-y-8">
            {#if activeModule === 'overview'}
                <div in:fade class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl space-y-4">
                        <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">System Status</h3>
                        <div class="space-y-2 font-mono">
                            <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Database</span><span class="text-[10px] font-black text-cyan-400 uppercase">Connected</span></div>
                            <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Heartbeat</span><span class="text-[10px] font-black text-cyan-400 uppercase">Synchronized</span></div>
                            <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Redis Cache</span><span class="text-[10px] font-black text-cyan-400 uppercase">Active</span></div>
                        </div>
                    </div>
                    <div class="bg-red-950/10 border border-red-900/20 p-8 rounded-3xl space-y-4">
                        <h3 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">Global Warning</h3>
                        <p class="text-gray-400 text-[10px] leading-relaxed italic">Changes made within this terminal affect the live production database immediately. Unauthorized modifications constitute sector-level treason.</p>
                    </div>
                </div>

            {:else if activeModule === 'mechanics'}
                <div in:fade class="space-y-6">
                    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-cyan-950/20 px-8 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Global Balance Parameters</h2>
                        </header>
                        <div class="p-8 space-y-6">
                            {#each gameSettings.filter(s => !s.setting_key.startsWith('battle_')) as setting}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center border-b border-white/5 pb-6 last:border-0 last:pb-0">
                                    <div class="md:col-span-1">
                                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">{setting.setting_key.replace(/_/g, ' ')}</span>
                                        <p class="text-[8px] text-gray-600 italic leading-tight">{setting.description}</p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <input type="text" bind:value={setting.setting_value} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-cyan-400 font-mono text-sm focus:border-cyan-500 focus:outline-none" />
                                    </div>
                                    <div class="md:col-span-1 text-right">
                                        <button onclick={() => saveSetting(setting)} class="px-6 py-2 bg-cyan-900/20 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-cyan-500 hover:text-black transition-all disabled:opacity-30" disabled={savingId === setting.setting_key}>
                                            {savingId === setting.setting_key ? 'SYNCING...' : 'UPDATE'}
                                        </button>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>

            {:else if activeModule === 'doctrine'}
                <div in:fade class="space-y-6">
                    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-red-950/20 px-8 py-4 border-b border-red-500/10 flex justify-between items-center">
                            <h2 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">War Doctrine Calibration</h2>
                        </header>
                        <div class="p-8 space-y-6">
                            {#each gameSettings.filter(s => s.setting_key.startsWith('battle_')) as setting}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center border-b border-white/5 pb-6 last:border-0 last:pb-0">
                                    <div class="md:col-span-1">
                                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">{setting.setting_key.replace(/battle_/g, '').replace(/_/g, ' ')}</span>
                                        <p class="text-[8px] text-gray-600 italic leading-tight">{setting.description}</p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <input type="text" bind:value={setting.setting_value} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-red-500 font-mono text-sm focus:border-red-500 focus:outline-none" />
                                    </div>
                                    <div class="md:col-span-1 text-right">
                                        <button onclick={() => saveSetting(setting)} class="px-6 py-2 bg-red-900/20 border border-red-500/30 text-red-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all disabled:opacity-30" disabled={savingId === setting.setting_key}>
                                            {savingId === setting.setting_key ? 'CALIBRATING...' : 'COMMIT'}
                                        </button>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>

            {:else if activeModule === 'players'}
                <div in:fade class="space-y-6">
                    <div class="flex gap-4">
                        <input type="text" bind:value={searchQuery} placeholder="Search kingdoms..." class="flex-grow bg-black/60 border border-white/10 rounded-xl px-6 py-4 text-white focus:border-red-900 focus:outline-none font-mono" onkeydown={(e) => e.key === 'Enter' && handleSearch()} />
                        <button onclick={handleSearch} class="bg-red-900 text-white px-8 rounded-xl font-title font-black text-[10px] uppercase tracking-widest hover:bg-red-700 transition-all">Search</button>
                    </div>
                    <div class="space-y-4">
                        {#each searchResults as kingdom}
                            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col gap-8 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                                    SECTOR {kingdom.id}
                                </div>
                                <div class="flex justify-between items-center relative z-10">
                                    <div class="flex-grow max-w-2xl grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Dominion Designation</span>
                                            <input type="text" bind:value={kingdom.name} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-2xl font-title font-black text-white uppercase tracking-tight focus:border-cyan-500 outline-none" />
                                        </div>
                                        <div class="space-y-2">
                                            <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Commander Handle</span>
                                            <input type="text" bind:value={kingdom.user.username} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-sm font-black text-gray-500 uppercase tracking-[2px] focus:border-cyan-500 outline-none" />
                                        </div>
                                    </div>
                                    <button onclick={() => saveKingdom(kingdom)} class="bg-cyan-600 text-white px-8 py-4 rounded-xl font-title font-black text-[10px] uppercase tracking-widest hover:bg-cyan-400 transition-all disabled:opacity-50 shadow-[0_0_10px_rgba(6,182,212,0.3)]" disabled={savingId === kingdom.id}>
                                        {savingId === kingdom.id ? 'UPLOADING...' : 'SAVE DIRECTIVES'}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-6 border-t border-white/5 relative z-10">
                                    {#each ['credits', 'xp', 'turns', 'citizens'] as field}
                                        <div class="space-y-2">
                                            <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">{field}</span>
                                            <input type="number" bind:value={kingdom[field]} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-sm font-mono text-cyan-400 focus:border-cyan-500" />
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
                            <div class="flex justify-between items-end border-b border-white/10 pb-4">
                                <div>
                                    <h3 class="text-2xl font-title font-black text-white uppercase tracking-tighter">{uType.name} Armament</h3>
                                    <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[4px] mt-1">{uType.title}</p>
                                </div>
                            </div>

                            {#each categories.filter(c => c.unit_type_id === uType.id) as cat}
                                <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-xl">
                                    <div class="bg-cyan-950/10 px-8 py-5 flex justify-between items-center border-b border-white/5">
                                        <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">{cat.name}</h4>
                                        <button onclick={() => addArmoryItem(uType.slug, cat.id)} class="text-[9px] font-black text-gray-500 uppercase hover:text-cyan-400 transition-colors tracking-widest">+ NEW ASSET</button>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse font-mono">
                                            <thead>
                                                <tr class="bg-black/40 text-[9px] font-black text-gray-600 uppercase tracking-[2px]">
                                                    <th class="px-8 py-4">Item Identity</th>
                                                    <th class="px-8 py-4">Stat Modification</th>
                                                    <th class="px-8 py-4">Requisition (CP)</th>
                                                    <th class="px-8 py-4">Prerequisites</th>
                                                    <th class="px-8 py-4"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-white/5">
                                                {#each armoryItems.filter(i => i.category_id === cat.id) as item}
                                                    <tr class="group hover:bg-white/[0.02] transition-colors">
                                                        <td class="px-8 py-6">
                                                            <input type="text" bind:value={item.name} class="bg-transparent border-none p-0 text-white text-sm font-black uppercase w-full focus:ring-0" />
                                                            <input type="text" bind:value={item.slug} class="bg-transparent border-none p-0 text-[9px] text-gray-700 uppercase font-mono w-full focus:ring-0 mt-1" />
                                                        </td>
                                                        <td class="px-8 py-6">
                                                            <div class="flex gap-4">
                                                                <div class="flex flex-col gap-1">
                                                                    <span class="text-[7px] text-red-900 uppercase font-black">ATK</span>
                                                                    <input type="number" bind:value={item.attack_bonus} class="bg-black/60 border border-white/10 px-3 py-1.5 text-red-500 text-xs font-mono w-20 rounded focus:border-red-900" />
                                                                </div>
                                                                <div class="flex flex-col gap-1">
                                                                    <span class="text-[7px] text-cyan-900 uppercase font-black">DEF</span>
                                                                    <input type="number" bind:value={item.defense_bonus} class="bg-black/60 border border-white/10 px-3 py-1.5 text-cyan-500 text-xs font-mono w-20 rounded focus:border-cyan-900" />
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-8 py-6">
                                                            <input type="number" bind:value={item.cost} class="bg-black/40 border border-white/5 px-4 py-2 text-cyan-400 text-sm font-black w-32 rounded focus:border-cyan-500" />
                                                        </td>
                                                        <td class="px-8 py-6">
                                                            <div class="flex flex-col gap-2">
                                                                <select bind:value={item.requirement_slug} class="bg-black/60 border border-white/10 text-[10px] text-gray-500 rounded px-3 py-2 focus:outline-none uppercase font-black tracking-tighter">
                                                                    <option value="">NO PREREQ</option>
                                                                    {#each armoryItems.filter(i => i.id !== item.id && i.unit_type === item.unit_type) as p}
                                                                        <option value={p.slug}>{p.name.toUpperCase()}</option>
                                                                    {/each}
                                                                </select>
                                                                <div class="flex items-center gap-2">
                                                                    <span class="text-[7px] text-gray-600 uppercase font-black">RANK REQ:</span>
                                                                    <input type="number" bind:value={item.armory_level_req} class="bg-black/60 border border-white/10 px-2 py-1 text-white text-[10px] font-mono w-12 rounded" />
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-8 py-6 text-right space-x-4">
                                                            <button onclick={() => saveArmoryItem(item)} class="text-cyan-500 font-black uppercase text-[10px] tracking-widest opacity-30 group-hover:opacity-100 hover:text-cyan-400 transition-all">{savingId === item.id ? '...' : 'COMMIT'}</button>
                                                            <button onclick={() => deleteArmoryItem(item.id)} class="text-red-900 hover:text-red-500 opacity-20 group-hover:opacity-100 transition-all text-xs">✕</button>
                                                        </td>
                                                    </tr>
                                                {/each}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            {/each}
                        </section>
                    {/each}
                </div>

            {:else if activeModule === 'units'}
                <div in:fade class="space-y-12">
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Combat Doctrine</h3>
                        <button onclick={addUnit} class="bg-white text-black font-title font-black text-[10px] px-8 py-3 rounded-xl hover:bg-cyan-500 transition-all uppercase tracking-widest">Enlist New Class</button>
                    </div>

                    <div class="grid grid-cols-1 gap-8">
                        {#each units as unit}
                            <div class="bg-dark-translucent border border-white/5 p-10 rounded-3xl space-y-10 relative group hover:border-red-900/20 transition-all">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-8">
                                        <div class="w-20 h-20 bg-red-950/20 border border-red-900/30 rounded-2xl flex items-center justify-center text-red-600 text-3xl font-black shadow-[inset_0_0_20px_rgba(153,27,27,0.1)]">
                                            {unit.slug.charAt(0).toUpperCase()}
                                        </div>
                                        <div class="flex-grow">
                                            <input type="text" bind:value={unit.name} class="bg-transparent border-none text-3xl font-title font-black text-white uppercase focus:ring-0 p-0 w-full tracking-wider" />
                                            <div class="flex items-center gap-6 mt-2">
                                                <p class="text-[10px] font-black text-gray-700 uppercase tracking-[3px]">Protocol ID: {unit.slug}</p>
                                                <input type="text" bind:value={unit.slug} class="bg-black/60 border border-white/10 rounded px-3 py-1 text-[10px] font-mono text-gray-500 w-48 focus:border-red-900" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <button onclick={() => saveUnit(unit)} class="bg-red-900/20 border border-red-900/50 text-red-500 px-8 py-4 rounded-xl font-title font-black text-[10px] uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all disabled:opacity-50" disabled={savingId === unit.id}>
                                            {savingId === unit.id ? 'COMMITTING...' : 'UPDATE DOCTRINE'}
                                        </button>
                                        <button onclick={() => deleteUnit(unit.id)} class="text-red-900 hover:text-red-500 p-4 transition-colors">✕</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-16 pt-10 border-t border-white/5 font-mono">
                                    <div class="space-y-6">
                                        <h4 class="text-[10px] font-black text-gray-600 uppercase tracking-[5px]">Requisition (Cost)</h4>
                                        <div class="grid grid-cols-3 gap-6">
                                            {#each ['cost_credits', 'cost_citizens', 'cost_turns'] as field}
                                                <div class="space-y-2">
                                                    <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">{field.split('_')[1]}</span>
                                                    <input type="number" bind:value={unit[field]} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-xs font-mono text-cyan-400" />
                                                </div>
                                            {/each}
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <h4 class="text-[10px] font-black text-gray-600 uppercase tracking-[5px]">Tactical Yield</h4>
                                        <div class="grid grid-cols-3 gap-6">
                                            <div class="space-y-2">
                                                <span class="block text-[8px] font-black text-red-900 uppercase tracking-widest text-shadow-glow-red">Offense</span>
                                                <input type="number" bind:value={unit.power_offense} class="w-full bg-black/60 border border-red-900/20 rounded-lg px-4 py-3 text-xs font-mono text-red-500" />
                                            </div>
                                            <div class="space-y-2">
                                                <span class="block text-[8px] font-black text-cyan-900 uppercase tracking-widest text-shadow-glow">Defense</span>
                                                <input type="number" bind:value={unit.power_defense} class="w-full bg-black/60 border border-cyan-900/20 rounded-lg px-4 py-3 text-xs font-mono text-cyan-400" />
                                            </div>
                                            <div class="space-y-2">
                                                <span class="block text-[8px] font-black text-emerald-900 uppercase tracking-widest">Prod (CP)</span>
                                                <input type="number" bind:value={unit.production_credits} class="w-full bg-black/60 border border-emerald-900/20 rounded-lg px-4 py-3 text-xs font-mono text-emerald-500" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <h4 class="text-[10px] font-black text-gray-600 uppercase tracking-[5px]">Tech Prereq</h4>
                                        <div class="space-y-2">
                                             <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Foundation Rank</span>
                                             <input type="number" bind:value={unit.foundation_level_req} class="w-full bg-black/60 border border-white/10 rounded-lg px-4 py-3 text-xs font-mono text-white" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <span class="block text-[8px] font-black text-gray-600 uppercase tracking-widest">Division Dossier (Description)</span>
                                    <textarea bind:value={unit.description} class="w-full bg-black/60 border border-white/10 rounded-2xl p-6 text-[11px] text-gray-400 focus:border-red-900 focus:outline-none min-h-[100px] leading-relaxed italic"></textarea>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>

            {:else if activeModule === 'structures'}
                <div in:fade class="space-y-8">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-wrap gap-2">
                            {#each structures as s}
                                <button onclick={() => activeStructureId = s.details.id} class="px-6 py-3 rounded-xl font-title font-black text-[10px] uppercase tracking-widest border transition-all {activeStructureId === s.details.id ? 'bg-red-900 border-red-900 text-white shadow-[0_0_10px_#7f1d1d]' : 'bg-black/60 border-white/5 text-gray-600 hover:text-gray-300'}">{s.details.name}</button>
                            {/each}
                        </div>
                        <button onclick={addStructure} class="text-cyan-500 font-title font-black text-[10px] uppercase tracking-widest hover:text-white transition-colors">+ NEW ARCHTYPE</button>
                    </div>

                    {#if currentStructure}
                        <div in:slide class="bg-dark-translucent border border-white/5 p-10 rounded-3xl space-y-10 relative group">
                            <div class="flex justify-between items-start">
                                <h3 class="text-xs font-black text-gray-600 uppercase tracking-[5px]">Structural Configuration</h3>
                                <div class="flex gap-4">
                                    <button onclick={() => saveStructureDetails(currentStructure.details)} class="bg-cyan-900/20 border border-cyan-500/50 text-cyan-400 px-8 py-3 rounded-xl font-title font-black text-[10px] uppercase tracking-widest hover:bg-cyan-600 hover:text-white transition-all disabled:opacity-50" disabled={savingId === currentStructure.details.id}>
                                        {savingId === currentStructure.details.id ? 'UPLOADING...' : 'COMMIT ARCHITECTURE'}
                                    </button>
                                    <button onclick={() => deleteStructure(currentStructure.details.id)} class="text-red-900 hover:text-red-500 text-[10px] font-black uppercase tracking-widest border border-red-950 px-4 py-1 rounded">Demolish ✕</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 font-mono">
                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Building Designation</span>
                                        <input type="text" bind:value={currentStructure.details.name} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-4 text-sm font-black text-white uppercase tracking-wider" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Upgrade Slots</span>
                                            <input type="number" bind:value={currentStructure.details.upgrade_slots} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-xs font-mono text-cyan-400" />
                                        </div>
                                        <div class="space-y-2">
                                            <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Max Tier</span>
                                            <input type="number" bind:value={currentStructure.details.max_level} class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-xs font-mono text-cyan-400" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <span class="block text-[8px] font-black text-gray-700 uppercase tracking-widest">Operational Directives (Description)</span>
                                    <textarea bind:value={currentStructure.details.description} class="w-full bg-black/60 border border-white/10 rounded-2xl p-6 text-[11px] text-gray-400 focus:border-red-900 focus:outline-none min-h-[120px] leading-relaxed"></textarea>
                                </div>
                            </div>

                            <div class="space-y-6 pt-10 border-t border-white/5">
                                <div class="flex justify-between items-center px-2">
                                    <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-[5px]">Rank Evolution Matrix</h4>
                                    <button onclick={() => addStructureLevel(currentStructure.details.id)} class="text-[9px] font-black text-gray-700 hover:text-white uppercase tracking-widest transition-colors">+ ADD RANK</button>
                                </div>
                                <div class="bg-black/40 rounded-3xl overflow-hidden shadow-2xl overflow-x-auto border border-white/5">
                                    <table class="w-full text-left border-collapse min-w-[1200px] font-mono">
                                        <thead>
                                            <tr class="bg-cyan-950/20 text-[9px] font-black text-gray-600 uppercase tracking-widest">
                                                <th class="px-6 py-5">Rank</th>
                                                <th class="px-6 py-5">Label</th>
                                                <th class="px-6 py-5">Cost (CP)</th>
                                                <th class="px-6 py-5">Integrity (HP)</th>
                                                <th class="px-6 py-5">ATK / DEF Buff</th>
                                                <th class="px-6 py-5">Economy Buff (%)</th>
                                                <th class="px-6 py-5">Citizens / Tick</th>
                                                <th class="px-6 py-5">Military Reinforcements</th>
                                                <th class="px-6 py-5">Capacity</th>
                                                <th class="px-6 py-5">Req. Lvl</th>
                                                <th class="px-6 py-5"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-white/5">
                                            {#each currentStructure.levels as row}
                                                <tr class="hover:bg-white/[0.02] transition-colors">
                                                    <td class="px-6 py-5 text-red-600 font-black text-sm italic">{row.level}</td>
                                                    <td class="px-6 py-5"><input type="text" bind:value={row.buff_name} class="bg-transparent border-none p-0 text-white text-[11px] font-black uppercase focus:ring-0 w-32" /></td>
                                                    <td class="px-6 py-5"><input type="number" bind:value={row.cost} class="bg-transparent border-none p-0 text-cyan-400 text-xs font-mono focus:ring-0 w-28" /></td>
                                                    <td class="px-6 py-5"><input type="number" bind:value={row.buff_hp} class="bg-transparent border-none p-0 text-gray-400 text-xs font-mono focus:ring-0 w-20" /></td>
                                                    <td class="px-6 py-5">
                                                        <div class="flex gap-2">
                                                            <input type="number" bind:value={row.buff_offense} class="bg-transparent border border-white/5 rounded px-2 py-1 text-red-500 text-[10px] font-mono focus:ring-0 w-12" title="Attack" />
                                                            <input type="number" bind:value={row.buff_defense} class="bg-transparent border border-white/5 rounded px-2 py-1 text-cyan-500 text-[10px] font-mono focus:ring-0 w-12" title="Defense" />
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-5"><input type="number" bind:value={row.buff_economy} class="bg-transparent border-none p-0 text-green-500 text-xs font-mono focus:ring-0 w-16" /></td>
                                                    <td class="px-6 py-5"><input type="number" bind:value={row.buff_citizens_per_tick} class="bg-transparent border-none p-0 text-blue-300 text-xs font-mono focus:ring-0 w-16" /></td>
                                                    <td class="px-6 py-5">
                                                        <div class="flex flex-wrap gap-1 max-w-[200px]">
                                                            <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">G</span><input type="number" bind:value={row.buff_unit_guards} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                            <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">S</span><input type="number" bind:value={row.buff_unit_soldiers} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                            <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">SP</span><input type="number" bind:value={row.buff_unit_spies} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                            <div class="flex items-center gap-1 bg-black/40 px-1 rounded"><span class="text-[7px] text-gray-700 font-black">SE</span><input type="number" bind:value={row.buff_unit_sentries} class="bg-transparent border-none p-0 text-white text-[9px] font-mono w-8" /></div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-5"><input type="number" bind:value={row.capacity} class="bg-transparent border-none p-0 text-blue-400 text-xs font-mono focus:ring-0 w-20" /></td>
                                                    <td class="px-6 py-5"><input type="number" bind:value={row.player_level_req} class="bg-transparent border-none p-0 text-white text-xs font-mono focus:ring-0 w-12" /></td>
                                                    <td class="px-6 py-5 text-right">
                                                        <button onclick={() => saveStructureLevel(currentStructure.details.id, row)} class="text-cyan-500 font-black uppercase text-[10px] tracking-[2px] hover:text-white transition-all">
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

            {:else if activeModule === 'api'}
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
                                                <button onclick={() => updateApiKey(key)} class="text-cyan-500 font-black uppercase text-[10px] tracking-widest opacity-30 group-hover:opacity-100 hover:text-cyan-400 transition-all">{savingId === `api-${key.id}` ? '...' : 'UPDATE'}</button>
                                                <button onclick={() => deleteApiKey(key.id)} class="text-red-900 hover:text-red-500 opacity-20 group-hover:opacity-100 transition-all text-xs" title="Revoke">✕</button>
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
                                            <button onclick={() => processApp(app, 'approve')} class="bg-cyan-900/20 border border-cyan-500/50 text-cyan-400 px-6 py-3 rounded-lg font-title font-black text-[10px] uppercase tracking-widest hover:bg-cyan-600 hover:text-white transition-all disabled:opacity-50" disabled={savingId === `app-${app.id}`}>
                                                APPROVE
                                            </button>
                                            <button onclick={() => processApp(app, 'reject')} class="bg-red-900/10 border border-red-900/30 text-red-500 px-6 py-3 rounded-lg font-title font-black text-[10px] uppercase tracking-widest hover:bg-red-900 hover:text-white transition-all disabled:opacity-50" disabled={savingId === `app-${app.id}`}>
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

            {:else if activeModule === 'docs'}
                <div in:fade class="space-y-6">
                    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-cyan-950/20 px-8 py-4 border-b border-cyan-500/10">
                            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Sector Documentation</h2>
                        </header>
                        <div class="p-8 space-y-8">
                            {#each gameSettings.filter(s => s.setting_key === 'official_rules') as setting}
                                <div class="space-y-4">
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Official Rules (Markdown)</span>
                                            <p class="text-[8px] text-gray-600 italic leading-tight">Primary protocols displayed on the public /rules page.</p>
                                        </div>
                                        <button onclick={() => saveSetting(setting)} class="px-8 py-3 bg-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all disabled:opacity-30" disabled={savingId === setting.setting_key}>
                                            {savingId === setting.setting_key ? 'UPLOADING...' : 'COMMIT CHANGES'}
                                        </button>
                                    </div>
                                    <textarea bind:value={setting.setting_value} class="w-full bg-black/60 border border-white/10 rounded-2xl p-8 text-cyan-400 font-mono text-sm focus:border-cyan-500 focus:outline-none min-h-[600px] leading-relaxed" placeholder="# Neural Protocols..."></textarea>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>

            {:else if activeModule === 'logs'}
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
            {/if}
        </main>
    </div>
</div>
