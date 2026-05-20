<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade } from 'svelte/transition';

    let isMobileMenuOpen = $state(false);

    const navLinks = [
        { name: 'Gameplay', href: '/about' },
        { name: 'Community', href: '/contact' },
        { name: 'Leaderboards', href: '/combat/battlefield' },
        { name: 'Terms', href: '/terms' }
    ];
</script>

<header class="fixed top-0 left-0 right-0 z-50 bg-dark-translucent border-b border-cyan-400/20">
    <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <a href="/" class="text-2xl md:text-3xl font-black tracking-wider font-title text-cyan-400 text-shadow-glow uppercase">
                Starlight Dominion
            </a>
            
            <nav class="hidden lg:flex space-x-10">
                {#each navLinks as link}
                    <a href={link.href} class="nav-link-public {game.component.includes(link.href.substring(1)) ? 'active' : ''}">
                        {link.name}
                    </a>
                {/each}
            </nav>

            <button 
                class="lg:hidden text-cyan-400 p-2"
                onclick={() => isMobileMenuOpen = !isMobileMenuOpen}
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {#if isMobileMenuOpen}
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    {:else}
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    {/if}
                </svg>
            </button>
        </div>
    </div>

    {#if isMobileMenuOpen}
        <div in:fade={{ duration: 200 }} class="lg:hidden bg-dark-translucent border-b border-cyan-400/20 py-6">
            <nav class="flex flex-col items-center space-y-6">
                {#each navLinks as link}
                    <a href={link.href} class="text-lg font-title text-cyan-400 uppercase tracking-widest">{link.name}</a>
                {/each}
            </nav>
        </div>
    {/if}
</header>