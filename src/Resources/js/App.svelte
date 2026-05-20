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

    /**
     * Active Component Resolution
     * Determines which view to mount based on PHP routing state.
     */
    const ActiveComponent = $derived.by(() => {
        const comp = components[game.component];
        if (comp) return comp;
        
        // Logical Fallback: Authenticated home should always be Dashboard
        if (game.user && game.component === 'home') return Dashboard;
        
        return Landing;
    });
</script>

<div class="min-h-screen flex flex-col bg-[#030712] text-gray-300 font-sans selection:bg-cyan-500/30">
    
    {#if game.user}
        <!-- Authenticated HUD -->
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
        <!-- Public Interface -->
        <PublicNav />
        <main class="flex-grow">
            <ActiveComponent {...game.props} />
        </main>
    {/if}

    <!-- Futuristic Tactical Footer -->
    <footer class="bg-[#030712]/95 backdrop-blur-md border-t border-cyan-500/10 py-12 relative overflow-hidden">
        <!-- Subtle background scanline effect -->
        <div class="absolute inset-0 pointer-events-none opacity-[0.03] bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] bg-[length:100%_2px,3px_100%]"></div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex flex-col items-center md:items-start gap-2">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 bg-cyan-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(6,182,212,1)]"></span>
                    <span class="text-white font-title font-black text-xs uppercase tracking-[4px]">
                        Starlight Dominion
                    </span>
                </div>
                <span class="text-[9px] font-bold text-gray-600 uppercase tracking-[2px] ml-5">
                    Decentralized Sector Command • v1.0.4-Alpha
                </span>
            </div>

            <nav class="flex gap-10">
                <a href="/terms" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-cyan-400 transition-colors">Sector Laws</a>
                <a href="/about" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-cyan-400 transition-colors">Command Manual</a>
                <a href="/contact" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-cyan-400 transition-colors">Signal Uplink</a>
            </nav>

            <div class="text-[9px] font-mono text-gray-700 uppercase tracking-tighter">
                &copy; 2026 STARLIGHT DOMINION • ALL SECTORS MONITORED
            </div>
        </div>
    </footer>
</div>

<style>
    /* Global scrollbar styling for the sci-fi theme */
    :global(::-webkit-scrollbar) {
        width: 6px;
    }
    :global(::-webkit-scrollbar-track) {
        background: #030712;
    }
    :global(::-webkit-scrollbar-thumb) {
        background: #0e1a2b;
        border-radius: 10px;
    }
    :global(::-webkit-scrollbar-thumb:hover) {
        background: #162a3d;
    }
</style>