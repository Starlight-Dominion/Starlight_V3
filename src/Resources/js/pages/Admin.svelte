<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    // Administrative Modules
    import OverviewModule from '../components/admin/modules/OverviewModule.svelte';
    import MechanicsModule from '../components/admin/modules/MechanicsModule.svelte';
    import DoctrineModule from '../components/admin/modules/DoctrineModule.svelte';
    import SovereignModule from '../components/admin/modules/SovereignModule.svelte';
    import ArmoryModule from '../components/admin/modules/ArmoryModule.svelte';
    import WarRoomModule from '../components/admin/modules/WarRoomModule.svelte';
    import StructuresModule from '../components/admin/modules/StructuresModule.svelte';
    import BarracksModule from '../components/admin/modules/BarracksModule.svelte';
    import ApiGateModule from '../components/admin/modules/ApiGateModule.svelte';
    import AutomationModule from '../components/admin/modules/AutomationModule.svelte';
    import AdvisorModule from '../components/admin/modules/AdvisorModule.svelte';
    import DocsModule from '../components/admin/modules/DocsModule.svelte';
    import LogsModule from '../components/admin/modules/LogsModule.svelte';

    // Inspector Components
    import SovereignInspector from '../components/admin/SovereignInspector.svelte';
    import ArmoryInspector from '../components/admin/ArmoryInspector.svelte';
    import UnitInspector from '../components/admin/UnitInspector.svelte';
    import StructureInspector from '../components/admin/StructureInspector.svelte';
    import RaceInspector from '../components/admin/RaceInspector.svelte';
    import BotProfileInspector from '../components/admin/BotProfileInspector.svelte';
    import BotFoundry from '../components/admin/BotFoundry.svelte';
    import LogDashboard from '../components/admin/logs/LogDashboard.svelte';

    let { stats = {} } = $props();

    let activeModule = $state('overview');
    let showBotFoundry = $state(false);
    let searchQuery = $state('');
    let searchResults = $state([]);
    let units = $state([]);
    let structures = $state([]);
    let armoryItems = $state([]);
    let unitTypes = $state([]);
    let categories = $state([]);
    let battleLogs = $state([]);
    let gameSettings = $state([]);
    let loading = $state(false);
    let savingId = $state(null);
    let notifications = $state([]);

    function notify(message, type = 'success') {
        const id = Math.random();
        notifications = [...notifications, { id, message, type }];
        setTimeout(() => {
            notifications = notifications.filter(n => n.id !== id);
        }, 5000);
    }

    async function adminPost(endpoint, formData) {
        try {
            const res = await fetch(endpoint, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                notify("Directives Committed Successfully");
                return true;
            } else {
                notify(data.message || "Directive Failure", "error");
                return false;
            }
        } catch (e) {
            notify("Neural Link Failure", "error");
            return false;
        }
    }

    const modules = [
        { id: 'overview', name: 'Command Overview', icon: '◈' },
        { id: 'mechanics', name: 'Global Mechanics', icon: '⚙' },
        { id: 'doctrine', name: 'Battle Doctrine', icon: '⚔' },
        { id: 'advisor', name: 'A.I. Advisor Panel', icon: '🧠' },
        { id: 'armory', name: 'Armory Forge', icon: '🛠' },
        { id: 'units', name: 'War Room (Units)', icon: '👥' },
        { id: 'structures', name: 'Structural Engineering', icon: '🏛' },
        { id: 'barracks', name: 'Evolutionary Strains', icon: '🧬' },
        { id: 'players', name: 'Sovereign Oversight', icon: '👁' },
        { id: 'api', name: 'Neural API Gate', icon: '📡' },
        { id: 'automation', name: 'Automation Suite', icon: '🤖' },
        { id: 'audit', name: 'Neural Audit Terminal', icon: '🕵️' },
        { id: 'docs', name: 'Documentation', icon: '📝' },
        { id: 'logs', name: 'Battle Records', icon: '📜' }
    ];

    let apiKeys = $state([]);
    let apiLogs = $state([]);
    let apiApps = $state([]);
    let botProfiles = $state([]);
    let apiTab = $state('keys'); // 'keys', 'apps', 'logs'

    // Bot Profile Inspector State
    let showBotProfileInspector = $state(false);
    let selectedBotProfile = $state(null);

    // Sovereign Inspector State
    let showInspector = $state(false);
    let inspectorData = $state(null);
    let inspectorTab = $state('identity');

    // Armory Inspector State
    let showArmoryInspector = $state(false);
    let selectedArmoryItem = $state(null);
    let armoryInspectorTab = $state('identity');

    // Unit Inspector State
    let showUnitInspector = $state(false);
    let selectedUnit = $state(null);
    let unitInspectorTab = $state('identity');

    // Structure Inspector State
    let showStructureInspector = $state(false);
    let selectedStructure = $state(null);
    let structureInspectorTab = $state('blueprint');

    // Race Inspector State
    let showRaceInspector = $state(false);
    let selectedRace = $state(null);
    let raceInspectorTab = $state('identity');
    let races = $state([]);

    async function fetchKingdomProfile(id) {
        loading = true;
        try {
            const res = await fetch(`/admin/kingdom/profile?id=${id}`);
            const data = await res.json();
            if (data.success) {
                inspectorData = data.profile;
                showInspector = true;
            } else {
                notify(data.message || "Uplink Failed", "error");
            }
        } catch (e) { notify("Neural Link Failure", "error"); } finally { loading = false; }
    }

    async function updateInspectorDominion() {
        savingId = 'inspector-save';
        const formData = new FormData();
        formData.append('id', inspectorData.dominion.id);
        Object.entries(inspectorData.dominion.user).forEach(([k, v]) => {
            if (k !== 'dominion') {
                if (typeof v === 'boolean') v = v ? 1 : 0;
                formData.append(k, v === null ? '' : v);
            }
        });
        Object.entries(inspectorData.dominion).forEach(([k, v]) => {
            if (typeof v !== 'object') {
                if (typeof v === 'boolean') v = v ? 1 : 0;
                formData.append(k, v === null ? '' : v);
            }
        });
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-kingdom', formData);
        savingId = null;
    }

    async function updateInspectorManpower(unit) {
        savingId = `unit-${unit.unit_id}`;
        const formData = new FormData();
        formData.append('dominion_id', inspectorData.dominion.id);
        formData.append('unit_id', unit.unit_id);
        formData.append('total_quantity', unit.total_quantity);
        formData.append('stabled_quantity', unit.stabled_quantity);
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-kingdom-manpower', formData);
        savingId = null;
    }

    async function updateInspectorStructure(s) {
        savingId = `structure-${s.structure_id}`;
        const formData = new FormData();
        formData.append('dominion_id', inspectorData.dominion.id);
        formData.append('structure_id', s.structure_id);
        formData.append('level', s.level);
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-kingdom-structure', formData);
        savingId = null;
    }

    async function updateInspectorArmory(item) {
        savingId = `item-${item.item_id}`;
        const formData = new FormData();
        formData.append('dominion_id', inspectorData.dominion.id);
        formData.append('item_id', item.item_id);
        formData.append('quantity', item.quantity);
        formData.append('is_equipped', item.is_equipped ? 1 : 0);
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-kingdom-armory', formData);
        savingId = null;
    }

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

    async function fetchBotProfiles() {
        loading = true;
        try {
            const res = await fetch('/admin/automation/profiles');
            const data = await res.json();
            botProfiles = data.profiles;
        } catch (e) { console.error("Failed to fetch bot profiles"); } finally { loading = false; }
    }

    async function saveBotProfile(profile) {
        savingId = profile.id || 'new-profile';
        const formData = new FormData();
        if (profile.id) formData.append('id', profile.id);
        formData.append('name', profile.name);
        formData.append('description', profile.description || '');
        formData.append('action_frequency_minutes', profile.action_frequency_minutes);
        formData.append('weight_attack', profile.weight_attack);
        formData.append('weight_build', profile.weight_build);
        formData.append('weight_train', profile.weight_train);
        formData.append('weight_explore', profile.weight_explore);
        formData.append('_csrf', game.csrf);

        const endpoint = profile.id ? '/admin/automation/profiles/update' : '/admin/automation/profiles/create';
        const res = await adminPost(endpoint, formData);
        if (res) fetchBotProfiles();
        savingId = null;
        showBotProfileInspector = false;
    }

    async function deleteBotProfile(id) {
        if (!confirm("Permanently delete this automation profile?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/automation/profiles/delete', formData);
        if (res) fetchBotProfiles();
    }

    async function commissionBots(formData) {
        loading = true;
        try {
            const res = await fetch('/admin/automation/generate-bot', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                notify(`Single Unit Commissioned successfully.`);
                fetchAllKingdoms();
                return true;
            } else {
                notify(data.message || "Production Failure", "error");
                return false;
            }
        } catch (e) {
            notify("Neural Link Failure", "error");
            return false;
        } finally {
            loading = false;
        }
    }

    async function fetchRaces() {
        loading = true;
        try {
            const res = await fetch('/admin/races');
            const data = await res.json();
            races = data.races;
        } catch (e) { console.error("Neural uplink failure"); } finally { loading = false; }
    }

    async function updateRace(race) {
        savingId = race.id;
        const formData = new FormData();
        formData.append('id', race.id);
        formData.append('name', race.name);
        formData.append('description', race.description);
        formData.append('bonus_type', race.bonus_type);
        formData.append('bonus_value', race.bonus_value);
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-race', formData);
        savingId = null;
    }

    $effect(() => {
        if (activeModule === 'mechanics' || activeModule === 'doctrine' || activeModule === 'docs' || activeModule === 'advisor') fetchSettings();
        if (activeModule === 'units') fetchUnits();
        if (activeModule === 'structures') fetchStructures();
        if (activeModule === 'armory') fetchArmoryItems();
        if (activeModule === 'barracks') fetchRaces();
        if (activeModule === 'logs') fetchBattleLogs();
        if (activeModule === 'players') fetchAllKingdoms();
        if (activeModule === 'api') fetchApiData();
        if (activeModule === 'automation') fetchBotProfiles();
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
        await adminPost('/admin/update-setting', formData);
        savingId = null;
    }

    async function saveUnit(unit) {
        savingId = unit.id;
        const formData = new FormData();
        formData.append('id', unit.id);
        formData.append('name', unit.name || '');
        formData.append('slug', unit.slug || '');
        formData.append('cost_credits', unit.cost_credits || 0);
        formData.append('cost_citizens', unit.cost_citizens || 0);
        formData.append('cost_turns', unit.cost_turns || 0);
        formData.append('power_offense', unit.power_offense || 0);
        formData.append('power_defense', unit.power_defense || 0);
        formData.append('power_spy_offense', unit.power_spy_offense || 0);
        formData.append('power_spy_defense', unit.power_spy_defense || 0);
        formData.append('production_credits', unit.production_credits || 0);
        formData.append('foundation_level_req', unit.foundation_level_req || 0);
        formData.append('requirement_slug', unit.requirement_slug || '');
        formData.append('description', unit.description || '');
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-unit', formData);
        savingId = null;
    }

    async function addUnit() {
        const formData = new FormData();
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/add-unit', formData);
        if (res) fetchUnits();
    }

    async function deleteUnit(id) {
        if (!confirm("Confirm decommissioning of this unit class?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/delete-unit', formData);
        if (res) fetchUnits();
    }

    async function saveStructureDetails(s) {
        savingId = s.id;
        const formData = new FormData();
        formData.append('id', s.id);
        formData.append('name', s.name || '');
        formData.append('upgrade_slots', s.upgrade_slots || 1);
        formData.append('max_level', s.max_level || 10);
        formData.append('description', s.description || '');
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-structure', formData);
        savingId = null;
    }

    async function addStructure() {
        const formData = new FormData();
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/add-structure', formData);
        if (res) fetchStructures();
    }

    async function deleteStructure(id) {
        if (!confirm("Permanently demolish this structure and all its ranks?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/delete-structure', formData);
        if (res) fetchStructures();
    }

    async function updateApiKey(key) {
        savingId = `api-${key.id}`;
        const formData = new FormData();
        formData.append('id', key.id);
        formData.append('rate_limit', key.rate_limit_per_minute);
        formData.append('is_active', key.is_active ? 1 : 0);
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/api/update', formData);
        savingId = null;
    }

    async function deleteApiKey(id) {
        if (!confirm("Permanently revoke this access token?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/api/delete', formData);
        if (res) fetchApiData();
    }

    async function processApp(app, action) {
        savingId = `app-${app.id}`;
        const formData = new FormData();
        formData.append('id', app.id);
        formData.append('action', action);
        formData.append('rate_limit', app._new_limit || 60);
        formData.append('notes', app.admin_notes || '');
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/api/process-app', formData);
        if (res) fetchApiData();
        savingId = null;
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
        await adminPost('/admin/update-structure-level', formData);
        savingId = null;
    }

    async function addStructureLevel(sId) {
        const s = structures.find(st => st.details.id === sId);
        const nextLvl = (s.levels.length > 0) ? (Math.max(...s.levels.map(l => l.level)) + 1) : 1;
        const formData = new FormData();
        formData.append('structure_id', sId);
        formData.append('level', nextLvl);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/add-structure-level', formData);
        if (res) fetchStructures();
    }

    async function saveArmoryItem(item) {
        savingId = item.id;
        const formData = new FormData();
        formData.append('id', item.id);
        formData.append('name', item.name);
        formData.append('slug', item.slug);
        formData.append('category_id', item.category_id);
        formData.append('unit_type', item.unit_type);
        formData.append('attack_bonus', item.attack_bonus);
        formData.append('defense_bonus', item.defense_bonus);
        formData.append('cost', item.cost);
        formData.append('requirement_slug', item.requirement_slug || '');
        formData.append('armory_level_req', item.armory_level_req);
        formData.append('notes', item.notes || '');
        formData.append('_csrf', game.csrf);
        await adminPost('/admin/update-armory-item', formData);
        savingId = null;
    }

    async function addArmoryItem(unitType, categoryId) {
        const formData = new FormData();
        formData.append('unit_type', unitType);
        formData.append('category_id', categoryId);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/add-armory-item', formData);
        if (res) fetchArmoryItems();
    }

    async function deleteArmoryItem(id) {
        if (!confirm("Confirm decommission of this asset?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/delete-armory-item', formData);
        if (res) fetchArmoryItems();
    }

    async function impersonateCommander(id) {
        if (!confirm("Caution: You are about to initiate a neural link with this commander's perspective. Proceed?")) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/impersonate', formData);
        if (res) {
            window.location.href = '/dashboard';
        }
    }
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
                <OverviewModule {stats} />
            {:else if activeModule === 'mechanics'}
                <MechanicsModule {gameSettings} {savingId} onSaveSetting={saveSetting} />
            {:else if activeModule === 'doctrine'}
                <DoctrineModule {gameSettings} {savingId} onSaveSetting={saveSetting} />
            {:else if activeModule === 'advisor'}
                <AdvisorModule {gameSettings} {savingId} onSaveSetting={saveSetting} />
            {:else if activeModule === 'players'}
                <SovereignModule 
                    bind:searchQuery={searchQuery} 
                    {searchResults} 
                    {loading} 
                    onSearch={handleSearch} 
                    onImpersonate={impersonateCommander} 
                    onInspect={fetchKingdomProfile} 
                    onShowBotFoundry={() => showBotFoundry = true}
                />
            {:else if activeModule === 'armory'}
                <ArmoryModule 
                    {armoryItems} {unitTypes} {categories} 
                    onInspect={(item) => { selectedArmoryItem = item; armoryInspectorTab = 'identity'; showArmoryInspector = true; }} 
                    onDelete={deleteArmoryItem} 
                    onAdd={addArmoryItem} 
                />
            {:else if activeModule === 'units'}
                <WarRoomModule 
                    {units} 
                    onAdd={addUnit} 
                    onDelete={deleteUnit} 
                    onInspect={(unit) => { selectedUnit = unit; unitInspectorTab = 'identity'; showUnitInspector = true; }} 
                />
            {:else if activeModule === 'structures'}
                <StructuresModule 
                    {structures} 
                    onAdd={addStructure} 
                    onDelete={deleteStructure} 
                    onInspect={(s) => { selectedStructure = s; structureInspectorTab = 'blueprint'; showStructureInspector = true; }} 
                />
            {:else if activeModule === 'barracks'}
                <BarracksModule {races} onInspect={(race) => { selectedRace = race; raceInspectorTab = 'identity'; showRaceInspector = true; }} />
            {:else if activeModule === 'api'}
                <ApiGateModule 
                    {apiKeys} {apiApps} {apiLogs} bind:apiTab={apiTab} {savingId} 
                    onUpdateKey={updateApiKey} onDeleteKey={deleteApiKey} onProcessApp={processApp} 
                />
            {:else if activeModule === 'automation'}
                <AutomationModule 
                    {botProfiles} 
                    onInspect={(profile) => { selectedBotProfile = {...profile}; showBotProfileInspector = true; }} 
                    onDelete={deleteBotProfile} 
                    onAdd={() => { selectedBotProfile = { name: '', description: '', action_frequency_minutes: 60, weight_attack: 25, weight_build: 25, weight_train: 25, weight_explore: 25 }; showBotProfileInspector = true; }} 
                />
            {:else if activeModule === 'audit'}
                <div in:fade><LogDashboard {game} /></div>
            {:else if activeModule === 'docs'}
                <DocsModule {gameSettings} {savingId} onSaveSetting={saveSetting} />
            {:else if activeModule === 'logs'}
                <LogsModule {battleLogs} />
            {/if}
        </main>
    </div>

    <!-- Modular Administrative Inspectors -->
    <SovereignInspector 
        bind:show={showInspector} 
        bind:data={inspectorData} 
        bind:tab={inspectorTab}
        savingId={savingId}
        botProfiles={botProfiles}
        onUpdateDominion={updateInspectorDominion}
        onUpdateManpower={updateInspectorManpower}
        onUpdateStructure={updateInspectorStructure}
        onUpdateArmory={updateInspectorArmory}
    />

    <ArmoryInspector
        bind:show={showArmoryInspector}
        bind:data={selectedArmoryItem}
        bind:tab={armoryInspectorTab}
        bind:savingId
        {unitTypes}
        {categories}
        allArmoryItems={armoryItems}
        onSave={saveArmoryItem}
    />

    <UnitInspector
        bind:show={showUnitInspector}
        bind:data={selectedUnit}
        bind:tab={unitInspectorTab}
        bind:savingId
        allUnits={units}
        onSave={saveUnit}
    />

    <StructureInspector
        bind:show={showStructureInspector}
        bind:data={selectedStructure}
        bind:tab={structureInspectorTab}
        bind:savingId
        onSaveDetails={saveStructureDetails}
        onSaveLevel={saveStructureLevel}
        onAddLevel={addStructureLevel}
    />

    <RaceInspector 
        bind:show={showRaceInspector} 
        bind:data={selectedRace} 
        bind:tab={raceInspectorTab}
        savingId={savingId}
        onSave={updateRace} 
    />

    <BotProfileInspector
        bind:show={showBotProfileInspector}
        bind:data={selectedBotProfile}
        savingId={savingId}
        onSave={saveBotProfile}
    />

    <BotFoundry
        bind:show={showBotFoundry}
        races={races}
        profiles={botProfiles}
        onCommission={commissionBots}
    />

    <div class="fixed bottom-8 right-8 z-[200] space-y-4 pointer-events-none">
        {#each notifications as n (n.id)}
            <div in:slide out:fade class="pointer-events-auto px-8 py-4 rounded-xl border font-title font-black text-[10px] uppercase tracking-widest shadow-2xl backdrop-blur-md {n.type === 'success' ? 'bg-cyan-900/40 border-cyan-500 text-cyan-400' : 'bg-red-900/40 border-red-500 text-red-400'}">
                {n.message}
            </div>
        {/each}
    </div>
</div>
