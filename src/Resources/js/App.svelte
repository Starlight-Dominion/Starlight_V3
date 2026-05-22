<script>
    import { game } from './stores/gameStore.svelte.js';
    
    import ResourceHeader from './components/ResourceHeader.svelte';
    import TacticalSidebar from './components/TacticalSidebar.svelte';
    import PublicNav from './components/PublicNav.svelte';

    // Page Components
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
    import Recruit from './pages/Recruit.svelte';
    import BattleReport from './pages/BattleReport.svelte';
    import Rules from './pages/Rules.svelte';

    const components = {
        'home': Landing,
        'auth/login': Login,
        'auth/register': Register,
        'pages/about': About,
        'pages/rules': Rules,
        'pages/terms': Terms,
        'pages/contact': Contact,
        'dashboard/index': Dashboard,
        'admin/index': Admin,
        'structures/index': Structures,
        'mines': Mines,
        'battlefield/index': Battlefield,
        'battlefield/report': BattleReport,
        'armory/index': Armory,
        'bank/index': Bank,
        'foundation/index': Foundation,
        'upgrades/index': Upgrades,
        'settings/index': Settings,
        'spy/index': Spy,
        'training/index': Training,
        'combat/recruit': Recruit
    };

    const ActiveComponent = $derived.by(() => {
        const comp = components[game.component];
        if (comp) return comp;
        if (game.user && game.component === 'home') return Dashboard;
        return Landing;
    });

    const isPublicView = $derived(['home', 'pages/about', 'pages/rules', 'pages/terms', 'pages/contact', 'auth/login', 'auth/register'].includes(game.component));

    const bgUrl = "/images/backgroundMain.avif";
</script>

<div 
    class="min-h-screen flex flex-col bg-[#030712] text-gray-300 font-sans selection:bg-cyan-500/30 bg-cover bg-center bg-fixed relative"
    style="background-image: url('{bgUrl}');"
>
    <div class="absolute inset-0 bg-gradient-to-b from-[#030712]/60 via-[#030712]/50 to-[#030712]/90 z-0 pointer-events-none"></div>

    <div class="flex flex-col flex-grow z-10">
        {#if game.user && !isPublicView}
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

        <footer class="bg-[#030712]/95 backdrop-blur-md border-t border-cyan-500/10 py-12 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex flex-col items-center md:items-start gap-2">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 bg-cyan-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(6,182,212,1)]"></span>
                        <span class="text-white font-title font-black text-xs uppercase tracking-[4px]">
                            Starlight Dominion
                        </span>
                    </div>
                </div>

                <nav class="flex gap-10">
                    <a href="/rules" class="text-[10px] font-bold text-cyan-400 uppercase tracking-widest hover:text-white transition-colors">Protocols</a>
                    <a href="/terms" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-cyan-400 transition-colors">Sector Laws</a>
                    <a href="/about" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-cyan-400 transition-colors">Command Manual</a>
                    <a href="/contact" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-cyan-400 transition-colors">Signal Uplink</a>
                </nav>

                <div class="text-[9px] font-mono text-gray-700 uppercase tracking-tighter">
                    &copy; 2026 STARLIGHT DOMINION
                </div>
            </div>
        </footer>
    </div>
</div>
