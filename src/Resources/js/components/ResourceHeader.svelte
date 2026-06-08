<script>
  import { game } from '../stores/gameStore.svelte.js';
  import { fade } from 'svelte/transition';

  const mainNavLinks = [
    { title: 'HOME', url: '/dashboard' },
    { title: 'BATTLE', url: '/combat/battlefield' },
    { title: 'STRUCTURES', url: '/structures' },
    { title: 'SECTOR GROUP', url: '/alliance' },
    { title: 'COMMUNITY', url: '/contact' },
    { title: 'SIGN OUT', url: '/logout' }
  ];

  const subNavConfig = $derived({
    'HOME': [
      { title: 'Dashboard', url: '/dashboard', component: 'dashboard/index' },
      { title: 'Bank', url: '/bank', component: 'bank/index' },
      { title: 'Settings', url: '/settings', component: 'settings/index' }
    ],
    'BATTLE': [
      { title: 'War Room', url: '/combat/battlefield', component: 'battlefield/index' },
      { title: 'Training', url: '/combat/training', component: 'training/index' },
      { title: 'Spy', url: '/spy', component: 'spy/index' },
      { title: 'Armory', url: '/structures/armory', component: 'armory/index' },
      { title: 'Recruitment', url: '/combat/recruit', component: 'combat/recruit' }
    ],
    'SECTOR GROUP': [
      { 
        title: game.user?.alliance ? `SECTOR GROUP: [${game.user.alliance.tag}]` : 'UNALIGNED', 
        url: '/alliance', 
        component: 'alliance/hub',
        isLabel: true 
      },
      { title: 'Alliance Hub', url: '/alliance', component: 'alliance/hub' },
      ...(game.user?.alliance ? [
        { title: 'Alliance Treasury', url: '/alliance/treasury', component: 'alliance/bank' },
        { title: 'Alliance Structures', url: '/alliance/structures', component: 'alliance/structures' },
        { title: 'Alliance Forum', url: '/alliance/forum', component: 'alliance/forum' },
        ...(game.user.alliance.can_manage ? [
            { title: 'Alliance Command', url: '/alliance/command', component: 'alliance/management' }
        ] : [])
      ] : [])
    ],
    'STRUCTURES': [
      { title: 'Overview', url: '/structures', component: 'structures/index' },
      { title: 'Foundation', url: '/structures/foundation', component: 'foundation/index' },
      { title: 'Mines', url: '/structures/mines', component: 'mines/index' },
      { title: 'Upgrades', url: '/structures/upgrades', component: 'upgrades/index' }
    ],
    'COMMUNITY': [
      { title: 'Signal Uplink', url: '/contact', component: 'pages/contact' },
      { title: 'Protocols', url: '/rules', component: 'pages/rules' },
      { title: 'Sector Laws', url: '/terms', component: 'pages/terms' }
    ]
  });

  const activeMainCategory = $derived.by(() => {
    const comp = game.component;
    for (const [cat, subLinks] of Object.entries(subNavConfig)) {
      if (subLinks.some(link => link.component === comp)) {
        return cat;
      }
    }
    return 'HOME';
  });
</script>

<div in:fade class="w-full max-w-7xl mx-auto px-6 pt-8 pb-4">
    <header class="text-center mb-8">
        <h1 class="text-5xl font-title font-black text-cyan-400 tracking-[8px] uppercase" style="text-shadow: 0 0 15px rgba(6, 182, 212, 0.6);">
            STARLIGHT DOMINION
        </h1>
        <p class="text-[9px] font-mono text-cyan-900 uppercase tracking-[4px] mt-2">Galactic Command Interface</p>
    </header>

    <div class="main-bg border border-cyan-500/10 rounded-xl shadow-2xl p-1 bg-[#0c1427]/60 backdrop-blur-md overflow-hidden">
        <nav class="flex justify-center flex-wrap items-center gap-x-2 md:gap-x-6 bg-[#030712]/80 p-3 rounded-t-lg">
            {#each mainNavLinks as link}
                <a 
                    href={link.url}
                    class="font-title text-[11px] font-black tracking-[2px] uppercase transition-all px-4 py-2 {link.title === activeMainCategory ? 'text-cyan-400 border-b-2 border-cyan-400' : 'text-gray-500 hover:text-white'}"
                >
                    {link.title}
                </a>
            {/each}
        </nav>

        {#if subNavConfig[activeMainCategory] && subNavConfig[activeMainCategory].length > 0}
            <div in:fade={{ duration: 150 }} class="bg-[#0c1427]/40 text-center p-3 flex justify-center flex-wrap gap-x-6 gap-y-1 rounded-b-lg border-t border-white/5">
                {#each subNavConfig[activeMainCategory] as link}
                    {#if link.isLabel}
                        <span class="text-[10px] font-black uppercase tracking-widest text-cyan-600/50 italic mr-2 border-r border-white/5 pr-6 py-1">
                            {link.title}
                        </span>
                    {:else}
                        <a 
                            href={link.url}
                            class="text-[10px] font-bold uppercase tracking-widest transition-all {game.component === link.component ? 'text-white shadow-[0_0_8px_white]' : 'text-gray-500 hover:text-cyan-400'}"
                        >
                            {link.title}
                        </a>
                    {/if}
                {/each}
            </div>
        {/if}
    </div>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
    .main-bg {
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.5), inset 0 0 20px rgba(6, 182, 212, 0.05);
    }
</style>
