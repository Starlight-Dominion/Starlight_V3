<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    // Administrative Components
    import SovereignInspector from '../components/admin/SovereignInspector.svelte';
    import ArmoryInspector from '../components/admin/ArmoryInspector.svelte';
    import UnitInspector from '../components/admin/UnitInspector.svelte';
    import StructureInspector from '../components/admin/StructureInspector.svelte';
    import RaceInspector from '../components/admin/RaceInspector.svelte';
    import BotProfileInspector from '../components/admin/BotProfileInspector.svelte';
    import BotFoundry from '../components/admin/BotFoundry.svelte';
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
        { id: 'armory', name: 'Armory Forge', icon: '🛠' },
        { id: 'units', name: 'War Room (Units)', icon: '👥' },
        { id: 'structures', name: 'Structural Engineering', icon: '🏛' },
        { id: 'barracks', name: 'Evolutionary Strains', icon: '🧬' },
        { id: 'players', name: 'Sovereign Oversight', icon: '👁' },
        { id: 'api', name: 'Neural API Gate', icon: '📡' },
        { id: 'automation', name: 'Automation Suite', icon: '🤖' },
        { id: 'audit', name: 'Audit Trail', icon: '🕵️' },
        { id: 'docs', name: 'Documentation', icon: '📝' },
        { id: 'logs', name: 'Battle Records', icon: '📜' }
    ];

    let apiKeys = $state([]);
    let apiLogs = $state([]);
    let apiApps = $state([]);
    let botProfiles = $state([]);
    let auditLogs = $state([]);
    let apiTab = $state('keys'); // 'keys', 'apps', 'logs'

    // Bot Profile Inspector State
    let showBotProfileInspector = $state(false);
    let selectedBotProfile = $state(null);

    // Sovereign Inspector State
    let showInspector = $state(false);
    let inspectorData = $state(null);
    let inspectorTab = $state('identity'); // 'identity', 'stats', 'military', 'structures', 'armory'

    // Armory Inspector State
    let showArmoryInspector = $state(false);
    let selectedArmoryItem = $state(null);
    let armoryInspectorTab = $state('identity'); // 'identity', 'combat', 'reqs'

    // Unit Inspector State
    let showUnitInspector = $state(false);
    let selectedUnit = $state(null);
    let unitInspectorTab = $state('identity'); // 'identity', 'costs', 'yield', 'reqs'

    // Structure Inspector State
    let showStructureInspector = $state(false);
    let selectedStructure = $state(null);
    let structureInspectorTab = $state('blueprint'); // 'blueprint', 'matrix'

    // Race Inspector State
    let showRaceInspector = $state(false);
    let selectedRace = $state(null);
    let raceInspectorTab = $state('identity'); // 'identity', 'bonuses'
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
        
        // Dynamic payload for all user/dominion fields
        Object.entries(inspectorData.dominion.user).forEach(([k, v]) => {
            if (k !== 'dominion') formData.append(k, v === null ? '' : v);
        });
        Object.entries(inspectorData.dominion).forEach(([k, v]) => {
            if (typeof v !== 'object') formData.append(k, v === null ? '' : v);
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

    async function assignBotProfile(userId, profileId) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('bot_profile_id', profileId || '');
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/automation/assign-profile', formData);
        if (res) fetchAllKingdoms(); // Refresh player list to show new assignment
    }

    async function commissionBots(formData) {
        loading = true;
        try {
            const res = await fetch('/admin/automation/generate-bot', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                notify(`Single Unit Commissioned successfully.`);
                fetchAllKingdoms(); // Refresh player list
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

    function openRaceInspector(race) {
        selectedRace = race;
        raceInspectorTab = 'identity';
        showRaceInspector = true;
    }

    async function fetchAuditLogs() {
        loading = true;
        try {
            const res = await fetch('/admin/audit-logs');
            const data = await res.json();
            auditLogs = data.logs;
        } catch (e) { console.error("Failed to fetch audit logs"); } finally { loading = false; }
    }

    $effect(() => {
        if (activeModule === 'mechanics' || activeModule === 'doctrine' || activeModule === 'docs') fetchSettings();
        if (activeModule === 'units') fetchUnits();
        if (activeModule === 'structures') fetchStructures();
        if (activeModule === 'armory') fetchArmoryItems();
        if (activeModule === 'barracks') fetchRaces();
        if (activeModule === 'logs') fetchBattleLogs();
        if (activeModule === 'players') fetchAllKingdoms();
        if (activeModule === 'api') fetchApiData();
        if (activeModule === 'automation') fetchBotProfiles();
        if (activeModule === 'audit') fetchAuditLogs();
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
        formData.append('is_admin', kingdom.user.is_admin ? 1 : 0);
        formData.append('stasis_until', kingdom.user.stasis_until || '');
        formData.append('_csrf', game.csrf);
        
        await adminPost('/admin/update-kingdom', formData);
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

    // --- API Gate Functions ---
    async function issueApiKey(userId) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('rate_limit', 60);
        formData.append('_csrf', game.csrf);
        const res = await adminPost('/admin/api/issue', formData);
        if (res) fetchApiData();
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

    function openArmoryInspector(item) {
        selectedArmoryItem = item;
        armoryInspectorTab = 'identity';
        showArmoryInspector = true;
    }

    function openUnitInspector(unit) {
        selectedUnit = unit;
        unitInspectorTab = 'identity';
        showUnitInspector = true;
    }

    function openStructureInspector(s) {
        selectedStructure = s;
        structureInspectorTab = 'blueprint';
        showStructureInspector = true;
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

    function exportAuditLogsToJson() {
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(auditLogs, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href",     dataStr);
        downloadAnchorNode.setAttribute("download", `audit_logs_${new Date().toISOString()}.json`);
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
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
                <div in:fade class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
                            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Total Sovereigns</span>
                            <span class="text-4xl font-title font-black text-white">{stats.total_users || 0}</span>
                            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-cyan-500/10 transition-colors">USR</div>
                        </div>
                        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
                            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Active Sectors</span>
                            <span class="text-4xl font-title font-black text-white">{stats.total_kingdoms || 0}</span>
                            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-red-500/10 transition-colors">DOM</div>
                        </div>
                        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
                            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Total Wealth</span>
                            <span class="text-4xl font-title font-black text-emerald-500">{((stats.total_credits || 0) / 1000000).toFixed(1)}M</span>
                            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-emerald-500/10 transition-colors">CP</div>
                        </div>
                        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
                            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Total Population</span>
                            <span class="text-4xl font-title font-black text-cyan-400">{((stats.total_citizens || 0) / 1000).toFixed(1)}K</span>
                            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-cyan-400/10 transition-colors">POP</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl space-y-4">
                            <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">System Status</h3>
                            <div class="space-y-2 font-mono">
                                <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Database</span><span class="text-[10px] font-black text-cyan-400 uppercase">Connected</span></div>
                                <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Heartbeat</span><span class="text-[10px] font-black text-cyan-400 uppercase">Synchronized</span></div>
                                <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Total Manpower</span><span class="text-[10px] font-black text-white uppercase">{((stats.total_manpower || 0) / 1000).toFixed(1)}K Units</span></div>
                            </div>
                        </div>
                        <div class="bg-red-950/10 border border-red-900/20 p-8 rounded-3xl space-y-4">
                            <h3 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">Global Warning</h3>
                            <p class="text-gray-400 text-[10px] leading-relaxed italic">Changes made within this terminal affect the live production database immediately. Unauthorized modifications constitute sector-level treason.</p>
                        </div>
                    </div>
                </div>

            {:else if activeModule === 'mechanics'}
                <div in:fade class="space-y-8">
                    <!-- Comms Relay -->
                    <div class="bg-red-950/20 border border-red-900/30 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-red-900/20 px-8 py-5 border-b border-red-900/30 flex justify-between items-center">
                            <h2 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">Sector Comms Relay (Global Broadcast)</h2>
                        </header>
                        <div class="p-8 space-y-4">
                            {#each gameSettings.filter(s => s.setting_key === 'global_broadcast') as broadcast}
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                                    <div class="md:col-span-3">
                                        <span class="block text-[8px] font-black text-gray-500 uppercase tracking-widest mb-2">Announcement / Maintenance Signal</span>
                                        <input type="text" bind:value={broadcast.setting_value} class="w-full bg-black/60 border border-red-900/30 rounded-xl px-6 py-4 text-red-400 font-mono text-sm focus:border-red-500 outline-none" placeholder="Enter global transmission..." />
                                    </div>
                                    <div class="md:col-span-1 text-right">
                                        <button onclick={() => saveSetting(broadcast)} class="w-full md:w-auto px-8 py-4 bg-red-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-[0_0_15px_rgba(153,27,27,0.3)]">Transmit</button>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>

                    <!-- Recruitment Parameters -->
                    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-cyan-950/20 px-8 py-5 border-b border-white/5 flex justify-between items-center">
                            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Recruitment Parameters</h2>
                        </header>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                            {#each [
                                { key: 'recruitment_sessions_per_day', label: 'Daily Sessions', icon: '📅' },
                                { key: 'recruitment_sessions_per_3days', label: '72H Allocation', icon: '⏳' },
                                { key: 'recruitment_clicks_per_session', label: 'Clicks Per Session', icon: '🖱' },
                                { key: 'recruitment_click_cooldown_ms', label: 'Click Cooldown (ms)', icon: '⏱' }
                            ] as param}
                                {@const setting = gameSettings.find(s => s.setting_key === param.key)}
                                {#if setting}
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-end">
                                            <span class="text-[8px] font-black text-gray-500 uppercase tracking-widest">{param.icon} {param.label}</span>
                                            <button onclick={() => saveSetting(setting)} class="text-[8px] font-black text-cyan-500 hover:text-white uppercase tracking-widest disabled:opacity-30" disabled={savingId === setting.setting_key}>Commit</button>
                                        </div>
                                        <input type="number" bind:value={setting.setting_value} class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs focus:border-cyan-500 outline-none" />
                                    </div>
                                {/if}
                            {/each}
                        </div>
                    </div>

                    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-cyan-950/20 px-8 py-4 border-b border-cyan-500/10 flex justify-between items-center">
                            <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Global Balance Parameters</h2>
                        </header>
                        <div class="p-8 space-y-6">
                            {#each gameSettings.filter(s => !s.setting_key.startsWith('battle_') && s.setting_key !== 'global_broadcast') as setting}
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
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Sovereign Oversight</h3>
                            <p class="text-gray-500 font-bold uppercase tracking-[4px] text-[10px] mt-2 italic">Monitor and manage all active sectors and automated drones.</p>
                        </div>
                        <div class="flex gap-4">
                            <button 
                                onclick={() => { showBotFoundry = true; }}
                                class="px-8 py-4 bg-red-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-[0_0_20px_rgba(127,29,29,0.2)]"
                            >
                                Bot Foundry
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <input type="text" bind:value={searchQuery} placeholder="Search kingdoms..." class="flex-grow bg-black/60 border border-white/10 rounded-xl px-6 py-4 text-white focus:border-red-900 focus:outline-none font-mono" onkeydown={(e) => e.key === 'Enter' && handleSearch()} />
                        <button onclick={handleSearch} class="bg-red-900 text-white px-8 rounded-xl font-title font-black text-[10px] uppercase tracking-widest hover:bg-red-700 transition-all">Search</button>
                    </div>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        {#each searchResults as kingdom}
                            <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl flex justify-between items-center group hover:border-cyan-500/30 transition-all">
                                <div class="flex items-center gap-6">
                                    <div class="w-12 h-12 rounded-full bg-cyan-950/30 border border-cyan-500/20 flex items-center justify-center text-cyan-500 font-title font-black">
                                        {kingdom.id}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-white uppercase tracking-tight">{kingdom.name}</h4>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">CDR: {kingdom.user.username}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button onclick={() => impersonateCommander(kingdom.user.id)} class="px-4 py-2 bg-red-950/20 text-red-500 border border-red-900/30 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all opacity-0 group-hover:opacity-100">Impersonate</button>
                                    <button onclick={() => fetchKingdomProfile(kingdom.id)} class="px-6 py-3 bg-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all shadow-[0_0_15px_rgba(6,182,212,0.2)]">Sovereign Inspector</button>
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
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center px-4">
                                        <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">{cat.name}</h4>
                                        <button onclick={() => addArmoryItem(uType.slug, cat.id)} class="text-[9px] font-black text-gray-700 uppercase hover:text-cyan-400 transition-colors tracking-widest">+ NEW ASSET</button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                        {#each armoryItems.filter(i => i.category_id === cat.id) as item}
                                            <div class="bg-dark-translucent border border-white/5 p-6 rounded-2xl flex justify-between items-center group hover:border-amber-500/30 transition-all">
                                                <div class="flex items-center gap-6">
                                                    <div class="w-12 h-12 bg-amber-950/20 rounded-lg flex items-center justify-center border border-amber-500/10 text-amber-500 font-black">
                                                        {item.slug.substring(0,2).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <h4 class="text-xs font-black text-white uppercase tracking-tight">{item.name}</h4>
                                                        <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">{item.cost.toLocaleString()} CP</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <button onclick={() => deleteArmoryItem(item.id)} class="w-10 h-10 rounded-lg bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                                                    <button onclick={() => openArmoryInspector(item)} class="px-5 py-3 bg-amber-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-amber-400 transition-all shadow-[0_0_15px_rgba(217,119,6,0.2)]">Calibrate Asset</button>
                                                </div>
                                            </div>
                                        {/each}
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

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        {#each units as unit}
                            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-red-900/30 transition-all relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                                    {unit.slug.substring(0,3)}
                                </div>
                                <div class="space-y-6 relative z-10">
                                    <div class="flex items-center gap-6">
                                        <div class="w-16 h-16 bg-red-950/20 border border-red-900/30 rounded-2xl flex items-center justify-center text-red-600 text-2xl font-black">
                                            {unit.slug.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{unit.name}</h4>
                                            <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">{unit.slug}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                                            <span class="block text-[7px] font-black text-red-900 uppercase tracking-widest mb-1">ATK Power</span>
                                            <span class="text-xl font-title font-black text-red-500">{unit.power_offense}</span>
                                        </div>
                                        <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                                            <span class="block text-[7px] font-black text-cyan-900 uppercase tracking-widest mb-1">DEF Power</span>
                                            <span class="text-xl font-title font-black text-cyan-400">{unit.power_defense}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 mt-8 relative z-10">
                                    <button onclick={() => deleteUnit(unit.id)} class="w-12 h-12 rounded-xl bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                                    <button onclick={() => openUnitInspector(unit)} class="flex-grow py-4 bg-red-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-[0_0_20px_rgba(127,29,29,0.2)]">Tactical Calibration</button>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>

            {:else if activeModule === 'structures'}
                <div in:fade class="space-y-12">
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Structural Engineering</h3>
                        <button onclick={addStructure} class="bg-white text-black font-title font-black text-[10px] px-8 py-3 rounded-xl hover:bg-cyan-500 transition-all uppercase tracking-widest">Commission New Blueprint</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        {#each structures as s}
                            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-cyan-500/30 transition-all relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                                    {s.details.slug.substring(0,3)}
                                </div>
                                <div class="space-y-6 relative z-10">
                                    <div class="flex items-center gap-6">
                                        <div class="w-16 h-16 bg-cyan-950/20 border border-cyan-500/30 rounded-2xl flex items-center justify-center text-cyan-600 text-2xl font-black">
                                            {s.details.slug.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{s.details.name}</h4>
                                            <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">MAX RANK: {s.details.max_level}</p>
                                        </div>
                                    </div>
                                    <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                                        <span class="block text-[7px] font-black text-cyan-900 uppercase tracking-widest mb-1">Upgrade Matrix Status</span>
                                        <span class="text-sm font-mono text-cyan-400 font-black uppercase">{s.levels.length} Ranks Configured</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 mt-8 relative z-10">
                                    <button onclick={() => deleteStructure(s.details.id)} class="w-12 h-12 rounded-xl bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                                    <button onclick={() => openStructureInspector(s)} class="flex-grow py-4 bg-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all shadow-[0_0_20px_rgba(6,182,212,0.2)]">Structural Calibration</button>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>

            {:else if activeModule === 'barracks'}
                <div in:fade class="space-y-12">
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Evolutionary Strains</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        {#each races as race}
                            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-purple-500/30 transition-all relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                                    {race.name.substring(0,3)}
                                </div>
                                <div class="space-y-6 relative z-10">
                                    <div class="flex items-center gap-6">
                                        <div class="w-16 h-16 bg-purple-950/20 border border-purple-900/30 rounded-2xl flex items-center justify-center text-purple-600 text-2xl font-black">
                                            {race.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{race.name}</h4>
                                            <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">{race.bonus_type}</p>
                                        </div>
                                    </div>
                                    <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                                        <span class="block text-[7px] font-black text-purple-900 uppercase tracking-widest mb-1">Neural Multiplier</span>
                                        <span class="text-sm font-mono text-purple-400 font-black uppercase">{race.bonus_value}x</span>
                                    </div>
                                </div>
                                <div class="mt-8 relative z-10">
                                    <button onclick={() => openRaceInspector(race)} class="w-full py-4 bg-purple-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-700 transition-all shadow-[0_0_20px_rgba(147,51,234,0.2)]">Genetic Calibration</button>
                                </div>
                            </div>
                        {/each}
                    </div>
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

            {:else if activeModule === 'automation'}
                <div in:fade class="space-y-12">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Automation Suite</h3>
                            <p class="text-gray-500 font-bold uppercase tracking-[4px] text-[10px] mt-2 italic">Neural processing unit for automated sectors.</p>
                        </div>
                        <div class="flex gap-4">
                            <button 
                                onclick={() => { selectedBotProfile = { name: '', description: '', action_frequency_minutes: 60, weight_attack: 25, weight_build: 25, weight_train: 25, weight_explore: 25 }; showBotProfileInspector = true; }}
                                class="px-8 py-4 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-500 transition-all shadow-[0_0_20px_rgba(16,185,129,0.2)]"
                            >
                                Commission New Profile
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        {#each botProfiles as profile}
                            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-emerald-500/30 transition-all relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                                    {profile.name.substring(0,3)}
                                </div>
                                <div class="space-y-6 relative z-10">
                                    <div>
                                        <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{profile.name}</h4>
                                        <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">{profile.description || 'No directives defined.'}</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                                            <span class="block text-[7px] font-black text-emerald-900 uppercase tracking-widest mb-1">Frequency</span>
                                            <span class="text-sm font-mono text-emerald-400 font-black uppercase">{profile.action_frequency_minutes}m</span>
                                        </div>
                                        <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                                            <span class="block text-[7px] font-black text-gray-600 uppercase tracking-widest mb-1">Sectors</span>
                                            <span class="text-sm font-mono text-white font-black uppercase">{profile.users_count || 0}</span>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                                            <span class="text-red-500">Attack</span>
                                            <span class="text-white">{profile.weight_attack}%</span>
                                        </div>
                                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                            <div class="h-full bg-red-500" style="width: {profile.weight_attack}%"></div>
                                        </div>

                                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                                            <span class="text-blue-500">Build</span>
                                            <span class="text-white">{profile.weight_build}%</span>
                                        </div>
                                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500" style="width: {profile.weight_build}%"></div>
                                        </div>

                                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                                            <span class="text-amber-500">Train</span>
                                            <span class="text-white">{profile.weight_train}%</span>
                                        </div>
                                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                            <div class="h-full bg-amber-500" style="width: {profile.weight_train}%"></div>
                                        </div>

                                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                                            <span class="text-cyan-500">Explore</span>
                                            <span class="text-white">{profile.weight_explore}%</span>
                                        </div>
                                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                            <div class="h-full bg-cyan-500" style="width: {profile.weight_explore}%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 mt-8 relative z-10">
                                    <button onclick={() => deleteBotProfile(profile.id)} class="w-12 h-12 rounded-xl bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                                    <button onclick={() => { selectedBotProfile = {...profile}; showBotProfileInspector = true; }} class="flex-grow py-4 bg-emerald-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-[0_0_20px_rgba(16,185,129,0.2)]">Calibrate Profile</button>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>

            {:else if activeModule === 'audit'}
                <div in:fade class="space-y-6">
                    <div class="bg-dark-translucent border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                        <header class="bg-cyan-950/20 px-8 py-6 border-b border-white/5 flex justify-between items-center">
                            <div>
                                <h2 class="text-[10px] font-black text-cyan-500 uppercase tracking-[4px]">Neural Audit Trail</h2>
                                <p class="text-[8px] text-gray-600 uppercase mt-1">Full record of administrative directives.</p>
                            </div>
                            <button onclick={exportAuditLogsToJson} class="px-6 py-3 bg-white/5 border border-white/10 text-[9px] font-black uppercase text-gray-400 rounded-xl hover:bg-cyan-600 hover:text-white transition-all">
                                Neural Export (JSON)
                            </button>
                        </header>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse font-mono">
                                <thead>
                                    <tr class="bg-cyan-950/20 border-b border-white/5 text-[9px] font-black text-gray-500 uppercase tracking-widest">
                                        <th class="px-8 py-5 text-red-500">ID</th>
                                        <th class="px-8 py-5">ADMIN ID</th>
                                        <th class="px-8 py-5">OPERATION</th>
                                        <th class="px-8 py-5">LOG DIRECTIVE (DESCRIPTION)</th>
                                        <th class="px-8 py-5 text-right">TIMESTAMP</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    {#each auditLogs as log}
                                        <tr class="group hover:bg-white/[0.02] transition-colors border-b border-white/5 last:border-0">
                                            <td class="px-8 py-6 text-red-900 text-xs font-black">#{log.id}</td>
                                            <td class="px-8 py-6 text-gray-400 text-xs font-black">CMD_{log.dominion_id}</td>
                                            <td class="px-8 py-6 text-white text-xs font-black uppercase tracking-tighter">{log.action.replace('ADMIN_', '')}</td>
                                            <td class="px-8 py-6">
                                                <span class="text-xs text-cyan-400 font-black">{log.description}</span>
                                                {#if log.metadata}
                                                    <span class="block text-[8px] text-gray-700 mt-1 uppercase break-all whitespace-pre-wrap">{JSON.stringify(log.metadata, null, 2)}</span>
                                                {/if}
                                            </td>
                                            <td class="px-8 py-6 text-right text-[10px] text-gray-600 font-black uppercase tracking-widest">{log.created_at}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    </div>
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
