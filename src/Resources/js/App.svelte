<script>
    import { game } from './stores/gameStore.svelte.js';
    
    import ResourceHeader from './components/ResourceHeader.svelte';
    import TacticalSidebar from './components/TacticalSidebar.svelte';
    import PublicNav from './components/PublicNav.svelte';

    // Components
    import Landing from './pages/Landing.svelte';
    import Login from './pages/Login.svelte';
    import Register from './pages/Register.svelte';
    import About from './pages/About.svelte';
    import Terms from './pages/Terms.svelte';
    import Contact from './pages/Contact.svelte';
    import Dashboard from './pages/Dashboard.svelte';
    import Admin from './pages/Admin.svelte';
    import Structures from './pages/Structures.svelte';
    import Mines from './pages/Mines.svelte';
    import Battlefield from './pages/Battlefield.svelte';
    import Armory from './pages/Armory.svelte';
    import Bank from './pages/Bank.svelte';
    import Foundation from './pages/Foundation.svelte';
    import Upgrades from './pages/Upgrades.svelte';
    import Settings from './pages/Settings.svelte';
    import Spy from './pages/Spy.svelte';
    import Training from './pages/Training.svelte';
    import Stable from './pages/Stable.svelte';
    import BattleReport from './pages/BattleReport.svelte';

    const components = {
        'home': Landing,
        'auth/login': Login,
        'auth/register': Register,
        'pages/about': About,
        'pages/terms': Terms,
        'pages/contact': Contact,
        'dashboard/index': Dashboard,
        'admin/index': Admin,
        'structures/index': Structures,
        'mines/index': Mines,
        'battlefield/index': Battlefield,
        'battlefield/report': BattleReport,
        'armory/index': Armory,
        'bank/index': Bank,
        'foundation/index': Foundation,
        'upgrades/index': Upgrades,
        'settings/index': Settings,
        'spy/index': Spy,
        'training/index': Training,
        'stable/index': Stable
    };

    // Use a derived fallback logic
    const ActiveComponent = $derived.by(() => {
        const comp = components[game.component];
        if (comp) return comp;
        
        // Logical check: If user is present but component is home, force Dashboard
        if (game.user && game.component === 'home') return Dashboard;
        
        return Landing;
    });
</script>

<div class="min-h-screen flex flex-col bg-[#0a0a0a] text-[#d1d5db] font-sans">
    
    {#if game.user}
        <ResourceHeader />
        
        <main class="flex-grow w-full max-w-7xl mx-auto px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <TacticalSidebar />
                <section class="lg:col-span-3">
                    <ActiveComponent {...game.props} />
                </section>
            </div>
        </main>
    {:else}
        <PublicNav />
        <main class="flex-grow">
            <ActiveComponent {...game.props} />
        </main>
    {/if}

    <footer class="bg-black/80 border-t border-[#2a231e] py-12">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8 text-[10px] font-black uppercase tracking-[2px] text-[#5c4a3e]">
            <div>SHADOWREIGN &bull; Built for the Long Game</div>
            <div class="flex gap-8">
                <a href="/terms">Covenant</a>
                <a href="/about">Manual</a>
                <a href="/contact">Signal</a>
            </div>
        </div>
    </footer>
</div>
